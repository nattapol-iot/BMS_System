@extends('layouts.app')

@section('title', __('menu.energy_management'))

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Section Header --}}
    <div class="section-header d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0 fw-bold text-white">
                <i class="fa-solid fa-bolt me-2" style="color:var(--nx-cyan)"></i>
                {{ __('menu.energy_management') }}
            </h4>
            <small class="text-muted">{{ __('menu.energy_sub') ?? 'Monitor energy consumption across all buildings' }}</small>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('energy.index') }}" class="d-flex gap-2 align-items-center">
                <label class="text-white-50 small me-1 mb-0">{{ __('menu.building') ?? 'Building' }}:</label>
                <select name="building_id" class="form-select form-select-sm" style="min-width:200px;background:#1a2a4a;color:#fff;border-color:#2d4a7a;" onchange="this.form.submit()">
                    <option value="">— {{ __('menu.all_buildings') ?? 'All Buildings' }} —</option>
                    @foreach($buildings as $b)
                        <option value="{{ $b->id }}" {{ optional($selectedBuilding)->id == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    {{-- Stat Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(29,78,216,0.15)">
                    <i class="fa-solid fa-calendar-day" style="color:#1d4ed8"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.today_kwh') ?? "Today's Usage" }}</div>
                    <div class="stat-value">{{ number_format($todayKwh, 1) }}</div>
                    <div class="stat-unit text-muted small">kWh</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(6,182,212,0.15)">
                    <i class="fa-solid fa-calendar-month" style="color:#06b6d4"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.month_kwh') ?? "This Month" }}</div>
                    <div class="stat-value">{{ number_format($monthKwh, 1) }}</div>
                    <div class="stat-unit text-muted small">kWh</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.15)">
                    <i class="fa-solid fa-gauge-high" style="color:#f59e0b"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.peak_demand') ?? "Peak Demand" }}</div>
                    <div class="stat-value">{{ number_format($peakDemand, 1) }}</div>
                    <div class="stat-unit text-muted small">kW</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.15)">
                    <i class="fa-solid fa-coins" style="color:#10b981"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.cost_estimate') ?? "Cost Estimate" }}</div>
                    <div class="stat-value">{{ number_format($costEstimate, 0) }}</div>
                    <div class="stat-unit text-muted small">THB / month</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="row g-3 mb-4">
        {{-- 30-Day Trend --}}
        <div class="col-xl-7">
            <div class="nx-card h-100">
                <div class="nx-card-header d-flex align-items-center justify-content-between">
                    <span>
                        <i class="fa-solid fa-chart-area me-2" style="color:var(--nx-blue)"></i>
                        {{ __('menu.energy_trend_30d') ?? '30-Day Energy Trend' }}
                    </span>
                    <span class="nx-badge" style="background:rgba(29,78,216,0.15);color:#1d4ed8;">kWh / day</span>
                </div>
                <div class="nx-card-body p-0">
                    <div id="chart-trend" style="height:260px;"></div>
                </div>
            </div>
        </div>

        {{-- Today Hourly --}}
        <div class="col-xl-5">
            <div class="nx-card h-100">
                <div class="nx-card-header d-flex align-items-center justify-content-between">
                    <span>
                        <i class="fa-solid fa-clock me-2" style="color:var(--nx-cyan)"></i>
                        {{ __('menu.hourly_today') ?? "Today's Hourly Usage" }}
                    </span>
                    <span class="nx-badge" style="background:rgba(6,182,212,0.15);color:#06b6d4;">kWh / hour</span>
                </div>
                <div class="nx-card-body p-0">
                    <div id="chart-hourly" style="height:260px;"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Energy Meters Table --}}
    <div class="nx-card">
        <div class="nx-card-header d-flex align-items-center justify-content-between">
            <span>
                <i class="fa-solid fa-meter me-2" style="color:var(--nx-cyan)"></i>
                {{ __('menu.energy_meters') ?? 'Energy Meters' }}
                @if($selectedBuilding)
                    <span class="nx-badge ms-2" style="background:rgba(6,182,212,0.15);color:#06b6d4;">
                        {{ $selectedBuilding->name }}
                    </span>
                @endif
            </span>
            <span class="text-muted small">{{ $meters->count() }} {{ __('menu.meters') ?? 'meters' }}</span>
        </div>
        <div class="nx-card-body p-0">
            @if($meters->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-plug-circle-xmark fa-2x mb-3 d-block opacity-40"></i>
                    {{ __('menu.no_meters') ?? 'No meters found. Please select a building.' }}
                </div>
            @else
            <div class="table-responsive">
                <table class="nx-table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('menu.meter_name') ?? 'Meter Name' }}</th>
                            <th>{{ __('menu.type') ?? 'Type' }}</th>
                            <th>{{ __('menu.floor') ?? 'Floor' }}</th>
                            <th class="text-end">{{ __('menu.today_reading') ?? "Today's Reading" }}</th>
                            <th class="text-end">{{ __('menu.monthly_reading') ?? 'Monthly Reading' }}</th>
                            <th class="text-center">{{ __('menu.status') ?? 'Status' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($meters as $meter)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="stat-icon-sm" style="background:{{ $meter->type === 'electricity' ? 'rgba(29,78,216,0.15)' : 'rgba(6,182,212,0.15)' }}">
                                        <i class="fa-solid {{ $meter->type === 'electricity' ? 'fa-bolt' : 'fa-droplet' }}"
                                           style="color:{{ $meter->type === 'electricity' ? '#1d4ed8' : '#06b6d4' }};font-size:.75rem;"></i>
                                    </span>
                                    <span class="fw-semibold text-white">{{ $meter->name }}</span>
                                </div>
                            </td>
                            <td>
                                @if($meter->type === 'electricity')
                                    <span class="nx-badge" style="background:rgba(29,78,216,0.15);color:#1d4ed8;">
                                        <i class="fa-solid fa-bolt me-1"></i>Electricity
                                    </span>
                                @elseif($meter->type === 'water')
                                    <span class="nx-badge" style="background:rgba(6,182,212,0.15);color:#06b6d4;">
                                        <i class="fa-solid fa-droplet me-1"></i>Water
                                    </span>
                                @else
                                    <span class="nx-badge" style="background:rgba(107,114,128,0.15);color:#9ca3af;">
                                        {{ ucfirst($meter->type) }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted">{{ optional($meter->floor)->name ?? '—' }}</td>
                            <td class="text-end fw-semibold text-white">
                                {{ number_format($meter->today_kwh ?? 0, 2) }}
                                <span class="text-muted small ms-1">{{ $meter->type === 'water' ? 'm³' : 'kWh' }}</span>
                            </td>
                            <td class="text-end text-white">
                                {{ number_format($meter->monthly_kwh ?? 0, 2) }}
                                <span class="text-muted small ms-1">{{ $meter->type === 'water' ? 'm³' : 'kWh' }}</span>
                            </td>
                            <td class="text-center">
                                @if($meter->status === 'active' || $meter->status === 'online')
                                    <span class="nx-badge" style="background:rgba(16,185,129,0.15);color:#10b981;">
                                        <i class="fa-solid fa-circle me-1" style="font-size:.45rem;vertical-align:middle;"></i>
                                        {{ __('menu.active') ?? 'Active' }}
                                    </span>
                                @elseif($meter->status === 'fault' || $meter->status === 'error')
                                    <span class="nx-badge" style="background:rgba(239,68,68,0.15);color:#ef4444;">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                        {{ __('menu.fault') ?? 'Fault' }}
                                    </span>
                                @else
                                    <span class="nx-badge" style="background:rgba(107,114,128,0.15);color:#9ca3af;">
                                        {{ ucfirst($meter->status ?? 'offline') }}
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

</div>

@push('scripts')
<script>
(function () {
    // --- 30-Day Trend Chart ---
    const trendRaw = @json($trendData);
    const trendDates = trendRaw.map(d => d.date);
    const trendKwh  = trendRaw.map(d => parseFloat(d.kwh));

    new ApexCharts(document.querySelector('#chart-trend'), {
        chart: {
            type: 'area',
            height: 260,
            background: 'transparent',
            toolbar: { show: false },
            zoom: { enabled: false }
        },
        series: [{ name: 'kWh', data: trendKwh }],
        xaxis: {
            categories: trendDates,
            labels: {
                style: { colors: '#8898aa', fontSize: '11px' },
                rotate: -35,
                formatter: v => v ? v.slice(5) : ''
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: { style: { colors: '#8898aa', fontSize: '11px' }, formatter: v => v.toFixed(0) + ' kWh' }
        },
        colors: ['#1d4ed8'],
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.45,
                opacityTo: 0.02,
                stops: [0, 95]
            }
        },
        stroke: { curve: 'smooth', width: 2 },
        dataLabels: { enabled: false },
        grid: {
            borderColor: 'rgba(255,255,255,0.06)',
            strokeDashArray: 4
        },
        tooltip: {
            theme: 'dark',
            y: { formatter: v => v.toFixed(1) + ' kWh' }
        },
        markers: { size: 3, colors: ['#1d4ed8'], strokeWidth: 0 }
    }).render();

    // --- Hourly Bar Chart ---
    const hourlyRaw = @json($hourlyData);
    const allHours = Array.from({length: 24}, (_, i) => i);
    const hourlyMap = {};
    hourlyRaw.forEach(h => { hourlyMap[h.hour] = parseFloat(h.kwh); });
    const hourlyKwh = allHours.map(h => hourlyMap[h] ?? 0);
    const hourLabels = allHours.map(h => (h < 10 ? '0' + h : '' + h) + ':00');

    new ApexCharts(document.querySelector('#chart-hourly'), {
        chart: {
            type: 'bar',
            height: 260,
            background: 'transparent',
            toolbar: { show: false }
        },
        series: [{ name: 'kWh', data: hourlyKwh }],
        xaxis: {
            categories: hourLabels,
            labels: {
                style: { colors: '#8898aa', fontSize: '10px' },
                rotate: -45,
                formatter: (v, i) => (i % 3 === 0) ? v : ''
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: { style: { colors: '#8898aa', fontSize: '11px' }, formatter: v => v.toFixed(1) }
        },
        colors: ['#06b6d4'],
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '70%',
                dataLabels: { position: 'top' }
            }
        },
        dataLabels: { enabled: false },
        grid: { borderColor: 'rgba(255,255,255,0.06)', strokeDashArray: 4 },
        tooltip: {
            theme: 'dark',
            y: { formatter: v => v.toFixed(2) + ' kWh' }
        }
    }).render();
})();
</script>
@endpush
@endsection
