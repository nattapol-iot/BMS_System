<?php
namespace App\Modules\SCADA\Console;

use App\Modules\SCADA\Models\AlarmEvent;
use App\Modules\SCADA\Models\AuditLog;
use App\Modules\SCADA\Models\TagValueHistory;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * scada:purge-history
 * -------------------
 * Drops old time-series rows to control disk usage. Default retention is
 * 90 days for tag history, 365 days for alarm events and audit log.
 *
 * Schedule once a day via routes/console.php.
 */
class PurgeHistoryCommand extends Command
{
    protected $signature = 'scada:purge-history
        {--days=90 : Keep last N days of tag-value history}
        {--alarm-days=365 : Keep last N days of cleared alarm events}
        {--audit-days=365 : Keep last N days of audit log}
        {--tenant= : Tenant ID or slug (default: all)}
        {--dry-run : Count rows that would be deleted, do not delete}';

    protected $description = 'Purge old SCADA time-series + alarm + audit history beyond retention';

    public function handle(): int
    {
        $tagDays   = max(1, (int) $this->option('days'));
        $alarmDays = max(1, (int) $this->option('alarm-days'));
        $auditDays = max(1, (int) $this->option('audit-days'));
        $tenant    = $this->option('tenant');
        $dry       = (bool) $this->option('dry-run');

        $tagCutoff   = Carbon::now()->subDays($tagDays);
        $alarmCutoff = Carbon::now()->subDays($alarmDays);
        $auditCutoff = Carbon::now()->subDays($auditDays);

        $this->info(sprintf(
            'SCADA purge: tag-history > %dd, cleared-alarms > %dd, audit > %dd%s',
            $tagDays, $alarmDays, $auditDays,
            $dry ? '  [DRY RUN]' : ''
        ));

        $tagQ = TagValueHistory::query()->where('recorded_at', '<', $tagCutoff);
        $alarmQ = AlarmEvent::query()
            ->where('status', 'cleared')
            ->where('cleared_at', '<', $alarmCutoff);
        $auditQ = AuditLog::query()->where('performed_at', '<', $auditCutoff);

        if ($tenant !== null && $tenant !== '') {
            $tenantId = $this->resolveTenantId($tenant);
            if ($tenantId === null) {
                $this->error("Tenant not found: {$tenant}");
                return self::FAILURE;
            }
            $tagQ->where('tenant_id', $tenantId);
            $alarmQ->where('tenant_id', $tenantId);
            $auditQ->where('tenant_id', $tenantId);
            $this->line("Filter: tenant_id = {$tenantId}");
        }

        $tagCount   = (int) $tagQ->count();
        $alarmCount = (int) $alarmQ->count();
        $auditCount = (int) $auditQ->count();

        $this->table(
            ['Table', 'Cutoff', 'Rows'],
            [
                ['scada_tag_values_history', $tagCutoff->toDateTimeString(), number_format($tagCount)],
                ['scada_alarm_events (cleared)', $alarmCutoff->toDateTimeString(), number_format($alarmCount)],
                ['scada_audit_logs', $auditCutoff->toDateTimeString(), number_format($auditCount)],
            ]
        );

        if ($dry) {
            $this->info("Dry-run: would delete " . ($tagCount + $alarmCount + $auditCount) . " row(s).");
            return self::SUCCESS;
        }

        $deletedTag = $this->deleteInBatches($tagQ);
        $deletedAlarm = $alarmQ->delete();
        $deletedAudit = $auditQ->delete();

        $this->info(sprintf(
            'Deleted: tag-history=%s, cleared-alarms=%s, audit=%s',
            number_format($deletedTag),
            number_format($deletedAlarm),
            number_format($deletedAudit)
        ));

        return self::SUCCESS;
    }

    /**
     * Delete in 10k-row chunks to keep transaction size sane on large tables.
     */
    protected function deleteInBatches($query): int
    {
        $total = 0;
        while (true) {
            $deleted = (clone $query)->limit(10000)->delete();
            if ($deleted === 0) break;
            $total += $deleted;
        }
        return $total;
    }

    protected function resolveTenantId(string $filter): ?int
    {
        if (is_numeric($filter)) return (int) $filter;
        $row = DB::table('tenants')->where('slug', $filter)->first();
        return $row?->id;
    }
}
