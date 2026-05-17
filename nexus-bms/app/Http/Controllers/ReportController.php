<?php
namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\Building;
use App\Models\Equipment;
use App\Models\Alarm;
use App\Models\EnergyLog;
use App\Models\EnergyMeter;
use App\Core\AuditLog\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with('generator')->orderByDesc('created_at')->paginate(15);
        $buildings = Building::where('status','active')->get();
        return view('reports.index', compact('reports','buildings'));
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'type' => 'required|in:status,energy,alarm,maintenance',
            'format' => 'required|in:pdf,excel',
            'building_id' => 'nullable|exists:buildings,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $report = Report::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'format' => $data['format'],
            'parameters' => [
                'building_id' => $data['building_id'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
            ],
            'generated_by' => auth()->id(),
            'status' => 'generating',
        ]);

        try {
            $rows = $this->collectRows($report);
            $relativePath = $data['format'] === 'pdf'
                ? $this->writeHtmlReport($report, $rows)
                : $this->writeCsvReport($report, $rows);

            $report->update([
                'status' => 'completed',
                'file_path' => $relativePath,
            ]);

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'module' => 'reports',
                'description' => "Generated report: {$report->name}",
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->route('reports.index')->with('success', "Report generated: {$report->name}");
        } catch (\Throwable $e) {
            $report->update(['status' => 'failed']);
            return redirect()->route('reports.index')->with('error', 'Report generation failed: ' . $e->getMessage());
        }
    }

    public function download(Report $report)
    {
        if ($report->status !== 'completed' || !$report->file_path) {
            abort(404);
        }
        $disk = Storage::disk('local');
        if (!$disk->exists($report->file_path)) {
            abort(404);
        }
        $extension = $report->format === 'pdf' ? 'html' : 'csv';
        $downloadName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $report->name) . '.' . $extension;
        return response()->download($disk->path($report->file_path), $downloadName);
    }

    public function destroy(Report $report)
    {
        if ($report->file_path && Storage::disk('local')->exists($report->file_path)) {
            Storage::disk('local')->delete($report->file_path);
        }
        $report->delete();
        return back()->with('success', 'Report deleted.');
    }

    private function collectRows(Report $report): array
    {
        $params = $report->parameters ?? [];
        $buildingId = $params['building_id'] ?? null;
        $start = !empty($params['start_date']) ? Carbon::parse($params['start_date'])->startOfDay() : Carbon::now()->subDays(30);
        $end = !empty($params['end_date']) ? Carbon::parse($params['end_date'])->endOfDay() : Carbon::now();

        switch ($report->type) {
            case 'status':
                $query = Equipment::with(['category','building','floor'])->orderBy('code');
                if ($buildingId) $query->where('building_id', $buildingId);
                $items = $query->get();
                return [
                    'title' => 'Equipment Status Report',
                    'headers' => ['Code', 'Name', 'Category', 'Building', 'Floor', 'Status', 'Health', 'Runtime (h)'],
                    'rows' => $items->map(fn($e) => [
                        $e->code,
                        $e->name,
                        $e->category?->name ?? '-',
                        $e->building?->name ?? '-',
                        $e->floor?->name ?? '-',
                        $e->status,
                        $e->health_score . '%',
                        number_format((float) $e->runtime_hours, 1),
                    ])->all(),
                ];

            case 'alarm':
                $query = Alarm::with(['equipment','building','assignee'])
                    ->whereBetween('triggered_at', [$start, $end])
                    ->orderByDesc('triggered_at');
                if ($buildingId) $query->where('building_id', $buildingId);
                $items = $query->get();
                return [
                    'title' => 'Alarm Log Report',
                    'headers' => ['Code', 'Severity', 'Status', 'Description', 'Equipment', 'Building', 'Triggered', 'Assigned To'],
                    'rows' => $items->map(fn($a) => [
                        $a->code ?? '-',
                        $a->severity,
                        $a->status,
                        $a->description ?? '-',
                        $a->equipment?->name ?? '-',
                        $a->building?->name ?? '-',
                        optional($a->triggered_at)->format('Y-m-d H:i'),
                        $a->assignee?->name ?? '-',
                    ])->all(),
                ];

            case 'energy':
                $meterQuery = EnergyMeter::with(['building','floor']);
                if ($buildingId) $meterQuery->where('building_id', $buildingId);
                $meters = $meterQuery->get();
                $logs = EnergyLog::whereIn('meter_id', $meters->pluck('id'))
                    ->whereBetween('logged_at', [$start, $end])
                    ->get()
                    ->groupBy('meter_id');
                return [
                    'title' => 'Energy Summary Report (' . $start->format('Y-m-d') . ' to ' . $end->format('Y-m-d') . ')',
                    'headers' => ['Meter', 'Type', 'Building', 'Floor', 'Total Consumption', 'Unit', 'Reading Count'],
                    'rows' => $meters->map(function ($m) use ($logs) {
                        $meterLogs = $logs->get($m->id, collect());
                        return [
                            $m->name,
                            $m->type,
                            $m->building?->name ?? '-',
                            $m->floor?->name ?? '-',
                            number_format((float) $meterLogs->sum('value'), 2),
                            $m->type === 'water' ? 'm³' : 'kWh',
                            $meterLogs->count(),
                        ];
                    })->all(),
                ];

            case 'maintenance':
                $query = Equipment::with(['category','building'])
                    ->whereIn('status', ['maintenance', 'offline'])
                    ->orWhere('health_score', '<', 70)
                    ->orderBy('health_score');
                if ($buildingId) $query->where('building_id', $buildingId);
                $items = $query->get();
                return [
                    'title' => 'Maintenance Report',
                    'headers' => ['Code', 'Name', 'Category', 'Building', 'Status', 'Health', 'Last Communication'],
                    'rows' => $items->map(fn($e) => [
                        $e->code,
                        $e->name,
                        $e->category?->name ?? '-',
                        $e->building?->name ?? '-',
                        $e->status,
                        $e->health_score . '%',
                        optional($e->last_communication)->format('Y-m-d H:i') ?? '-',
                    ])->all(),
                ];
        }

        return ['title' => 'Empty', 'headers' => [], 'rows' => []];
    }

    private function writeCsvReport(Report $report, array $data): string
    {
        $disk = Storage::disk('local');
        $dir = 'reports';
        if (!$disk->exists($dir)) $disk->makeDirectory($dir);
        $filename = 'report_' . $report->id . '_' . now()->format('Ymd_His') . '.csv';
        $relative = $dir . '/' . $filename;
        $absolute = $disk->path($relative);

        $fp = fopen($absolute, 'w');
        // UTF-8 BOM so Excel renders Thai correctly
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, [$data['title']]);
        fputcsv($fp, ['Generated', now()->format('Y-m-d H:i:s')]);
        fputcsv($fp, []);
        fputcsv($fp, $data['headers']);
        foreach ($data['rows'] as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
        return $relative;
    }

    private function writeHtmlReport(Report $report, array $data): string
    {
        $disk = Storage::disk('local');
        $dir = 'reports';
        if (!$disk->exists($dir)) $disk->makeDirectory($dir);
        $filename = 'report_' . $report->id . '_' . now()->format('Ymd_His') . '.html';
        $relative = $dir . '/' . $filename;
        $absolute = $disk->path($relative);

        $rowsHtml = '';
        foreach ($data['rows'] as $row) {
            $rowsHtml .= '<tr>';
            foreach ($row as $cell) {
                $rowsHtml .= '<td>' . htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $rowsHtml .= '</tr>';
        }
        $headersHtml = '';
        foreach ($data['headers'] as $h) {
            $headersHtml .= '<th>' . htmlspecialchars($h, ENT_QUOTES, 'UTF-8') . '</th>';
        }

        $generated = now()->format('Y-m-d H:i:s');
        $title = htmlspecialchars($data['title'], ENT_QUOTES, 'UTF-8');
        $reportName = htmlspecialchars($report->name, ENT_QUOTES, 'UTF-8');
        $rowCount = count($data['rows']);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>{$reportName} — Nexus BMS</title>
<style>
* { box-sizing: border-box; }
body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; margin: 32px; }
.brand { font-size: 12px; color: #1d4ed8; font-weight: 600; letter-spacing: 1px; }
h1 { font-size: 22px; margin: 4px 0 6px; color: #0d1b34; }
.meta { color: #6b7280; font-size: 12px; margin-bottom: 24px; }
table { width: 100%; border-collapse: collapse; font-size: 12px; }
thead { background: #0d1b34; color: #fff; }
th, td { padding: 8px 10px; border: 1px solid #e5e7eb; text-align: left; }
tbody tr:nth-child(even) { background: #f9fafb; }
.footer { margin-top: 24px; font-size: 11px; color: #9ca3af; text-align: center; }
@media print { body { margin: 16px; } .no-print { display: none; } }
.print-bar { background: #1d4ed8; color: #fff; padding: 8px 12px; border-radius: 4px; display: inline-block; cursor: pointer; text-decoration: none; font-size: 12px; }
</style>
</head>
<body>
<div class="brand">NEXUS BMS PLATFORM</div>
<h1>{$title}</h1>
<div class="meta">Report: {$reportName} · Generated: {$generated} · Rows: {$rowCount}</div>
<div class="no-print" style="margin-bottom:16px;"><a class="print-bar" onclick="window.print()" href="#">Print / Save as PDF</a></div>
<table>
<thead><tr>{$headersHtml}</tr></thead>
<tbody>{$rowsHtml}</tbody>
</table>
<div class="footer">Generated by Nexus BMS Platform · © Nexus BMS</div>
</body>
</html>
HTML;

        file_put_contents($absolute, $html);
        return $relative;
    }
}
