@extends('layouts.app')
@section('title', __('menu.dashboard'))
@section('page-title', __('menu.dashboard'))
@section('page-subtitle', __('dashboard.subtitle'))

@section('content')
<div class="fade-in">

<!-- STATS ROW -->
<div class="row g-3 mb-4">
    <!-- Energy Usage -->
    <div class="col-xl col-md-6">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-bolt"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ number_format($stats['todayEnergy'], 0) }}</div>
                <div class="stat-label">{{ __('dashboard.energy_today') }} (kWh)</div>
                <div class="stat-trend {{ $stats['energyTrend'] >= 0 ? 'up' : 'down' }}">
                    <i class="fa-solid fa-arrow-{{ $stats['energyTrend'] >= 0 ? 'up' : 'down' }}"></i>
                    {{ abs($stats['energyTrend']) }}% {{ __('dashboard.vs_yesterday') }}
                </div>
            </div>
        </div>
    </div>
    <!-- HVAC Status -->
    <div class="col-xl col-md-6">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-wind"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ $stats['hvacTotal'] > 0 ? round($stats['hvacOnline']/$stats['hvacTotal']*100) : 98 }}%</div>
                <div class="stat-label">{{ __('dashboard.hvac_status') }}</div>
                <div class="stat-trend up">
                    <i class="fa-solid fa-circle-check"></i>
                    {{ $stats['hvacOnline'] }}/{{ $stats['hvacTotal'] }} {{ __('dashboard.units_online') }}
                </div>
            </div>
        </div>
    </div>
    <!-- Water -->
    <div class="col-xl col-md-6">
        <div class="stat-card">
            <div class="stat-icon cyan"><i class="fa-solid fa-droplet"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ number_format($stats['waterToday'] ?: 12430) }}</div>
                <div class="stat-label">{{ __('dashboard.water_today') }} (L)</div>
                <div class="stat-trend down">
                    <i class="fa-solid fa-arrow-down"></i>
                    3.2% {{ __('dashboard.vs_yesterday') }}
                </div>
            </div>
        </div>
    </div>
    <!-- Active Alarms -->
    <div class="col-xl col-md-6">
        <div class="stat-card">
            <div class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-content">
                <div class="stat-value" style="color:var(--danger)">{{ $stats['activeAlarms'] }}</div>
                <div class="stat-label">{{ __('dashboard.active_alarms') }}</div>
                <div class="stat-trend down">
                    <span class="nx-badge badge-danger" style="font-size:10px;">{{ $stats['criticalAlarms'] }} {{ __('dashboard.critical') }}</span>
                </div>
            </div>
        </div>
    </div>
    <!-- System Health -->
    <div class="col-xl col-md-6">
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fa-solid fa-heart-pulse"></i></div>
            <div class="stat-content">
                <div class="stat-value" style="color:var(--success)">{{ $stats['systemHealth'] }}%</div>
                <div class="stat-label">{{ __('dashboard.system_health') }}</div>
                <div style="margin-top:8px;">
                    <div class="health-bar">
                        <div class="health-fill good" style="width:{{ $stats['systemHealth'] }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MIDDLE ROW -->
<div class="row g-3 mb-4">
    <!-- Energy Chart -->
    <div class="col-xl-7">
        <div class="nx-card" style="height:320px;">
            <div class="nx-card-header">
                <div class="nx-card-title"><i class="fa-solid fa-chart-line" style="color:var(--primary);margin-right:8px;"></i>{{ __('dashboard.energy_overview') }}</div>
                <div style="display:flex;gap:6px;">
                    <button class="nx-btn nx-btn-outline nx-btn-sm" onclick="setRange('7d')">7D</button>
                    <button class="nx-btn nx-btn-primary nx-btn-sm" onclick="setRange('30d')">30D</button>
                </div>
            </div>
            <div class="nx-card-body" style="padding:12px 16px;">
                <div id="energyChart" style="height:230px;"></div>
            </div>
        </div>
    </div>

    <!-- Energy Breakdown -->
    <div class="col-xl-5">
        <div class="nx-card" style="height:320px;">
            <div class="nx-card-header">
                <div class="nx-card-title"><i class="fa-solid fa-chart-pie" style="color:var(--warning);margin-right:8px;"></i>{{ __('dashboard.energy_breakdown') }}</div>
                <span class="nx-badge badge-info">{{ __('dashboard.this_month') }}</span>
            </div>
            <div class="nx-card-body" style="padding:8px;display:flex;align-items:center;gap:12px;">
                <div id="energyDonut" style="width:160px;flex-shrink:0;"></div>
                <div style="flex:1;">
                    @foreach($energyBreakdown as $item)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f1f5f9;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="width:10px;height:10px;border-radius:2px;background:{{ $item['color'] }};flex-shrink:0;"></div>
                            <span style="font-size:12px;color:var(--text-muted);">{{ $item['label'] }}</span>
                        </div>
                        <strong style="font-size:12px;">{{ $item['value'] }}%</strong>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BOTTOM ROW -->
<div class="row g-3">
    <!-- Equipment Status -->
    <div class="col-xl-7">
        <div class="nx-card">
            <div class="nx-card-header">
                <div class="nx-card-title"><i class="fa-solid fa-microchip" style="color:var(--primary);margin-right:8px;"></i>{{ __('dashboard.equipment_status') }}</div>
                <a href="{{ route('equipment.index') }}" class="nx-btn nx-btn-ghost nx-btn-sm">{{ __('dashboard.view_all') }} <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="nx-card-body" style="padding:12px 16px;">
                <div class="row g-2">
                    @forelse($equipmentStatus as $eq)
                    <div class="col-md-6">
                        <div style="padding:12px;border:1px solid var(--border);border-radius:var(--radius-sm);display:flex;align-items:center;gap:12px;">
                            <div style="width:40px;height:40px;border-radius:8px;background:{{ $eq->category?->color ?? '#3b82f6' }}20;display:flex;align-items:center;justify-content:center;">
                                <i class="fa-solid {{ $eq->category?->icon ?? 'fa-cog' }}" style="color:{{ $eq->category?->color ?? '#3b82f6' }};font-size:16px;"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-size:12px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $eq->name }}</div>
                                <div style="font-size:11px;color:var(--text-muted);">{{ $eq->building?->name }}</div>
                                <div style="margin-top:4px;">
                                    <div class="health-bar">
                                        <div class="health-fill {{ $eq->health_score >= 80 ? 'good' : ($eq->health_score >= 50 ? 'warn' : 'crit') }}" style="width:{{ $eq->health_score }}%"></div>
                                    </div>
                                </div>
                            </div>
                            <span class="nx-badge {{ $eq->status_badge_class }}">{{ ucfirst($eq->status) }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="col-12"><p class="text-muted text-center py-3">{{ __('dashboard.no_equipment') }}</p></div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Alerts -->
    <div class="col-xl-5">
        <div class="nx-card">
            <div class="nx-card-header">
                <div class="nx-card-title"><i class="fa-solid fa-bell" style="color:var(--danger);margin-right:8px;"></i>{{ __('dashboard.recent_alerts') }}</div>
                <a href="{{ route('alarms.index') }}" class="nx-btn nx-btn-ghost nx-btn-sm">{{ __('dashboard.view_all') }} <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div style="max-height:320px;overflow-y:auto;">
                @forelse($recentAlarms as $alarm)
                <div style="padding:10px 16px;border-bottom:1px solid #f8fafc;display:flex;align-items:flex-start;gap:10px;">
                    <div style="width:8px;height:8px;border-radius:50%;margin-top:5px;flex-shrink:0;background:{{ $alarm->severity==='critical'?'#ef4444':($alarm->severity==='warning'?'#f59e0b':'#3b82f6') }};{{ $alarm->status==='active'?'box-shadow:0 0 6px currentColor;':'' }}"></div>
                    <div style="flex:1;min-width:0;">
                        <div style="font-size:12px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $alarm->description }}</div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">
                            {{ $alarm->building?->name }} • {{ $alarm->triggered_at?->diffForHumans() }}
                        </div>
                    </div>
                    <span class="nx-badge {{ $alarm->severity_badge_class }}" style="flex-shrink:0;font-size:10px;">{{ ucfirst($alarm->severity) }}</span>
                </div>
                @empty
                <div style="padding:32px;text-align:center;color:var(--text-muted);">
                    <i class="fa-solid fa-circle-check" style="font-size:32px;color:var(--success);margin-bottom:8px;display:block;"></i>
                    {{ __('dashboard.no_alarms') }}
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

</div>
@endsection

@section('scripts')
<script>
// Energy Chart
const energyData = @json($energyChartData);
const energyBreakdownData = @json($energyBreakdown);

// Area Chart
const energyChart = new ApexCharts(document.getElementById('energyChart'), {
    chart: { type: 'area', height: 230, toolbar: { show: false }, sparkline: { enabled: false } },
    series: [{
        name: '{{ __("dashboard.energy_kwh") }}',
        data: energyData.values
    }],
    xaxis: { categories: energyData.days, labels: { style: { fontSize: '11px', colors: '#94a3b8' } } },
    yaxis: { labels: { style: { fontSize: '11px', colors: '#94a3b8' }, formatter: v => v.toLocaleString() } },
    colors: ['#1d4ed8'],
    fill: {
        type: 'gradient',
        gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02, stops: [0, 90] }
    },
    stroke: { curve: 'smooth', width: 2.5 },
    grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
    dataLabels: { enabled: false },
    tooltip: { y: { formatter: v => v.toLocaleString() + ' kWh' } }
});
energyChart.render();

// Donut Chart
const energyDonut = new ApexCharts(document.getElementById('energyDonut'), {
    chart: { type: 'donut', height: 200 },
    series: energyBreakdownData.map(d => d.value),
    labels: energyBreakdownData.map(d => d.label),
    colors: energyBreakdownData.map(d => d.color),
    legend: { show: false },
    plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', fontSize: '11px', color: '#64748b', formatter: () => '100%' } } } } },
    dataLabels: { enabled: false },
    stroke: { width: 0 }
});
energyDonut.render();

// Range switcher (placeholder — wire to AJAX as needed)
function setRange(range) {
    document.querySelectorAll('.nx-btn-outline.nx-btn-sm, .nx-btn-primary.nx-btn-sm').forEach(btn => {
        btn.classList.remove('nx-btn-primary');
        btn.classList.add('nx-btn-outline');
    });
    event.target.classList.remove('nx-btn-outline');
    event.target.classList.add('nx-btn-primary');
}
</script>
@endsection
