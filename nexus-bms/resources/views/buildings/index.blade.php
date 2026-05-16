@extends('layouts.app')
@section('title', 'Buildings')
@section('page-title', 'Buildings / อาคาร')
@section('page-subtitle', 'Manage and monitor all buildings in the system')

@section('content')
<div class="fade-in">

<!-- STATS ROW -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-building"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ $totalBuildings }}</div>
                <div class="stat-label">Total Buildings</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ $activeBuildings }}</div>
                <div class="stat-label">Active Buildings</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon cyan"><i class="fa-solid fa-users"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ $buildings->sum('occupancy_count') }}</div>
                <div class="stat-label">Total Occupancy</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon red"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ $buildings->filter(fn($b) => $b->active_alarms_count > 0)->count() }}</div>
                <div class="stat-label">Buildings with Alerts</div>
            </div>
        </div>
    </div>
</div>

<!-- FILTER BAR -->
<div class="filter-bar mb-4">
    <form method="GET" action="{{ route('buildings.index') }}" class="d-flex align-items-center gap-3 flex-wrap w-100">
        <div style="position:relative;flex:1;min-width:200px;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:13px;"></i>
            <input type="text" name="search" class="nx-input" style="padding-left:32px;" placeholder="Search buildings..." value="{{ request('search') }}">
        </div>
        <select name="status" class="nx-select" style="width:140px;">
            <option value="">All Status</option>
            <option value="active" {{ request('status')==='active'?'selected':'' }}>Active</option>
            <option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Inactive</option>
        </select>
        <button type="submit" class="nx-btn nx-btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
        <a href="{{ route('buildings.index') }}" class="nx-btn nx-btn-outline">Reset</a>
        <a href="{{ route('buildings.create') }}" class="nx-btn nx-btn-primary ms-auto"><i class="fa-solid fa-plus"></i> Add Building</a>
    </form>
</div>

<!-- BUILDINGS GRID + DETAIL PANEL -->
<div class="row g-3">
    <div class="col-xl-8">
        <div class="row g-3" id="buildingsGrid">
            @forelse($buildings as $building)
            <div class="col-md-6 col-lg-4">
                <div class="nx-card building-card" style="cursor:pointer;transition:all 0.2s;" onclick="showBuildingDetail({{ $building->id }})" data-id="{{ $building->id }}">
                    <!-- Building Image -->
                    <div style="height:140px;background:linear-gradient(135deg,#0d1b34,#1d4ed8);border-radius:12px 12px 0 0;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;">
                        <div style="position:absolute;inset:0;background-image:radial-gradient(rgba(255,255,255,0.05) 1px,transparent 1px);background-size:20px 20px;"></div>
                        <i class="fa-solid fa-building" style="font-size:48px;color:rgba(255,255,255,0.2);"></i>
                        <div style="position:absolute;top:10px;right:10px;">
                            <span class="nx-badge {{ $building->status==='active' ? 'badge-success' : 'badge-secondary' }}">
                                <i class="fa-solid fa-circle" style="font-size:6px;"></i>
                                {{ ucfirst($building->status) }}
                            </span>
                        </div>
                        <div style="position:absolute;bottom:0;left:0;right:0;height:40px;background:linear-gradient(to top,rgba(0,0,0,0.4),transparent);"></div>
                        <div style="position:absolute;bottom:8px;left:12px;">
                            <span style="font-size:11px;color:rgba(255,255,255,0.7);"><i class="fa-solid fa-location-dot"></i> {{ $building->city }}</span>
                        </div>
                    </div>

                    <div class="nx-card-body" style="padding:14px;">
                        <div style="margin-bottom:10px;">
                            <div style="font-size:15px;font-weight:700;color:var(--text);">{{ $building->name }}</div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">{{ $building->code }} · {{ $building->floors_count }} Floors</div>
                        </div>

                        <!-- Stats row -->
                        <div style="display:flex;gap:12px;margin-bottom:12px;">
                            <div style="flex:1;text-align:center;padding:8px;background:#f8fafc;border-radius:8px;">
                                <div style="font-size:14px;font-weight:700;color:var(--primary);">{{ $building->floors_count }}</div>
                                <div style="font-size:10px;color:var(--text-muted);">Floors</div>
                            </div>
                            <div style="flex:1;text-align:center;padding:8px;background:#f8fafc;border-radius:8px;">
                                <div style="font-size:14px;font-weight:700;color:{{ $building->occupancy_percent > 80 ? '#ef4444' : '#22c55e' }};">{{ $building->occupancy_percent }}%</div>
                                <div style="font-size:10px;color:var(--text-muted);">Occupancy</div>
                            </div>
                            <div style="flex:1;text-align:center;padding:8px;background:#f8fafc;border-radius:8px;">
                                <div style="font-size:14px;font-weight:700;color:{{ $building->active_alarms_count > 0 ? '#ef4444' : '#22c55e' }};">{{ $building->active_alarms_count }}</div>
                                <div style="font-size:10px;color:var(--text-muted);">Alerts</div>
                            </div>
                        </div>

                        <!-- System status indicators -->
                        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px;">
                            @foreach([['HVAC','fa-wind','#3b82f6'],['Lighting','fa-lightbulb','#f59e0b'],['Fire','fa-fire','#ef4444'],['Access','fa-door-open','#22c55e']] as $sys)
                            <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:20px;font-size:10px;font-weight:600;background:{{ $sys[2] }}15;color:{{ $sys[2] }};">
                                <i class="fa-solid {{ $sys[1] }}" style="font-size:9px;"></i> {{ $sys[0] }}
                            </span>
                            @endforeach
                        </div>

                        <div class="d-flex gap-2">
                            <a href="#" onclick="showBuildingDetail({{ $building->id }});return false;" class="nx-btn nx-btn-outline flex-fill justify-content-center">
                                <i class="fa-solid fa-eye"></i> View
                            </a>
                            <a href="{{ route('buildings.edit', $building->id) }}" class="nx-btn nx-btn-primary" onclick="event.stopPropagation();" title="Edit" style="flex:0 0 auto;">
                                <i class="fa-solid fa-pen"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="nx-card" style="padding:60px;text-align:center;">
                    <i class="fa-solid fa-building" style="font-size:48px;color:#e2e8f0;margin-bottom:16px;"></i>
                    <p style="color:var(--text-muted);">No buildings found. <a href="{{ route('buildings.create') }}">Add your first building</a></p>
                </div>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        <div class="mt-4 d-flex justify-content-center">
            {{ $buildings->links() }}
        </div>
    </div>

    <!-- DETAIL PANEL -->
    <div class="col-xl-4">
        <div id="buildingDetailPanel" class="detail-panel" style="position:sticky;top:calc(64px + 24px);">
            <div class="detail-panel-header">
                <div style="font-size:13px;font-weight:600;opacity:0.7;margin-bottom:4px;">BUILDING DETAIL</div>
                <div style="font-size:18px;font-weight:700;" id="detailBuildingName">Select a building</div>
                <div style="font-size:12px;opacity:0.7;margin-top:2px;" id="detailBuildingAddress">Click any building card to view details</div>
            </div>
            <div class="detail-panel-body" id="detailPanelBody">
                <div style="text-align:center;padding:40px 20px;color:var(--text-muted);">
                    <i class="fa-solid fa-building" style="font-size:48px;color:#e2e8f0;margin-bottom:16px;display:block;"></i>
                    Click a building card to see details
                </div>
            </div>
        </div>
    </div>
</div>

</div>
@endsection

@section('scripts')
<script>
const buildings = @json($buildings->items());

function showBuildingDetail(id) {
    // Highlight selected card
    document.querySelectorAll('.building-card').forEach(c => c.style.borderColor = '');
    const card = document.querySelector(`.building-card[data-id="${id}"]`);
    if (card) card.style.borderColor = 'var(--primary)';

    const b = buildings.find(b => b.id == id);
    if (!b) return;

    document.getElementById('detailBuildingName').textContent = b.name;
    document.getElementById('detailBuildingAddress').textContent = b.address || b.city;

    const occPct = b.occupancy_capacity > 0 ? Math.round(b.occupancy_count / b.occupancy_capacity * 100) : 0;

    document.getElementById('detailPanelBody').innerHTML = `
        <div style="padding:4px 0;">
            <div class="detail-row">
                <span class="detail-key"><i class="fa-solid fa-hashtag" style="width:16px"></i> Code</span>
                <span class="detail-val">${b.code}</span>
            </div>
            <div class="detail-row">
                <span class="detail-key"><i class="fa-solid fa-layer-group" style="width:16px"></i> Floors</span>
                <span class="detail-val">${b.floors_count} floors</span>
            </div>
            <div class="detail-row">
                <span class="detail-key"><i class="fa-solid fa-location-dot" style="width:16px"></i> City</span>
                <span class="detail-val">${b.city}, ${b.country}</span>
            </div>
            <div class="detail-row">
                <span class="detail-key"><i class="fa-solid fa-users" style="width:16px"></i> Occupancy</span>
                <span class="detail-val">${b.occupancy_count} / ${b.occupancy_capacity}</span>
            </div>
            <div class="detail-row">
                <span class="detail-key"><i class="fa-solid fa-ruler-combined" style="width:16px"></i> Area</span>
                <span class="detail-val">${b.total_area ? Number(b.total_area).toLocaleString() + ' m²' : 'N/A'}</span>
            </div>
            <div class="detail-row">
                <span class="detail-key"><i class="fa-solid fa-calendar" style="width:16px"></i> Year Built</span>
                <span class="detail-val">${b.year_built || 'N/A'}</span>
            </div>
        </div>

        <div style="margin:16px 0 8px;font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;">Occupancy</div>
        <div style="height:8px;background:#f1f5f9;border-radius:4px;overflow:hidden;">
            <div style="height:100%;width:${occPct}%;background:${occPct>80?'#ef4444':'#22c55e'};border-radius:4px;"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-top:4px;">
            <span>${occPct}% occupied</span><span>${b.occupancy_count} people</span>
        </div>

        <div style="margin:16px 0 8px;font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;">Key Systems Status</div>
        ${[['HVAC','fa-wind','#3b82f6',95],['Lighting','fa-lightbulb','#f59e0b',88],['Fire Alarm','fa-fire','#ef4444',100],['Access Control','fa-door-open','#22c55e',92]].map(([name,icon,color,pct]) => `
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <i class="fa-solid ${icon}" style="color:${color};width:16px;font-size:12px;"></i>
            <span style="font-size:12px;color:var(--text-muted);flex:1">${name}</span>
            <div style="width:80px;height:5px;background:#f1f5f9;border-radius:3px;overflow:hidden;">
                <div style="height:100%;width:${pct}%;background:${color};border-radius:3px;"></div>
            </div>
            <span style="font-size:11px;font-weight:600;color:var(--text);width:32px;text-align:right">${pct}%</span>
        </div>`).join('')}

        <div style="margin-top:16px;display:flex;gap:8px;">
            <a href="/floors?building_id=${b.id}" class="nx-btn nx-btn-primary" style="flex:1;justify-content:center;font-size:12px;">
                <i class="fa-solid fa-layer-group"></i> View Floors
            </a>
            <a href="/buildings/${b.id}/edit" class="nx-btn nx-btn-outline" style="justify-content:center;font-size:12px;">
                <i class="fa-solid fa-pen"></i>
            </a>
        </div>
    `;
}
</script>
@endsection
