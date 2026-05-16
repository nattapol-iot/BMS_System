@extends('layouts.app')

@section('title', __('menu.schedule_overview') ?? 'Schedule Overview')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Section Header --}}
    <div class="section-header d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-white">
                <i class="fa-solid fa-calendar-check me-2" style="color:var(--nx-cyan)"></i>
                {{ __('menu.schedules') ?? 'Schedules' }}
            </h4>
            <small class="text-muted">{{ __('menu.schedules_sub') ?? 'Manage automated schedules for HVAC, lighting and access control' }}</small>
        </div>
        <a href="{{ route('schedules.create') }}" class="nx-btn nx-btn-primary">
            <i class="fa-solid fa-plus me-1"></i>
            {{ __('menu.add_schedule') ?? 'Add Schedule' }}
        </a>
    </div>

    {{-- Tab Navigation --}}
    <div class="d-flex gap-2 mb-4">
        <a href="{{ route('schedules.index') }}"
           class="nx-chip {{ request()->routeIs('schedules.index') ? 'nx-chip-active' : '' }}">
            <i class="fa-solid fa-list-ul me-1"></i>
            {{ __('menu.schedule_overview') ?? 'Schedule Overview' }}
        </a>
        <a href="{{ route('schedules.calendar') }}"
           class="nx-chip {{ request()->routeIs('schedules.calendar') ? 'nx-chip-active' : '' }}">
            <i class="fa-solid fa-calendar-days me-1"></i>
            {{ __('menu.calendar_view') ?? 'Calendar View' }}
        </a>
        <a href="{{ route('schedules.device-settings') }}"
           class="nx-chip {{ request()->routeIs('schedules.device-settings') ? 'nx-chip-active' : '' }}">
            <i class="fa-solid fa-sliders me-1"></i>
            {{ __('menu.device_time_setting') ?? 'Device Time Setting' }}
        </a>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(29,78,216,0.15)">
                    <i class="fa-solid fa-calendar-check" style="color:#1d4ed8"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.total_schedules') ?? 'Total Schedules' }}</div>
                    <div class="stat-value">{{ $schedules->total() }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.15)">
                    <i class="fa-solid fa-circle-check" style="color:#10b981"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.active') ?? 'Active' }}</div>
                    <div class="stat-value">{{ $schedules->getCollection()->where('is_active', true)->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(6,182,212,0.15)">
                    <i class="fa-solid fa-wind" style="color:#06b6d4"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">HVAC</div>
                    <div class="stat-value">{{ $schedules->getCollection()->where('schedule_type', 'hvac')->count() }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.15)">
                    <i class="fa-solid fa-lightbulb" style="color:#f59e0b"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.lighting') ?? 'Lighting' }}</div>
                    <div class="stat-value">{{ $schedules->getCollection()->where('schedule_type', 'lighting')->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Schedules Table --}}
    <div class="nx-card">
        <div class="nx-card-header d-flex align-items-center justify-content-between">
            <span>
                <i class="fa-solid fa-table-list me-2" style="color:var(--nx-cyan)"></i>
                {{ __('menu.schedule_list') ?? 'Schedule List' }}
            </span>
            <span class="text-muted small">{{ $schedules->total() }} {{ __('menu.records') ?? 'records' }}</span>
        </div>
        <div class="nx-card-body p-0">
            @if($schedules->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-calendar-xmark fa-2x mb-3 d-block opacity-40"></i>
                    <p>{{ __('menu.no_schedules') ?? 'No schedules found.' }}</p>
                    <a href="{{ route('schedules.create') }}" class="nx-btn nx-btn-primary">
                        <i class="fa-solid fa-plus me-1"></i>
                        {{ __('menu.add_schedule') ?? 'Add Schedule' }}
                    </a>
                </div>
            @else
            <div class="table-responsive">
                <table class="nx-table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('menu.name') ?? 'Name' }}</th>
                            <th>{{ __('menu.type') ?? 'Type' }}</th>
                            <th>{{ __('menu.start_time') ?? 'Start Time' }}</th>
                            <th>{{ __('menu.end_time') ?? 'End Time' }}</th>
                            <th>{{ __('menu.repeat_days') ?? 'Repeat Days' }}</th>
                            <th class="text-center">{{ __('menu.devices') ?? 'Devices' }}</th>
                            <th class="text-center">{{ __('menu.status') ?? 'Status' }}</th>
                            <th class="text-center">{{ __('menu.actions') ?? 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($schedules as $s)
                        @php
                            $typeColors = [
                                'hvac'           => ['bg'=>'rgba(6,182,212,0.15)','color'=>'#06b6d4','icon'=>'fa-wind'],
                                'lighting'       => ['bg'=>'rgba(245,158,11,0.15)','color'=>'#f59e0b','icon'=>'fa-lightbulb'],
                                'access_control' => ['bg'=>'rgba(16,185,129,0.15)','color'=>'#10b981','icon'=>'fa-door-open'],
                                'general'        => ['bg'=>'rgba(107,114,128,0.15)','color'=>'#9ca3af','icon'=>'fa-gear'],
                            ];
                            $tc = $typeColors[$s->schedule_type] ?? $typeColors['general'];
                            $dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                            $dayMap   = [0=>'Sun',1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat'];
                            $repeatDays = is_array($s->repeat_days) ? $s->repeat_days : json_decode($s->repeat_days ?? '[]', true);
                        @endphp
                        <tr>
                            <td class="fw-semibold text-white">{{ $s->name }}</td>
                            <td>
                                <span class="nx-badge" style="background:{{ $tc['bg'] }};color:{{ $tc['color'] }}">
                                    <i class="fa-solid {{ $tc['icon'] }} me-1"></i>
                                    {{ ucwords(str_replace('_', ' ', $s->schedule_type)) }}
                                </span>
                            </td>
                            <td class="text-muted">
                                <i class="fa-regular fa-clock me-1"></i>
                                {{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}
                            </td>
                            <td class="text-muted">
                                <i class="fa-regular fa-clock me-1"></i>
                                {{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($dayMap as $num => $abbr)
                                        @php $active = in_array($num, $repeatDays) || in_array($abbr, $repeatDays); @endphp
                                        <span class="nx-chip" style="{{ $active ? 'background:rgba(29,78,216,0.25);color:#93c5fd;' : 'background:rgba(255,255,255,0.05);color:#4b5563;' }}font-size:.7rem;padding:1px 6px;">
                                            {{ $abbr }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="nx-badge" style="background:rgba(29,78,216,0.15);color:#1d4ed8;">
                                    {{ method_exists($s, 'deviceCount') ? $s->deviceCount() : ($s->devices_count ?? 0) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="form-check form-switch d-flex justify-content-center mb-0">
                                    <input type="checkbox"
                                           class="form-check-input"
                                           role="switch"
                                           style="cursor:pointer;"
                                           {{ $s->is_active ? 'checked' : '' }}
                                           onchange="toggleSchedule(this, {{ $s->id }})">
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('schedules.edit', $s->id) }}"
                                       class="nx-btn nx-btn-outline" style="padding:4px 10px;font-size:.78rem;">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form method="POST" action="{{ route('schedules.destroy', $s->id) }}"
                                          onsubmit="return confirm('{{ __('menu.confirm_delete') ?? 'Delete this schedule?' }}')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="nx-btn"
                                                style="padding:4px 10px;font-size:.78rem;background:rgba(239,68,68,0.12);color:#ef4444;border:1px solid rgba(239,68,68,0.3);">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($schedules->hasPages())
            <div class="d-flex justify-content-end p-3">
                {{ $schedules->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>

</div>

@push('scripts')
<script>
function toggleSchedule(el, id) {
    const isActive = el.checked ? 1 : 0;
    nexusPost(`/schedules/${id}/toggle`, { is_active: isActive })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const label = el.checked ? 'Activated' : 'Deactivated';
                const color = el.checked ? '#10b981' : '#9ca3af';
                showToast(label, color);
            } else {
                el.checked = !el.checked;
                showToast('Failed to update schedule', '#ef4444');
            }
        })
        .catch(() => {
            el.checked = !el.checked;
            showToast('Network error', '#ef4444');
        });
}

function showToast(msg, color) {
    const t = document.createElement('div');
    t.style.cssText = `position:fixed;bottom:24px;right:24px;background:${color};color:#fff;padding:10px 20px;border-radius:8px;z-index:9999;font-size:.875rem;box-shadow:0 4px 16px rgba(0,0,0,.4);`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.remove(), 3000);
}
</script>
@endpush
@endsection
