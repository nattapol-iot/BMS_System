@extends('layouts.app')

@section('title', __('menu.activity_logs') ?? 'Activity Logs')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Section Header --}}
    <div class="section-header d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0 fw-bold text-white">
                <i class="fa-solid fa-scroll me-2" style="color:var(--nx-cyan)"></i>
                {{ __('menu.activity_logs') ?? 'Activity Logs' }}
            </h4>
            <small class="text-muted">{{ __('menu.logs_sub') ?? 'Complete audit trail of all system activities' }}</small>
        </div>
    </div>

    {{-- Stat Cards --}}
    @php
        $allLogs = $logs->getCollection();
        $todayTotal   = $allLogs->count();
        $uniqueUsers  = $allLogs->pluck('user_id')->unique()->filter()->count();
        $actionCounts = $allLogs->groupBy('action')->map->count()->sortDesc();
        $mostCommon   = $actionCounts->keys()->first() ?? '—';
        $mostCommonCount = $actionCounts->first() ?? 0;
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(29,78,216,0.15)">
                    <i class="fa-solid fa-list" style="color:#1d4ed8"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.logs_today') ?? 'Logs Today' }}</div>
                    <div class="stat-value">{{ number_format($logs->total()) }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.15)">
                    <i class="fa-solid fa-users" style="color:#10b981"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.unique_users_today') ?? 'Unique Users Today' }}</div>
                    <div class="stat-value">{{ $uniqueUsers }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.15)">
                    <i class="fa-solid fa-chart-simple" style="color:#f59e0b"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.most_common_action') ?? 'Most Common Action' }}</div>
                    <div class="stat-value" style="font-size:1.1rem;text-transform:capitalize;">{{ $mostCommon }}</div>
                    <div class="stat-unit text-muted small">{{ $mostCommonCount }} {{ __('menu.times') ?? 'times' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar mb-3">
        <form method="GET" action="{{ route('logs.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
            <div class="input-group" style="max-width:260px;">
                <span class="input-group-text" style="background:#1a2a4a;border-color:#2d4a7a;">
                    <i class="fa-solid fa-search text-muted"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control" placeholder="{{ __('menu.search_user') ?? 'Search user name/email...' }}"
                       style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
            </div>

            <select name="action" class="form-select" style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;max-width:160px;">
                <option value="">{{ __('menu.all_actions') ?? 'All Actions' }}</option>
                @foreach($actions as $action)
                    <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                        {{ ucfirst($action) }}
                    </option>
                @endforeach
            </select>

            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="form-control" style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;max-width:160px;"
                   title="{{ __('menu.date_from') ?? 'From Date' }}">

            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="form-control" style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;max-width:160px;"
                   title="{{ __('menu.date_to') ?? 'To Date' }}">

            <button type="submit" class="nx-btn nx-btn-outline">
                <i class="fa-solid fa-filter me-1"></i>
                {{ __('menu.filter') ?? 'Filter' }}
            </button>

            @if(request()->hasAny(['search','action','date_from','date_to']))
            <a href="{{ route('logs.index') }}" class="nx-btn"
               style="background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.3);">
                <i class="fa-solid fa-xmark me-1"></i>
                {{ __('menu.clear') ?? 'Clear' }}
            </a>
            @endif

            <div class="ms-auto">
                <a href="{{ route('logs.index', array_merge(request()->query(), ['export' => 1])) }}"
                   class="nx-btn nx-btn-outline">
                    <i class="fa-solid fa-file-csv me-1" style="color:#10b981"></i>
                    {{ __('menu.export_csv') ?? 'Export CSV' }}
                </a>
            </div>
        </form>
    </div>

    {{-- Logs Table --}}
    <div class="nx-card">
        <div class="nx-card-header d-flex align-items-center justify-content-between">
            <span>
                <i class="fa-solid fa-table-list me-2" style="color:var(--nx-cyan)"></i>
                {{ __('menu.audit_trail') ?? 'Audit Trail' }}
            </span>
            <span class="text-muted small">{{ number_format($logs->total()) }} {{ __('menu.records') ?? 'records' }}</span>
        </div>
        <div class="nx-card-body p-0">
            @if($logs->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-magnifying-glass fa-2x mb-3 d-block opacity-40"></i>
                    <p>{{ __('menu.no_logs') ?? 'No activity logs found for the selected filters.' }}</p>
                    @if(request()->hasAny(['search','action','date_from','date_to']))
                        <a href="{{ route('logs.index') }}" class="nx-btn nx-btn-outline">
                            {{ __('menu.clear_filters') ?? 'Clear Filters' }}
                        </a>
                    @endif
                </div>
            @else
            <div class="table-responsive">
                <table class="nx-table w-100">
                    <thead>
                        <tr>
                            <th style="width:50px">#</th>
                            <th>{{ __('menu.user') ?? 'User' }}</th>
                            <th>{{ __('menu.action') ?? 'Action' }}</th>
                            <th>{{ __('menu.url') ?? 'URL' }}</th>
                            <th>{{ __('menu.ip_address') ?? 'IP Address' }}</th>
                            <th>{{ __('menu.timestamp') ?? 'Timestamp' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                        @php
                            $actionStyles = [
                                'login'   => ['bg'=>'rgba(16,185,129,0.15)','color'=>'#10b981','icon'=>'fa-right-to-bracket'],
                                'logout'  => ['bg'=>'rgba(107,114,128,0.15)','color'=>'#9ca3af','icon'=>'fa-right-from-bracket'],
                                'create'  => ['bg'=>'rgba(29,78,216,0.15)','color'=>'#1d4ed8','icon'=>'fa-plus-circle'],
                                'update'  => ['bg'=>'rgba(245,158,11,0.15)','color'=>'#f59e0b','icon'=>'fa-pen-to-square'],
                                'delete'  => ['bg'=>'rgba(239,68,68,0.15)','color'=>'#ef4444','icon'=>'fa-trash'],
                                'view'    => ['bg'=>'rgba(255,255,255,0.06)','color'=>'#d1d5db','icon'=>'fa-eye'],
                                'export'  => ['bg'=>'rgba(6,182,212,0.15)','color'=>'#06b6d4','icon'=>'fa-file-arrow-down'],
                            ];
                            $as = $actionStyles[strtolower($log->action)] ?? ['bg'=>'rgba(255,255,255,0.06)','color'=>'#9ca3af','icon'=>'fa-circle-info'];
                        @endphp
                        <tr>
                            <td class="text-muted small">{{ $log->id }}</td>
                            <td>
                                <div class="fw-semibold text-white" style="font-size:.875rem;">
                                    {{ optional($log->user)->name ?? 'System' }}
                                </div>
                                <div class="text-muted" style="font-size:.72rem;">
                                    {{ optional($log->user)->email ?? '—' }}
                                </div>
                            </td>
                            <td>
                                <span class="nx-badge" style="background:{{ $as['bg'] }};color:{{ $as['color'] }}">
                                    <i class="fa-solid {{ $as['icon'] }} me-1"></i>
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td class="text-muted small" style="max-width:300px;">
                                <span title="{{ $log->url }}" style="display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:280px;">
                                    {{ Str::limit($log->url ?? '—', 50) }}
                                </span>
                            </td>
                            <td>
                                <span class="text-muted small font-monospace">{{ $log->ip_address ?? '—' }}</span>
                            </td>
                            <td class="text-muted small" style="white-space:nowrap;">
                                {{ $log->created_at->format('d M Y H:i:s') }}
                                <div style="font-size:.7rem;color:#6b7280;">
                                    {{ $log->created_at->diffForHumans() }}
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
            <div class="d-flex justify-content-end p-3">
                {{ $logs->appends(request()->query())->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>

</div>
@endsection
