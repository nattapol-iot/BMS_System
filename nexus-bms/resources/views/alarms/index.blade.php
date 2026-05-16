@extends('layouts.app')
@section('title', 'Alarms & Events')
@section('page-title', 'Alarms & Events / สัญญาณเตือนและเหตุการณ์')
@section('page-subtitle', 'Monitor, acknowledge, and manage all system alarms')

@section('content')
<div class="fade-in">

<!-- STATS ROW -->
<div class="row g-3 mb-4">
    <div class="col-md col-sm-6">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-bell"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ $totalAlerts }}</div>
                <div class="stat-label">Total Alerts</div>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="stat-card" style="border-left:4px solid var(--danger);">
            <div class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-content">
                <div class="stat-value" style="color:var(--danger)">{{ $criticalCount }}</div>
                <div class="stat-label">Critical</div>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="stat-card" style="border-left:4px solid var(--warning);">
            <div class="stat-icon orange"><i class="fa-solid fa-exclamation-circle"></i></div>
            <div class="stat-content">
                <div class="stat-value" style="color:var(--warning)">{{ $warningCount }}</div>
                <div class="stat-label">Warning</div>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="stat-card">
            <div class="stat-icon slate"><i class="fa-solid fa-eye"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ $acknowledgedCount }}</div>
                <div class="stat-label">Acknowledged</div>
            </div>
        </div>
    </div>
    <div class="col-md col-sm-6">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-content">
                <div class="stat-value" style="color:var(--success)">{{ $resolvedToday }}</div>
                <div class="stat-label">Resolved Today</div>
            </div>
        </div>
    </div>
</div>

<!-- FILTER BAR -->
<div class="filter-bar mb-3">
    <form method="GET" action="{{ route('alarms.index') }}" class="d-flex align-items-center gap-3 flex-wrap w-100">
        <div style="position:relative;flex:1;min-width:200px;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px;"></i>
            <input type="text" name="search" class="nx-input" style="padding-left:32px;" placeholder="Search alarms..." value="{{ request('search') }}">
        </div>
        <select name="severity" class="nx-select" style="width:130px;">
            <option value="">All Severity</option>
            <option value="critical" {{ request('severity')==='critical'?'selected':'' }}>Critical</option>
            <option value="warning" {{ request('severity')==='warning'?'selected':'' }}>Warning</option>
            <option value="info" {{ request('severity')==='info'?'selected':'' }}>Info</option>
        </select>
        <select name="status" class="nx-select" style="width:140px;">
            <option value="">All Status</option>
            <option value="active" {{ request('status')==='active'?'selected':'' }}>Active</option>
            <option value="acknowledged" {{ request('status')==='acknowledged'?'selected':'' }}>Acknowledged</option>
            <option value="silenced" {{ request('status')==='silenced'?'selected':'' }}>Silenced</option>
            <option value="resolved" {{ request('status')==='resolved'?'selected':'' }}>Resolved</option>
        </select>
        <select name="building_id" class="nx-select" style="width:160px;">
            <option value="">All Buildings</option>
            @foreach($buildings as $b)
            <option value="{{ $b->id }}" {{ request('building_id')==$b->id?'selected':'' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="nx-btn nx-btn-primary nx-btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
        <a href="{{ route('alarms.index') }}" class="nx-btn nx-btn-outline nx-btn-sm">Reset</a>
    </form>
</div>

<div class="row g-3">
    <!-- ALARM TABLE -->
    <div class="{{ $selectedAlarm ? 'col-xl-7' : 'col-12' }}">
        <div class="nx-card">
            <div class="nx-card-header">
                <div class="nx-card-title"><i class="fa-solid fa-list" style="color:var(--danger);margin-right:6px;"></i>Active Alarms</div>
                <span style="font-size:12px;color:var(--text-muted);">{{ $alarms->total() }} alarms</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="nx-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Alarm ID</th>
                            <th>Source</th>
                            <th>Building/Floor</th>
                            <th>Severity</th>
                            <th>Description</th>
                            <th>Assigned To</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alarms as $alarm)
                        <tr class="{{ $selectedAlarm && $selectedAlarm->id === $alarm->id ? 'table-active' : '' }}" style="cursor:pointer;" onclick="window.location='{{ route('alarms.index', array_merge(request()->all(), ['detail'=>$alarm->id])) }}'">
                            <td style="white-space:nowrap;font-size:11px;color:var(--text-muted);">
                                <div>{{ $alarm->triggered_at?->format('d/m/Y') }}</div>
                                <div style="font-weight:600;color:var(--text);">{{ $alarm->triggered_at?->format('H:i:s') }}</div>
                            </td>
                            <td><span style="font-family:monospace;font-size:11px;color:var(--primary);font-weight:600;">{{ $alarm->code }}</span></td>
                            <td style="font-size:12px;font-weight:600;">{{ $alarm->equipment?->name ?? 'System' }}</td>
                            <td style="font-size:12px;">
                                <div>{{ $alarm->building?->name ?? '-' }}</div>
                                <div style="font-size:11px;color:var(--text-muted);">{{ $alarm->floor ? 'Floor '.$alarm->floor->floor_number : '' }}</div>
                            </td>
                            <td>
                                <span class="nx-badge {{ $alarm->severity_badge_class }}">
                                    <i class="fa-solid fa-circle" style="font-size:6px;{{ $alarm->severity==='critical' && $alarm->status==='active' ? 'animation:pulse 1s infinite;' : '' }}"></i>
                                    {{ ucfirst($alarm->severity) }}
                                </span>
                            </td>
                            <td style="max-width:200px;">
                                <div style="font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;" title="{{ $alarm->description }}">
                                    {{ $alarm->description }}
                                </div>
                            </td>
                            <td style="font-size:12px;">{!! $alarm->assignee?->name ?? '<span style="color:var(--text-muted)">Unassigned</span>' !!}</td>
                            <td>
                                <span class="nx-badge {{ $alarm->status_badge_class }}">{{ ucfirst($alarm->status) }}</span>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <div class="d-flex gap-1">
                                    @if($alarm->status === 'active')
                                    <button class="nx-btn nx-btn-warning nx-btn-sm" onclick="acknowledgeAlarm({{ $alarm->id }})" title="Acknowledge">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                    @endif
                                    @if(in_array($alarm->status, ['active','acknowledged']))
                                    <button class="nx-btn nx-btn-success nx-btn-sm" onclick="resolveAlarm({{ $alarm->id }})" title="Resolve">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">
                            <i class="fa-solid fa-circle-check" style="font-size:32px;color:var(--success);margin-bottom:8px;display:block;"></i>
                            No alarms found
                        </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="nx-card-footer d-flex justify-content-between align-items-center">
                <span style="font-size:12px;color:var(--text-muted);">{{ $alarms->firstItem() }}-{{ $alarms->lastItem() }} of {{ $alarms->total() }}</span>
                {{ $alarms->appends(request()->all())->links() }}
            </div>
        </div>

        <!-- Recent Events Timeline -->
        <div class="nx-card mt-3">
            <div class="nx-card-header">
                <div class="nx-card-title"><i class="fa-solid fa-timeline" style="color:var(--primary);margin-right:6px;"></i>Recent Events</div>
            </div>
            <div class="nx-card-body">
                <div class="timeline">
                    @foreach($recentEvents as $evt)
                    <div class="timeline-item {{ $evt->event_type === 'triggered' ? 'critical' : ($evt->event_type === 'resolved' ? 'success' : 'warning') }}">
                        <div class="timeline-time">{{ $evt->created_at?->format('d/m H:i') }}</div>
                        <div class="timeline-text">
                            <strong>{{ ucfirst($evt->event_type) }}</strong> — {{ Str::limit($evt->alarm?->description ?? '', 60) }}
                            @if($evt->performer) <span style="color:var(--text-muted);">by {{ $evt->performer->name }}</span> @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- ALARM DETAIL PANEL -->
    @if($selectedAlarm)
    <div class="col-xl-5">
        <div class="detail-panel" style="position:sticky;top:calc(64px + 24px);">
            <div class="detail-panel-header" style="background:linear-gradient(135deg,{{ $selectedAlarm->severity==='critical'?'#991b1b, #b91c1c':'#92400e, #b45309' }});">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <div style="width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-triangle-exclamation" style="font-size:16px;"></i>
                    </div>
                    <div>
                        <div style="font-size:11px;opacity:0.6;">{{ $selectedAlarm->code }}</div>
                        <div style="font-size:15px;font-weight:700;">Alarm Detail</div>
                    </div>
                    <span class="nx-badge" style="margin-left:auto;background:rgba(255,255,255,0.2);color:white;">{{ ucfirst($selectedAlarm->severity) }}</span>
                </div>
            </div>
            <div class="detail-panel-body">
                <div style="padding:12px;background:#fef2f2;border-radius:8px;margin-bottom:16px;border-left:4px solid var(--danger);">
                    <div style="font-size:13px;font-weight:600;color:var(--text);">{{ $selectedAlarm->description }}</div>
                    @if($selectedAlarm->description_th)
                    <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">{{ $selectedAlarm->description_th }}</div>
                    @endif
                </div>

                @foreach([
                    ['Source', $selectedAlarm->equipment?->name ?? 'System'],
                    ['Building', $selectedAlarm->building?->name ?? '-'],
                    ['Floor', $selectedAlarm->floor ? 'Floor '.$selectedAlarm->floor->floor_number : '-'],
                    ['Category', $selectedAlarm->category ?? '-'],
                    ['Triggered', $selectedAlarm->triggered_at?->format('d/m/Y H:i:s') ?? '-'],
                    ['Status', ucfirst($selectedAlarm->status)],
                ] as $row)
                <div class="detail-row">
                    <span class="detail-key">{{ $row[0] }}</span>
                    <span class="detail-val" style="font-size:12px;">{{ $row[1] }}</span>
                </div>
                @endforeach

                @if($selectedAlarm->recommended_action)
                <div style="margin-top:12px;">
                    <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Recommended Action</div>
                    <div style="padding:12px;background:#fffbeb;border-radius:6px;border-left:3px solid var(--warning);font-size:12px;color:var(--text);">
                        <i class="fa-solid fa-lightbulb" style="color:var(--warning);margin-right:6px;"></i>
                        {{ $selectedAlarm->recommended_action }}
                    </div>
                </div>
                @endif

                <!-- Action Buttons -->
                <div style="margin-top:16px;">
                    <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Actions</div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;">
                        @if($selectedAlarm->status === 'active')
                        <button class="nx-btn nx-btn-warning nx-btn-sm" onclick="acknowledgeAlarm({{ $selectedAlarm->id }})">
                            <i class="fa-solid fa-eye"></i> Acknowledge
                        </button>
                        <button class="nx-btn nx-btn-outline nx-btn-sm" onclick="silenceAlarm({{ $selectedAlarm->id }})">
                            <i class="fa-solid fa-bell-slash"></i> Silence 30m
                        </button>
                        @endif
                        @if(in_array($selectedAlarm->status, ['active','acknowledged']))
                        <button class="nx-btn nx-btn-success nx-btn-sm" onclick="resolveAlarm({{ $selectedAlarm->id }})">
                            <i class="fa-solid fa-check"></i> Resolve
                        </button>
                        @endif
                        <button class="nx-btn nx-btn-outline nx-btn-sm">
                            <i class="fa-solid fa-user-plus"></i> Assign
                        </button>
                        <button class="nx-btn nx-btn-outline nx-btn-sm" style="color:var(--danger);">
                            <i class="fa-solid fa-arrow-up"></i> Escalate
                        </button>
                    </div>
                </div>

                <!-- Event History -->
                @if($selectedAlarm->events->isNotEmpty())
                <div style="margin-top:16px;">
                    <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Event History</div>
                    <div class="timeline">
                        @foreach($selectedAlarm->events->take(5) as $evt)
                        <div class="timeline-item {{ $evt->event_type==='resolved'?'success':($evt->event_type==='triggered'?'critical':'warning') }}">
                            <div class="timeline-time">{{ $evt->created_at?->format('d/m H:i') }}</div>
                            <div class="timeline-text">{{ ucfirst($evt->event_type) }}{{ $evt->performer ? ' by '.$evt->performer->name : '' }}</div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

</div>
@endsection

@section('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function acknowledgeAlarm(id) {
    if (!confirm('Acknowledge this alarm?')) return;
    fetch(`/alarms/${id}/acknowledge`, { method:'POST', headers:{'X-CSRF-TOKEN':csrfToken,'Content-Type':'application/json','Accept':'application/json'} })
        .then(r => r.json()).then(d => { if(d.success) location.reload(); });
}
function resolveAlarm(id) {
    if (!confirm('Mark this alarm as resolved?')) return;
    fetch(`/alarms/${id}/resolve`, { method:'POST', headers:{'X-CSRF-TOKEN':csrfToken,'Content-Type':'application/json','Accept':'application/json'} })
        .then(r => r.json()).then(d => { if(d.success) location.reload(); });
}
function silenceAlarm(id) {
    fetch(`/alarms/${id}/silence`, { method:'POST', headers:{'X-CSRF-TOKEN':csrfToken,'Content-Type':'application/json','Accept':'application/json'}, body: JSON.stringify({minutes:30}) })
        .then(r => r.json()).then(d => { if(d.success) location.reload(); });
}
</script>
@endsection
