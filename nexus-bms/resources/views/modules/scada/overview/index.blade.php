@extends('themes.nexus-scada.layouts.app')

@section('title', 'SCADA Overview')
@section('page-title', 'SCADA / Plant Overview')
@section('page-subtitle', 'Real-time operational summary')

@section('content')
<div class="fade-in">

{{-- Row 1: Hero KPIs --}}
<div class="row g-3 mb-4">
    <div class="col-xl col-md-6">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-microchip"></i></div>
            <div class="stat-content">
                <div class="stat-value" data-kpi="total_devices">{{ $kpis['total_devices'] }}</div>
                <div class="stat-label">Total Devices</div>
                <div class="stat-trend up">
                    <span class="nx-badge badge-success" style="font-size:10px"><span data-kpi="devices_online">{{ $kpis['devices_online'] }}</span> online</span>
                    <span class="nx-badge badge-danger" style="font-size:10px;margin-left:4px"><span data-kpi="devices_offline">{{ $kpis['devices_offline'] }}</span> offline</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-6">
        <div class="stat-card">
            <div class="stat-icon cyan"><i class="fa-solid fa-tags"></i></div>
            <div class="stat-content">
                <div class="stat-value" data-kpi="total_tags">{{ number_format($kpis['total_tags']) }}</div>
                <div class="stat-label">Total Tags</div>
                <div class="stat-trend up"><i class="fa-solid fa-bolt"></i> Polled across devices</div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-6">
        <div class="stat-card" style="border-left:4px solid var(--danger)">
            <div class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-content">
                <div class="stat-value" data-kpi="active_alarms" style="color:var(--danger)">{{ $kpis['active_alarms'] }}</div>
                <div class="stat-label">Active Alarms</div>
                <div class="stat-trend down">
                    <span class="nx-badge badge-danger" style="font-size:10px"><span data-kpi="critical_alarms">{{ $kpis['critical_alarms'] }}</span> critical</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl col-md-6">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-heart-pulse"></i></div>
            <div class="stat-content">
                <div class="stat-value"><span data-kpi="system_health">{{ $kpis['system_health'] }}</span><span style="font-size:14px;color:var(--text-muted);font-weight:600;">%</span></div>
                <div class="stat-label">System Health</div>
                <div class="stat-trend up"><i class="fa-solid fa-circle-check"></i> Uptime &amp; alarms</div>
            </div>
        </div>
    </div>
</div>

{{-- Row 2: Alarm summary + Hero trend --}}
<div class="row g-3 mb-4">
    <div class="col-xl-4">
        <div class="nx-card" style="height:100%">
            <div class="nx-card-header">
                <div class="nx-card-title"><i class="fa-solid fa-bell" style="color:var(--danger);margin-right:6px"></i>Alarm Summary</div>
                <a href="{{ route('scada.alarms.index') }}" class="nx-btn nx-btn-ghost nx-btn-sm">View all <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="nx-card-body">
                <div id="alarmDonut" style="min-height:220px"></div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="sev-pill sev-critical">{{ $alarmSummary['critical'] }} Critical</span>
                    <span class="sev-pill sev-high">{{ $alarmSummary['high'] }} High</span>
                    <span class="sev-pill sev-medium">{{ $alarmSummary['medium'] }} Medium</span>
                    <span class="sev-pill sev-low">{{ $alarmSummary['low'] }} Low</span>
                    <span class="sev-pill sev-info">{{ $alarmSummary['info'] }} Info</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="nx-card" style="height:100%">
            <div class="nx-card-header">
                <div class="nx-card-title"><i class="fa-solid fa-chart-line" style="color:var(--primary);margin-right:6px"></i>24-Hour Plant Trend</div>
                <a href="{{ route('scada.trend.index') }}" class="nx-btn nx-btn-ghost nx-btn-sm">Open Trend <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="nx-card-body">
                <div id="heroTrend" style="min-height:240px"></div>
            </div>
        </div>
    </div>
</div>

{{-- Row 3: Active alarms list + Devices grid --}}
<div class="row g-3">
    <div class="col-xl-6">
        <div class="nx-card" style="height:100%">
            <div class="nx-card-header">
                <div class="nx-card-title"><i class="fa-solid fa-list" style="color:var(--danger);margin-right:6px"></i>Active &amp; Acknowledged Alarms</div>
                <span style="font-size:12px;color:var(--text-muted)">{{ $recentAlarms->count() }} shown</span>
            </div>
            <div style="overflow-x:auto">
                <table class="nx-table">
                    <thead><tr><th>Severity</th><th>Tag</th><th>Message</th><th>Status</th><th>Time</th></tr></thead>
                    <tbody>
                    @forelse($recentAlarms as $a)
                        <tr>
                            <td><span class="sev-pill sev-{{ $a->severity }}">{{ ucfirst($a->severity) }}</span></td>
                            <td><span class="tag-code">{{ $a->tag?->code ?? '—' }}</span></td>
                            <td style="font-size:12px">{{ \Illuminate\Support\Str::limit($a->message, 56) }}</td>
                            <td><span class="alarm-status {{ $a->status }}">{{ $a->status }}</span></td>
                            <td style="font-size:11px;color:var(--text-muted)">{{ $a->triggered_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text-muted)">
                            <i class="fa-solid fa-circle-check" style="font-size:28px;color:var(--success);display:block;margin-bottom:6px"></i>
                            No active alarms
                        </td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="nx-card" style="height:100%">
            <div class="nx-card-header">
                <div class="nx-card-title"><i class="fa-solid fa-microchip" style="color:var(--primary);margin-right:6px"></i>Device Status</div>
                <a href="{{ route('scada.devices.index') }}" class="nx-btn nx-btn-ghost nx-btn-sm">All devices <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="nx-card-body">
                <div class="row g-2">
                    @foreach($deviceStatus as $d)
                        @php
                            $badge = $d->status === 'online' ? 'badge-success' : ($d->status === 'degraded' ? 'badge-warning' : 'badge-danger');
                        @endphp
                        <div class="col-md-4">
                            <div style="border:1px solid var(--border);border-radius:var(--radius-sm);padding:10px;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="tag-code">{{ $d->code }}</span>
                                    <span class="nx-badge {{ $badge }}" style="font-size:10px">{{ strtoupper($d->status) }}</span>
                                </div>
                                <div style="font-size:12px;margin-top:6px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ \Illuminate\Support\Str::limit($d->name, 26) }}</div>
                                <div style="font-size:11px;color:var(--text-muted);margin-top:4px">
                                    <i class="fa-regular fa-clock"></i>
                                    {{ $d->last_seen_at?->diffForHumans() ?? '—' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sum = @json($alarmSummary);
    new ApexCharts(document.querySelector('#alarmDonut'), {
        chart: { type: 'donut', height: 220, background: 'transparent' },
        labels: ['Critical','High','Medium','Low','Info'],
        series: [sum.critical, sum.high, sum.medium, sum.low, sum.info],
        colors: ['#ef4444','#f97316','#f59e0b','#3b82f6','#60a5fa'],
        legend: { show: false },
        plotOptions: { pie: { donut: { size: '70%',
            labels: { show: true, total: { show: true, label: 'Active', color: '#64748b',
                formatter: () => (sum.critical+sum.high+sum.medium+sum.low+sum.info) } } } } },
        stroke: { width: 0 },
        dataLabels: { enabled: false },
    }).render();

    const trend = @json($heroTrend);
    new ApexCharts(document.querySelector('#heroTrend'), {
        chart: { type: 'area', height: 240, background: 'transparent', toolbar: { show: false }, animations: { enabled: false } },
        series: trend.map(s => ({ name: s.name + ' (' + (s.unit || '') + ')', data: s.data })),
        xaxis: { type: 'datetime', labels: { style: { fontSize: '11px', colors: '#94a3b8' } } },
        yaxis: { labels: { style: { fontSize: '11px', colors: '#94a3b8' } } },
        colors: ['#1d4ed8', '#16a34a', '#f59e0b'],
        stroke: { curve: 'smooth', width: 2 },
        fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0 } },
        grid: { borderColor: '#f1f5f9', strokeDashArray: 4 },
        legend: { position: 'top', fontSize: '11px' },
        tooltip: { x: { format: 'HH:mm' } },
        dataLabels: { enabled: false },
    }).render();
});

// === Live polling: refresh KPI tiles every 5s without page reload ===
(function () {
    const url = "{{ route('api.scada.overview') }}";
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let consecutiveFailures = 0;

    function paint(kpis) {
        if (!kpis) return;
        document.querySelectorAll('[data-kpi]').forEach(el => {
            const key = el.dataset.kpi;
            if (kpis[key] === undefined || kpis[key] === null) return;
            const before = el.textContent.replace(/[\s,%]+/g, '');
            const after = String(kpis[key]).replace(/[\s,%]+/g, '');
            if (before !== after) {
                el.textContent = key === 'total_tags'
                    ? Number(kpis[key]).toLocaleString()
                    : kpis[key];
                el.style.transition = 'background 0.3s';
                el.style.background = 'rgba(34,211,238,0.18)';
                setTimeout(() => { el.style.background = 'transparent'; }, 400);
            }
        });
    }

    function refresh() {
        fetch(url, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...(csrf ? {'X-CSRF-TOKEN': csrf} : {}) },
            credentials: 'same-origin'
        })
        .then(r => r.ok ? r.json() : null)
        .then(body => {
            if (!body?.success) {
                consecutiveFailures++;
                return;
            }
            consecutiveFailures = 0;
            paint(body.data?.kpis);
        })
        .catch(() => { consecutiveFailures++; });
    }

    // First refresh after 3s, then every 5s
    setTimeout(refresh, 3000);
    setInterval(refresh, 5000);
})();
</script>
@endpush
