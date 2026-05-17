<?php
namespace App\Modules\SCADA\Console;

use App\Modules\SCADA\Models\AlarmEvent;
use App\Modules\SCADA\Models\Tag;
use App\Modules\SCADA\Models\TagValueCurrent;
use App\Modules\SCADA\Models\TagValueHistory;
use App\Modules\SCADA\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * scada:simulate
 * --------------
 * Continuously advances every SCADA tag with a realistic random walk, writes
 * to scada_tag_values_current (upsert) and scada_tag_values_history (insert),
 * and raises alarm events on threshold crossings.
 *
 * Designed for demo + integration testing — replace with real protocol pollers
 * for production via app/Integrations/Modbus, Mqtt, etc.
 */
class SimulateCommand extends Command
{
    protected $signature = 'scada:simulate
        {--tenant= : Tenant ID or slug to simulate (default: all)}
        {--cycles=0 : Number of ticks to run (0 = forever)}
        {--interval=5 : Seconds between ticks}
        {--dry-run : Print actions, do not write to DB}';

    protected $description = 'Continuously generate realistic SCADA tag values + alarms (demo simulator)';

    public function handle(): int
    {
        $tenantFilter = $this->option('tenant');
        $cycles       = (int) $this->option('cycles');
        $interval     = max(1, (int) $this->option('interval'));
        $dry          = (bool) $this->option('dry-run');

        $tenants = $this->resolveTenants($tenantFilter);
        if ($tenants->isEmpty()) {
            $this->error('No tenants matched.');
            return self::FAILURE;
        }

        $tags = Tag::query()
            ->whereIn('tenant_id', $tenants->pluck('id'))
            ->where('status', 'active')
            ->get();

        if ($tags->isEmpty()) {
            $this->error('No active SCADA tags found for the selected tenant(s).');
            return self::FAILURE;
        }

        $this->info(sprintf(
            'SCADA simulator: %d tag(s) across %d tenant(s), tick %ds, %s%s',
            $tags->count(),
            $tenants->count(),
            $interval,
            $cycles === 0 ? 'forever' : "{$cycles} cycle(s)",
            $dry ? '  [DRY RUN]' : ''
        ));
        $this->line('Press Ctrl+C to stop.');
        $this->newLine();

        $tick = 0;
        while ($cycles === 0 || $tick < $cycles) {
            $tick++;
            [$updates, $events] = $this->advanceOneTick($tags, $dry);
            $this->line(sprintf(
                '  [%s] tick %3d  values=%-3d  alarm-events=%d%s',
                now()->format('H:i:s'),
                $tick,
                $updates,
                $events,
                $dry ? '  (dry)' : ''
            ));

            if ($cycles !== 0 && $tick >= $cycles) break;
            sleep($interval);
        }

        $this->info("Done. {$tick} cycle(s).");
        return self::SUCCESS;
    }

    /**
     * Walk every tag forward by one tick. Returns [updatedCount, alarmEventCount].
     */
    protected function advanceOneTick($tags, bool $dry): array
    {
        $now = now();
        $currentRows = [];
        $historyRows = [];
        $alarmRows   = [];

        // Pre-load existing current-value rows in one query
        $existing = TagValueCurrent::whereIn('tag_id', $tags->pluck('id'))
            ->get()->keyBy('tag_id');

        foreach ($tags as $tag) {
            $prev = $existing->get($tag->id);
            $newValue = $this->nextValue($tag, $prev);

            $row = [
                'tenant_id'     => $tag->tenant_id,
                'tag_id'        => $tag->id,
                'value_numeric' => $newValue['numeric'],
                'value_boolean' => $newValue['boolean'],
                'value_string'  => $newValue['string'],
                'quality'       => 'good',
                'recorded_at'   => $now,
                'updated_at'    => $now,
            ];
            $currentRows[] = $row;

            // History has different schema (int quality, no timestamps,
            // requires site_id+device_id). Build a dedicated row.
            if ($tag->store_history && $newValue['numeric'] !== null) {
                $historyRows[] = [
                    'tenant_id'     => $tag->tenant_id,
                    'site_id'       => $tag->site_id,
                    'device_id'     => $tag->device_id,
                    'tag_id'        => $tag->id,
                    'value_numeric' => $newValue['numeric'],
                    'value_string'  => $newValue['string'],
                    'value_boolean' => $newValue['boolean'],
                    'quality'       => 1,
                    'recorded_at'   => $now,
                ];
            }

            // Threshold evaluation — numeric tags with thresholds only
            if ($newValue['numeric'] !== null) {
                $alarm = $this->checkThresholds($tag, $newValue['numeric'], $now);
                if ($alarm !== null) {
                    $alarmRows[] = $alarm;
                }
            }
        }

        if ($dry) {
            return [count($currentRows), count($alarmRows)];
        }

        DB::transaction(function () use ($currentRows, $historyRows, $alarmRows) {
            foreach ($currentRows as $row) {
                TagValueCurrent::updateOrCreate(
                    ['tag_id' => $row['tag_id']],
                    [
                        'tenant_id'     => $row['tenant_id'],
                        'value_numeric' => $row['value_numeric'],
                        'value_boolean' => $row['value_boolean'],
                        'value_string'  => $row['value_string'],
                        'quality'       => $row['quality'],
                        'recorded_at'   => $row['recorded_at'],
                        'updated_at'    => $row['updated_at'],
                    ]
                );
            }
            if (!empty($historyRows)) {
                foreach (array_chunk($historyRows, 500) as $chunk) {
                    TagValueHistory::insert($chunk);
                }
            }
            foreach ($alarmRows as $alarm) {
                AlarmEvent::create($alarm);
            }
        });

        return [count($currentRows), count($alarmRows)];
    }

    /**
     * Generate the next value for a tag based on its data_type.
     */
    protected function nextValue(Tag $tag, ?TagValueCurrent $prev): array
    {
        $out = ['numeric' => null, 'boolean' => null, 'string' => null];

        switch (strtolower($tag->data_type ?? 'float')) {
            case 'bool':
            case 'boolean':
                $prevBool = $prev?->value_boolean ?? false;
                $flip = mt_rand(1, 100) <= 3;
                $out['boolean'] = $flip ? !$prevBool : $prevBool;
                break;

            case 'string':
                $candidates = ['RUNNING', 'IDLE', 'STOPPED'];
                $out['string'] = $candidates[array_rand($candidates)];
                break;

            case 'int':
            case 'integer':
                $out['numeric'] = (float) $this->randomWalk($tag, $prev, integer: true);
                break;

            case 'float':
            case 'double':
            case 'real':
            default:
                $out['numeric'] = (float) $this->randomWalk($tag, $prev, integer: false);
                break;
        }

        return $out;
    }

    /**
     * Constrained random walk between min/max. ~60 ticks for a full sweep.
     */
    protected function randomWalk(Tag $tag, ?TagValueCurrent $prev, bool $integer): float|int
    {
        $min = $tag->min_value ?? 0;
        $max = $tag->max_value ?? ($min + 100);
        if ($max <= $min) $max = $min + 100;

        $prevVal = $prev?->value_numeric;
        if ($prevVal === null) {
            $val = $min + ($max - $min) * (0.25 + mt_rand(0, 50) / 100);
        } else {
            $step = ($max - $min) / 60;
            $mid = ($min + $max) / 2;
            $pull = ($mid - $prevVal) * 0.02;
            $jitter = (mt_rand(-100, 100) / 100) * $step;
            $val = $prevVal + $jitter + $pull;
            $val = max($min, min($max, $val));
        }

        return $integer ? (int) round($val) : round($val, 3);
    }

    /**
     * Returns array for AlarmEvent::create() if a NEW alarm should fire,
     * otherwise null. Suppresses duplicate active alarms for the same tag.
     */
    protected function checkThresholds(Tag $tag, float $value, $now): ?array
    {
        $severity = $tag->evaluateSeverity($value);
        if ($severity === null) {
            return null;
        }

        $exists = AlarmEvent::query()
            ->where('tag_id', $tag->id)
            ->whereIn('status', ['active', 'ack', 'shelved'])
            ->exists();
        if ($exists) {
            return null;
        }

        $threshold = match (true) {
            $tag->high_high !== null && $value >= $tag->high_high => $tag->high_high,
            $tag->low_low   !== null && $value <= $tag->low_low   => $tag->low_low,
            $tag->high      !== null && $value >= $tag->high      => $tag->high,
            $tag->low       !== null && $value <= $tag->low       => $tag->low,
            default => null,
        };

        $direction = ($tag->high !== null && $value >= $tag->high) ? 'HIGH' : 'LOW';
        $code = sprintf('%s.%s', $tag->code ?? "TAG{$tag->id}", $direction);

        return [
            'tenant_id'        => $tag->tenant_id,
            'site_id'          => $tag->site_id,
            'device_id'        => $tag->device_id,
            'tag_id'           => $tag->id,
            'code'             => $code,
            'message'          => sprintf('%s %s threshold breached: %s%s',
                                          $tag->name, $direction, $value, $tag->unit ?? ''),
            'severity'         => $severity,
            'status'           => 'active',
            'value_at_trigger' => $value,
            'threshold'        => $threshold,
            'triggered_at'     => $now,
        ];
    }

    /**
     * Resolve the --tenant flag into a Tenant collection.
     */
    protected function resolveTenants(?string $filter)
    {
        $q = Tenant::query();
        if ($filter !== null && $filter !== '') {
            if (is_numeric($filter)) {
                $q->where('id', (int) $filter);
            } else {
                $q->where('slug', $filter);
            }
        }
        return $q->get();
    }
}
