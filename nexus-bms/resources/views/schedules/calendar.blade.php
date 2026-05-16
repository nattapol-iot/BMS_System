@extends('layouts.app')

@section('title', __('menu.calendar_view') ?? 'Calendar View')
@section('page-title', 'Calendar View / มุมมองปฏิทิน')
@section('page-subtitle', 'Visual schedule overview by month')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Section Header --}}
    <div class="section-header d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-white">
                <i class="fa-solid fa-calendar-days me-2" style="color:var(--nx-cyan)"></i>
                {{ __('menu.calendar_view') ?? 'Calendar View' }}
            </h4>
            <small class="text-muted">{{ __('menu.calendar_sub') ?? 'Visual schedule overview by month' }}</small>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="d-flex gap-2 mb-4">
        <a href="{{ route('schedules.index') }}"
           class="nx-chip {{ request()->routeIs('schedules.index') ? 'nx-chip-active' : '' }}">
            <i class="fa-solid fa-list-ul me-1"></i>
            {{ __('menu.schedule_overview') ?? 'Schedule Overview' }}
        </a>
        <a href="{{ route('schedules.calendar') }}"
           class="nx-chip nx-chip-active">
            <i class="fa-solid fa-calendar-days me-1"></i>
            {{ __('menu.calendar_view') ?? 'Calendar View' }}
        </a>
        <a href="{{ route('schedules.device-settings') }}"
           class="nx-chip {{ request()->routeIs('schedules.device-settings') ? 'nx-chip-active' : '' }}">
            <i class="fa-solid fa-sliders me-1"></i>
            {{ __('menu.device_time_setting') ?? 'Device Time Setting' }}
        </a>
    </div>

    {{-- Month Navigation --}}
    @php
        $prevMonth = $month - 1;
        $prevYear  = $year;
        if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

        $nextMonth = $month + 1;
        $nextYear  = $year;
        if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

        $monthName    = \Carbon\Carbon::create($year, $month, 1)->format('F Y');
        $firstDayOfWeek = (int) \Carbon\Carbon::create($year, $month, 1)->dayOfWeek; // 0=Sun
        $daysInMonth  = \Carbon\Carbon::create($year, $month, 1)->daysInMonth;
        $today        = now();
        $isCurrentMonth = ($today->month == $month && $today->year == $year);
        $todayDay     = $isCurrentMonth ? (int)$today->day : -1;

        // Color mapping
        $typeColors = [
            'hvac'           => '#1d4ed8',
            'lighting'       => '#f59e0b',
            'access_control' => '#10b981',
            'general'        => '#6b7280',
        ];

        // Day-of-week name mapping (Carbon: 0=Sun, 6=Sat)
        $dowAbbr = [0=>'Sun',1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat'];

        // Build a map: day_number => [schedules active on that day]
        // For each day, compute its day-of-week and check if repeat_days contains it
        $calendarMap = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = \Carbon\Carbon::create($year, $month, $d);
            $dow  = (int) $date->dayOfWeek; // 0=Sun
            $calendarMap[$d] = [];
            foreach ($schedules as $s) {
                $repeatDays = is_array($s->repeat_days)
                    ? $s->repeat_days
                    : json_decode($s->repeat_days ?? '[]', true);
                // Support numeric (0-6) or string abbr ('Sun','Mon',...)
                if (in_array($dow, $repeatDays) || in_array($dowAbbr[$dow], $repeatDays)) {
                    $calendarMap[$d][] = $s;
                }
            }
        }

        // Build grid cells: pad leading blanks
        $totalCells = $firstDayOfWeek + $daysInMonth;
        $rows = ceil($totalCells / 7);
    @endphp

    <div class="nx-card mb-4">
        {{-- Month nav header --}}
        <div class="nx-card-header d-flex align-items-center justify-content-between">
            <a href="{{ route('schedules.calendar', ['year' => $prevYear, 'month' => $prevMonth]) }}"
               class="nx-btn nx-btn-outline" style="padding:6px 14px;">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <span class="fw-bold text-white" style="font-size:1.1rem;">
                <i class="fa-regular fa-calendar me-2" style="color:var(--nx-cyan)"></i>
                {{ $monthName }}
            </span>
            <a href="{{ route('schedules.calendar', ['year' => $nextYear, 'month' => $nextMonth]) }}"
               class="nx-btn nx-btn-outline" style="padding:6px 14px;">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>

        {{-- Calendar Grid --}}
        <div class="nx-card-body p-3">
            {{-- Day headers --}}
            <div class="row g-0 mb-1">
                @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dh)
                <div class="col text-center py-2" style="font-size:.8rem;font-weight:600;color:#8898aa;border-bottom:1px solid rgba(255,255,255,0.06);">
                    {{ $dh }}
                </div>
                @endforeach
            </div>

            {{-- Weeks --}}
            @php $cellIndex = 0; @endphp
            @for($row = 0; $row < $rows; $row++)
            <div class="row g-1 mb-1">
                @for($col = 0; $col < 7; $col++)
                    @php
                        $dayNum = $cellIndex - $firstDayOfWeek + 1;
                        $cellIndex++;
                        $isValid = ($dayNum >= 1 && $dayNum <= $daysInMonth);
                        $isToday = $isValid && ($dayNum === $todayDay);
                    @endphp
                    <div class="col" style="min-height:90px;">
                        <div style="
                            background: {{ $isToday ? 'var(--nx-navy)' : 'rgba(255,255,255,0.02)' }};
                            border: 1px solid {{ $isToday ? 'var(--nx-blue)' : 'rgba(255,255,255,0.06)' }};
                            border-radius: 6px;
                            min-height: 90px;
                            padding: 6px;
                            height: 100%;
                        ">
                            @if($isValid)
                                <div class="mb-1" style="
                                    font-size:.8rem;
                                    font-weight:{{ $isToday ? '700' : '500' }};
                                    color:{{ $isToday ? '#fff' : '#8898aa' }};
                                    {{ $isToday ? 'background:#1d4ed8;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;' : '' }}
                                ">
                                    {{ $dayNum }}
                                </div>
                                @foreach(array_slice($calendarMap[$dayNum] ?? [], 0, 3) as $sched)
                                    @php $sc = $typeColors[$sched->schedule_type] ?? '#6b7280'; @endphp
                                    <div style="
                                        background:{{ $sc }}22;
                                        border-left:3px solid {{ $sc }};
                                        color:{{ $sc }};
                                        font-size:.65rem;
                                        padding:2px 5px;
                                        border-radius:3px;
                                        margin-bottom:2px;
                                        white-space:nowrap;
                                        overflow:hidden;
                                        text-overflow:ellipsis;
                                        max-width:100%;
                                    " title="{{ $sched->name }} ({{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }})">
                                        {{ Str::limit($sched->name, 14) }}
                                    </div>
                                @endforeach
                                @php $extra = count($calendarMap[$dayNum] ?? []) - 3; @endphp
                                @if($extra > 0)
                                    <div style="font-size:.62rem;color:#8898aa;">+{{ $extra }} more</div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endfor
            </div>
            @endfor
        </div>
    </div>

    {{-- Legend --}}
    <div class="nx-card">
        <div class="nx-card-header">
            <i class="fa-solid fa-circle-info me-2" style="color:var(--nx-cyan)"></i>
            {{ __('menu.legend') ?? 'Legend' }}
        </div>
        <div class="nx-card-body">
            <div class="d-flex flex-wrap gap-3">
                @foreach([
                    ['hvac', 'HVAC', '#1d4ed8', 'fa-wind'],
                    ['lighting', 'Lighting', '#f59e0b', 'fa-lightbulb'],
                    ['access_control', 'Access Control', '#10b981', 'fa-door-open'],
                    ['general', 'General', '#6b7280', 'fa-gear'],
                ] as [$type, $label, $color, $icon])
                <div class="d-flex align-items-center gap-2">
                    <div style="width:14px;height:14px;background:{{ $color }};border-radius:3px;"></div>
                    <i class="fa-solid {{ $icon }}" style="color:{{ $color }};font-size:.85rem;"></i>
                    <span class="text-muted small">{{ $label }}</span>
                    <span class="nx-badge" style="background:{{ $color }}22;color:{{ $color }};">
                        {{ $schedules->where('schedule_type', $type)->count() }}
                    </span>
                </div>
                @endforeach

                <div class="d-flex align-items-center gap-2 ms-4">
                    <div style="width:22px;height:22px;background:#1d4ed8;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.7rem;color:#fff;font-weight:700;">
                        {{ $todayDay > 0 ? $todayDay : now()->day }}
                    </div>
                    <span class="text-muted small">{{ __('menu.today') ?? 'Today' }}</span>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
