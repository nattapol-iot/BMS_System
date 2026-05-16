<?php
namespace App\Console\Commands;

use App\Models\Schedule;
use App\Models\ScheduleRun;
use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Carbon\Carbon;

class RunSchedulesCommand extends Command
{
    protected $signature = 'schedules:run {--dry-run : Show what would run without applying changes}';
    protected $description = 'Execute due schedules — turn equipment on/off according to schedule windows';

    public function handle(): int
    {
        $dry = $this->option('dry-run');
        $now = Carbon::now();
        $today = strtolower($now->format('D'));
        $hm = $now->format('H:i:s');

        $this->info("[" . $now->format('Y-m-d H:i:s') . "] Running schedules" . ($dry ? ' (DRY RUN)' : ''));

        $schedules = Schedule::with('equipment')->where('status', 'active')->get();

        $turnedOn = 0;
        $turnedOff = 0;
        foreach ($schedules as $s) {
            $repeatDays = is_array($s->repeat_days) ? $s->repeat_days : [];
            if (!empty($repeatDays) && !in_array($today, $repeatDays)) {
                continue;
            }
            if ($s->start_date && $s->start_date > $now) continue;
            if ($s->end_date && $s->end_date < $now) continue;

            $onTime = $s->turn_on_time;
            $offTime = $s->turn_off_time;

            $shouldTurnOn = $onTime && $hm >= $onTime && (!$offTime || $hm < $offTime);
            $shouldTurnOff = $offTime && $hm >= $offTime;

            foreach ($s->equipment as $eq) {
                if ($shouldTurnOn && $eq->status !== 'active') {
                    $this->line("  ON  {$eq->code} (schedule: {$s->name})");
                    if (!$dry) {
                        $eq->update(['status' => 'active']);
                        $turnedOn++;
                    }
                } elseif ($shouldTurnOff && $eq->status === 'active') {
                    $this->line("  OFF {$eq->code} (schedule: {$s->name})");
                    if (!$dry) {
                        $eq->update(['status' => 'inactive']);
                        $turnedOff++;
                    }
                }
            }

            if (!$dry && ($shouldTurnOn || $shouldTurnOff)) {
                ScheduleRun::create([
                    'schedule_id' => $s->id,
                    'status' => 'success',
                    'run_at' => $now,
                    'message' => $shouldTurnOn ? 'Turn ON window' : 'Turn OFF window',
                ]);
            }
        }

        $this->info("Done. ON: {$turnedOn}, OFF: {$turnedOff}");

        if (!$dry && ($turnedOn + $turnedOff) > 0) {
            ActivityLog::create([
                'user_id' => null,
                'action' => 'update',
                'module' => 'schedules',
                'description' => "Schedule runner: {$turnedOn} ON, {$turnedOff} OFF",
                'ip_address' => '127.0.0.1',
                'user_agent' => 'artisan schedule:runner',
            ]);
        }

        return self::SUCCESS;
    }
}
