@extends('layouts.app')

@section('title', __('menu.reports') ?? 'Reports')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="alert mb-4 d-flex align-items-center gap-2"
         style="background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.3);color:#6ee7b7;border-radius:8px;">
        <i class="fa-solid fa-circle-check"></i>
        {{ session('success') }}
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Section Header --}}
    <div class="section-header d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0 fw-bold text-white">
                <i class="fa-solid fa-file-chart-column me-2" style="color:var(--nx-cyan)"></i>
                {{ __('menu.reports') ?? 'Reports' }}
            </h4>
            <small class="text-muted">{{ __('menu.reports_sub') ?? 'Generate and manage system reports' }}</small>
        </div>
        <button type="button" class="nx-btn nx-btn-primary" data-bs-toggle="modal" data-bs-target="#generateReportModal">
            <i class="fa-solid fa-file-plus me-1"></i>
            {{ __('menu.generate_report') ?? 'Generate Report' }}
        </button>
    </div>

    {{-- Summary Cards --}}
    @php
        $totalReports   = $reports->total();
        $thisMonth      = $reports->getCollection()->filter(fn($r) => $r->created_at->isCurrentMonth())->count();
        $pdfCount       = $reports->getCollection()->where('format', 'pdf')->count();
        $excelCsvCount  = $reports->getCollection()->whereIn('format', ['excel', 'csv'])->count();
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(29,78,216,0.15)">
                    <i class="fa-solid fa-file-lines" style="color:#1d4ed8"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.total_reports') ?? 'Total Reports' }}</div>
                    <div class="stat-value">{{ $totalReports }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(6,182,212,0.15)">
                    <i class="fa-solid fa-calendar-month" style="color:#06b6d4"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.this_month') ?? 'This Month' }}</div>
                    <div class="stat-value">{{ $thisMonth }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(239,68,68,0.15)">
                    <i class="fa-solid fa-file-pdf" style="color:#ef4444"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">PDF {{ __('menu.reports') ?? 'Reports' }}</div>
                    <div class="stat-value">{{ $pdfCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.15)">
                    <i class="fa-solid fa-file-excel" style="color:#10b981"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">Excel / CSV</div>
                    <div class="stat-value">{{ $excelCsvCount }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reports Table --}}
    <div class="nx-card">
        <div class="nx-card-header d-flex align-items-center justify-content-between">
            <span>
                <i class="fa-solid fa-table-list me-2" style="color:var(--nx-cyan)"></i>
                {{ __('menu.report_history') ?? 'Report History' }}
            </span>
            <span class="text-muted small">{{ $reports->total() }} {{ __('menu.records') ?? 'records' }}</span>
        </div>
        <div class="nx-card-body p-0">
            @if($reports->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-file-circle-xmark fa-2x mb-3 d-block opacity-40"></i>
                    <p>{{ __('menu.no_reports') ?? 'No reports generated yet.' }}</p>
                    <button type="button" class="nx-btn nx-btn-primary" data-bs-toggle="modal" data-bs-target="#generateReportModal">
                        <i class="fa-solid fa-plus me-1"></i>
                        {{ __('menu.generate_first_report') ?? 'Generate First Report' }}
                    </button>
                </div>
            @else
            <div class="table-responsive">
                <table class="nx-table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('menu.report_name') ?? 'Report Name' }}</th>
                            <th>{{ __('menu.type') ?? 'Type' }}</th>
                            <th>{{ __('menu.building') ?? 'Building' }}</th>
                            <th>{{ __('menu.date_range') ?? 'Date Range' }}</th>
                            <th>{{ __('menu.format') ?? 'Format' }}</th>
                            <th class="text-center">{{ __('menu.status') ?? 'Status' }}</th>
                            <th class="text-center">{{ __('menu.download') ?? 'Download' }}</th>
                            <th>{{ __('menu.created_by') ?? 'Created By' }}</th>
                            <th>{{ __('menu.created_at') ?? 'Created At' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reports as $report)
                        @php
                            $statusConfig = [
                                'pending'    => ['bg'=>'rgba(245,158,11,0.15)','color'=>'#f59e0b','icon'=>'fa-clock','label'=>'Pending'],
                                'generating' => ['bg'=>'rgba(29,78,216,0.15)','color'=>'#1d4ed8','icon'=>'fa-spinner fa-spin','label'=>'Generating'],
                                'completed'  => ['bg'=>'rgba(16,185,129,0.15)','color'=>'#10b981','icon'=>'fa-circle-check','label'=>'Completed'],
                                'failed'     => ['bg'=>'rgba(239,68,68,0.15)','color'=>'#ef4444','icon'=>'fa-circle-xmark','label'=>'Failed'],
                            ];
                            $sc = $statusConfig[$report->status] ?? $statusConfig['pending'];

                            $formatConfig = [
                                'pdf'   => ['bg'=>'rgba(239,68,68,0.15)','color'=>'#ef4444','icon'=>'fa-file-pdf'],
                                'excel' => ['bg'=>'rgba(16,185,129,0.15)','color'=>'#10b981','icon'=>'fa-file-excel'],
                                'csv'   => ['bg'=>'rgba(107,114,128,0.15)','color'=>'#9ca3af','icon'=>'fa-file-csv'],
                            ];
                            $fc = $formatConfig[strtolower($report->format)] ?? $formatConfig['csv'];

                            $typeLabels = [
                                'energy_summary'   => 'Energy Summary',
                                'equipment_status' => 'Equipment Status',
                                'alarm_log'        => 'Alarm Log',
                                'user_activity'    => 'User Activity',
                            ];
                        @endphp
                        <tr>
                            <td class="fw-semibold text-white">{{ $report->name }}</td>
                            <td class="text-muted small">{{ $typeLabels[$report->report_type] ?? ucwords(str_replace('_',' ',$report->report_type)) }}</td>
                            <td class="text-muted small">{{ optional($report->building)->name ?? __('menu.all_buildings') ?? 'All' }}</td>
                            <td class="text-muted small">
                                @if($report->start_date && $report->end_date)
                                    {{ \Carbon\Carbon::parse($report->start_date)->format('d M Y') }}
                                    –
                                    {{ \Carbon\Carbon::parse($report->end_date)->format('d M Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="nx-badge" style="background:{{ $fc['bg'] }};color:{{ $fc['color'] }}">
                                    <i class="fa-solid {{ $fc['icon'] }} me-1"></i>
                                    {{ strtoupper($report->format) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="nx-badge" style="background:{{ $sc['bg'] }};color:{{ $sc['color'] }}">
                                    <i class="fa-solid {{ $sc['icon'] }} me-1"></i>
                                    {{ $sc['label'] }}
                                </span>
                            </td>
                            <td class="text-center">
                                @if($report->status === 'completed' && $report->file_path)
                                    <a href="{{ route('reports.download', $report->id) }}"
                                       class="nx-btn nx-btn-outline" style="padding:4px 12px;font-size:.78rem;">
                                        <i class="fa-solid fa-download me-1"></i>
                                        {{ __('menu.download') ?? 'Download' }}
                                    </a>
                                @else
                                    <span class="text-muted small">—</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ optional($report->user)->name ?? '—' }}</td>
                            <td class="text-muted small">{{ $report->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($reports->hasPages())
            <div class="d-flex justify-content-end p-3">
                {{ $reports->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>

</div>

{{-- Generate Report Modal --}}
<div class="modal fade" id="generateReportModal" tabindex="-1" aria-labelledby="generateReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background:#0f1e38;border:1px solid rgba(255,255,255,0.1);">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,0.08);">
                <h5 class="modal-title text-white" id="generateReportModalLabel">
                    <i class="fa-solid fa-file-plus me-2" style="color:var(--nx-cyan)"></i>
                    {{ __('menu.generate_report') ?? 'Generate Report' }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('reports.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.report_name') ?? 'Report Name' }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control"
                                   placeholder="{{ __('menu.report_name_ph') ?? 'e.g. Monthly Energy Report - May 2026' }}"
                                   style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.report_type') ?? 'Report Type' }} <span class="text-danger">*</span>
                            </label>
                            <select name="report_type" class="form-select"
                                    style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;" required>
                                <option value="">— {{ __('menu.select_type') ?? 'Select Type' }} —</option>
                                <option value="energy_summary">Energy Summary</option>
                                <option value="equipment_status">Equipment Status</option>
                                <option value="alarm_log">Alarm Log</option>
                                <option value="user_activity">User Activity</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.building') ?? 'Building' }}
                            </label>
                            <select name="building_id" class="form-select"
                                    style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                                <option value="">— {{ __('menu.all_buildings') ?? 'All Buildings' }} —</option>
                                @foreach($buildings as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.start_date') ?? 'Start Date' }} <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="start_date" class="form-control"
                                   style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;"
                                   value="{{ now()->startOfMonth()->format('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.end_date') ?? 'End Date' }} <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="end_date" class="form-control"
                                   style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;"
                                   value="{{ now()->format('Y-m-d') }}" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.format') ?? 'Format' }} <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex gap-3">
                                @foreach(['pdf'=>['fa-file-pdf','#ef4444','PDF'],'excel'=>['fa-file-excel','#10b981','Excel'],'csv'=>['fa-file-csv','#9ca3af','CSV']] as $val=>$cfg)
                                <label class="d-flex align-items-center gap-2" style="cursor:pointer;">
                                    <input type="radio" name="format" value="{{ $val }}"
                                           {{ $val==='pdf' ? 'checked' : '' }}
                                           class="form-check-input" style="margin-top:0;">
                                    <span style="color:{{ $cfg[1] }};">
                                        <i class="fa-solid {{ $cfg[0] }} me-1"></i>{{ $cfg[2] }}
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,0.08);">
                    <button type="button" class="nx-btn nx-btn-outline" data-bs-dismiss="modal">
                        {{ __('menu.cancel') ?? 'Cancel' }}
                    </button>
                    <button type="submit" class="nx-btn nx-btn-primary">
                        <i class="fa-solid fa-play me-1"></i>
                        {{ __('menu.generate') ?? 'Generate' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
