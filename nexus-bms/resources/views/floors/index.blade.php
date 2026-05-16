@extends('layouts.app')
@section('title', 'Floor Plans')
@section('page-title', 'Floor Plans / แปลนอาคาร')
@section('page-subtitle', 'Visual floor plan with equipment positioning')

@section('content')
<div class="fade-in">

<!-- SELECTOR BAR -->
<div class="filter-bar mb-4">
    <form method="GET" action="{{ route('floors.index') }}" class="d-flex align-items-center gap-3 flex-wrap w-100" id="floorForm">
        <span class="filter-label"><i class="fa-solid fa-building" style="color:var(--primary);"></i> Building:</span>
        <select name="building_id" class="nx-select" style="width:200px;" onchange="this.form.submit()">
            <option value="">Select Building...</option>
            @foreach($buildings as $b)
            <option value="{{ $b->id }}" {{ request('building_id')==$b->id?'selected':'' }}>{{ $b->name }}</option>
            @endforeach
        </select>

        @if($selectedBuilding)
        <span class="filter-label" style="margin-left:8px;"><i class="fa-solid fa-layer-group" style="color:var(--primary);"></i> Floor:</span>
        <select name="floor_id" class="nx-select" style="width:140px;" onchange="this.form.submit()">
            @foreach($floors as $fl)
            <option value="{{ $fl->id }}" {{ request('floor_id')==$fl->id || ($selectedFloor && $selectedFloor->id==$fl->id) ? 'selected' : '' }}>
                Floor {{ $fl->floor_number }}
            </option>
            @endforeach
        </select>
        @endif

        <div class="ms-auto d-flex gap-2">
            <button type="button" class="nx-btn nx-btn-outline nx-btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
            <button type="button" class="nx-btn nx-btn-outline nx-btn-sm"><i class="fa-solid fa-expand"></i> Fullscreen</button>
        </div>
    </form>
</div>

@if($selectedBuilding && $selectedFloor)
<div class="row g-3">
    <!-- FLOOR PLAN AREA -->
    <div class="col-xl-9">
        <div class="nx-card">
            <div class="nx-card-header">
                <div>
                    <div class="nx-card-title"><i class="fa-solid fa-map" style="color:var(--primary);margin-right:8px;"></i>{{ $selectedBuilding->name }} — Floor {{ $selectedFloor->floor_number }}</div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">{{ $selectedFloor->name_th ?? $selectedFloor->name }} · {{ $selectedFloor->area ? number_format($selectedFloor->area).' m²' : '' }}</div>
                </div>
                <div class="d-flex gap-2">
                    <button class="nx-btn nx-btn-outline nx-btn-sm" onclick="zoomIn()"><i class="fa-solid fa-plus"></i></button>
                    <button class="nx-btn nx-btn-outline nx-btn-sm" onclick="zoomOut()"><i class="fa-solid fa-minus"></i></button>
                    <button class="nx-btn nx-btn-outline nx-btn-sm" onclick="resetZoom()"><i class="fa-solid fa-arrows-to-circle"></i></button>
                </div>
            </div>
            <div class="nx-card-body" style="padding:0;overflow:hidden;border-radius:0 0 12px 12px;">
                <!-- Floor Plan SVG -->
                <div id="floorPlanContainer" style="width:100%;height:520px;overflow:hidden;background:#f8fafc;cursor:grab;position:relative;" onmousedown="startDrag(event)">
                    <div id="floorPlanInner" style="transform-origin:top left;transition:transform 0.1s;">
                        <svg viewBox="0 0 800 500" width="800" height="500" style="display:block;" id="floorSvg">
                            <defs>
                                <filter id="shadow"><feDropShadow dx="0" dy="1" stdDeviation="2" flood-opacity="0.1"/></filter>
                            </defs>

                            <!-- Background -->
                            <rect width="800" height="500" fill="#f0f4f8"/>

                            <!-- Corridor/Hallway -->
                            <rect x="0" y="200" width="800" height="60" fill="#e2e8f0" opacity="0.8"/>
                            <text x="400" y="235" text-anchor="middle" font-size="11" fill="#94a3b8" font-family="Inter,sans-serif">MAIN CORRIDOR</text>

                            <!-- TOP ROOMS -->
                            <!-- Room 1: Open Office A -->
                            <rect x="20" y="20" width="180" height="170" rx="4" fill="#dbeafe" stroke="#93c5fd" stroke-width="1.5" filter="url(#shadow)" class="floor-room" data-room="Open Office A" data-type="office"/>
                            <text x="110" y="95" text-anchor="middle" font-size="12" font-weight="600" fill="#1d4ed8" font-family="Inter,sans-serif">Open Office A</text>
                            <text x="110" y="112" text-anchor="middle" font-size="10" fill="#3b82f6" font-family="Inter,sans-serif">280 m²</text>

                            <!-- Room 2: Meeting Room A -->
                            <rect x="215" y="20" width="140" height="80" rx="4" fill="#d1fae5" stroke="#6ee7b7" stroke-width="1.5" filter="url(#shadow)" class="floor-room" data-room="Meeting Room A" data-type="meeting"/>
                            <text x="285" y="58" text-anchor="middle" font-size="11" font-weight="600" fill="#065f46" font-family="Inter,sans-serif">Meeting A</text>
                            <text x="285" y="72" text-anchor="middle" font-size="9" fill="#059669" font-family="Inter,sans-serif">45 m²</text>

                            <!-- Room 3: Meeting Room B -->
                            <rect x="215" y="115" width="140" height="75" rx="4" fill="#d1fae5" stroke="#6ee7b7" stroke-width="1.5" filter="url(#shadow)" class="floor-room" data-room="Meeting Room B" data-type="meeting"/>
                            <text x="285" y="151" text-anchor="middle" font-size="11" font-weight="600" fill="#065f46" font-family="Inter,sans-serif">Meeting B</text>
                            <text x="285" y="165" text-anchor="middle" font-size="9" fill="#059669" font-family="Inter,sans-serif">40 m²</text>

                            <!-- Room 4: Server Room -->
                            <rect x="370" y="20" width="120" height="80" rx="4" fill="#fee2e2" stroke="#fca5a5" stroke-width="1.5" filter="url(#shadow)" class="floor-room" data-room="Server Room" data-type="server"/>
                            <text x="430" y="58" text-anchor="middle" font-size="11" font-weight="600" fill="#991b1b" font-family="Inter,sans-serif">Server Room</text>
                            <text x="430" y="72" text-anchor="middle" font-size="9" fill="#dc2626" font-family="Inter,sans-serif">30 m²</text>

                            <!-- Room 5: Executive Office -->
                            <rect x="505" y="20" width="130" height="80" rx="4" fill="#ede9fe" stroke="#c4b5fd" stroke-width="1.5" filter="url(#shadow)" class="floor-room" data-room="Executive Office" data-type="office"/>
                            <text x="570" y="58" text-anchor="middle" font-size="11" font-weight="600" fill="#5b21b6" font-family="Inter,sans-serif">Executive</text>
                            <text x="570" y="72" text-anchor="middle" font-size="9" fill="#7c3aed" font-family="Inter,sans-serif">60 m²</text>

                            <!-- Room 6: Pantry/Common -->
                            <rect x="650" y="20" width="130" height="80" rx="4" fill="#fef9c3" stroke="#fde047" stroke-width="1.5" filter="url(#shadow)" class="floor-room" data-room="Pantry" data-type="common"/>
                            <text x="715" y="58" text-anchor="middle" font-size="11" font-weight="600" fill="#854d0e" font-family="Inter,sans-serif">Pantry</text>
                            <text x="715" y="72" text-anchor="middle" font-size="9" fill="#92400e" font-family="Inter,sans-serif">25 m²</text>

                            <!-- Toilets -->
                            <rect x="370" y="115" width="60" height="75" rx="4" fill="#f1f5f9" stroke="#cbd5e1" stroke-width="1.5"/>
                            <text x="400" y="155" text-anchor="middle" font-size="9" fill="#64748b" font-family="Inter,sans-serif">WC</text>
                            <rect x="435" y="115" width="60" height="75" rx="4" fill="#f1f5f9" stroke="#cbd5e1" stroke-width="1.5"/>
                            <text x="465" y="155" text-anchor="middle" font-size="9" fill="#64748b" font-family="Inter,sans-serif">WC</text>

                            <!-- BOTTOM ROOMS -->
                            <!-- Open Office B -->
                            <rect x="20" y="275" width="250" height="200" rx="4" fill="#dbeafe" stroke="#93c5fd" stroke-width="1.5" filter="url(#shadow)" class="floor-room" data-room="Open Office B" data-type="office"/>
                            <text x="145" y="372" text-anchor="middle" font-size="12" font-weight="600" fill="#1d4ed8" font-family="Inter,sans-serif">Open Office B</text>
                            <text x="145" y="390" text-anchor="middle" font-size="10" fill="#3b82f6" font-family="Inter,sans-serif">320 m²</text>

                            <!-- Training Room -->
                            <rect x="285" y="275" width="200" height="100" rx="4" fill="#d1fae5" stroke="#6ee7b7" stroke-width="1.5" filter="url(#shadow)" class="floor-room" data-room="Training Room" data-type="meeting"/>
                            <text x="385" y="323" text-anchor="middle" font-size="11" font-weight="600" fill="#065f46" font-family="Inter,sans-serif">Training Room</text>
                            <text x="385" y="337" text-anchor="middle" font-size="9" fill="#059669" font-family="Inter,sans-serif">85 m²</text>

                            <!-- Storage -->
                            <rect x="285" y="385" width="200" height="90" rx="4" fill="#f1f5f9" stroke="#cbd5e1" stroke-width="1.5" filter="url(#shadow)" class="floor-room" data-room="Storage" data-type="storage"/>
                            <text x="385" y="432" text-anchor="middle" font-size="11" font-weight="600" fill="#475569" font-family="Inter,sans-serif">Storage</text>
                            <text x="385" y="448" text-anchor="middle" font-size="9" fill="#64748b" font-family="Inter,sans-serif">50 m²</text>

                            <!-- Reception -->
                            <rect x="500" y="275" width="150" height="100" rx="4" fill="#fef9c3" stroke="#fde047" stroke-width="1.5" filter="url(#shadow)" class="floor-room" data-room="Reception" data-type="lobby"/>
                            <text x="575" y="323" text-anchor="middle" font-size="11" font-weight="600" fill="#854d0e" font-family="Inter,sans-serif">Reception</text>

                            <!-- IT Room -->
                            <rect x="665" y="275" width="115" height="100" rx="4" fill="#fee2e2" stroke="#fca5a5" stroke-width="1.5" filter="url(#shadow)" class="floor-room" data-room="IT Room" data-type="server"/>
                            <text x="722" y="323" text-anchor="middle" font-size="11" font-weight="600" fill="#991b1b" font-family="Inter,sans-serif">IT Room</text>

                            <!-- Lobby -->
                            <rect x="500" y="385" width="280" height="90" rx="4" fill="#f8fafc" stroke="#e2e8f0" stroke-width="1.5" filter="url(#shadow)" class="floor-room" data-room="Lobby" data-type="lobby"/>
                            <text x="640" y="432" text-anchor="middle" font-size="11" font-weight="600" fill="#475569" font-family="Inter,sans-serif">Lobby / Elevator Hall</text>

                            <!-- EQUIPMENT POINTS -->
                            <!-- HVAC -->
                            <circle cx="110" cy="50" r="9" fill="#3b82f6" stroke="white" stroke-width="2" class="equip-dot" data-type="hvac" data-name="AHU-07" style="cursor:pointer;filter:drop-shadow(0 0 4px #3b82f680)"/>
                            <circle cx="285" cy="40" r="9" fill="#3b82f6" stroke="white" stroke-width="2" class="equip-dot" data-type="hvac" data-name="AHU-08" style="cursor:pointer;"/>
                            <circle cx="145" cy="320" r="9" fill="#3b82f6" stroke="white" stroke-width="2" class="equip-dot" data-type="hvac" data-name="FCU-01" style="cursor:pointer;"/>

                            <!-- Fire Alarm -->
                            <circle cx="200" cy="50" r="9" fill="#ef4444" stroke="white" stroke-width="2" class="equip-dot" data-type="fire" data-name="FAP-03" style="cursor:pointer;filter:drop-shadow(0 0 4px #ef444480)"/>
                            <circle cx="600" cy="50" r="9" fill="#ef4444" stroke="white" stroke-width="2" class="equip-dot" data-type="fire" data-name="FAP-04" style="cursor:pointer;"/>
                            <circle cx="350" cy="320" r="9" fill="#ef4444" stroke="white" stroke-width="2" class="equip-dot" data-type="fire" data-name="FAD-01" style="cursor:pointer;"/>

                            <!-- Lighting -->
                            <circle cx="110" cy="140" r="9" fill="#f59e0b" stroke="white" stroke-width="2" class="equip-dot" data-type="light" data-name="LGT-2F-12" style="cursor:pointer;"/>
                            <circle cx="385" cy="310" r="9" fill="#f59e0b" stroke="white" stroke-width="2" class="equip-dot" data-type="light" data-name="LGT-3F-01" style="cursor:pointer;"/>
                            <circle cx="720" cy="50" r="9" fill="#f59e0b" stroke="white" stroke-width="2" class="equip-dot" data-type="light" data-name="LGT-3F-02" style="cursor:pointer;"/>

                            <!-- Access Control -->
                            <circle cx="212" cy="200" r="9" fill="#22c55e" stroke="white" stroke-width="2" class="equip-dot" data-type="access" data-name="ACD-2F-12" style="cursor:pointer;"/>
                            <circle cx="498" cy="275" r="9" fill="#22c55e" stroke="white" stroke-width="2" class="equip-dot" data-type="access" data-name="ACD-3F-01" style="cursor:pointer;filter:drop-shadow(0 0 4px #ef444480)"/>

                            <!-- Sensors -->
                            <circle cx="430" cy="50" r="7" fill="#8b5cf6" stroke="white" stroke-width="2" class="equip-dot" data-type="sensor" data-name="SENS-T01" style="cursor:pointer;"/>
                            <circle cx="570" cy="320" r="7" fill="#8b5cf6" stroke="white" stroke-width="2" class="equip-dot" data-type="sensor" data-name="SENS-H01" style="cursor:pointer;"/>

                            <!-- Icons in dots -->
                            <text x="110" y="54" text-anchor="middle" font-size="8" fill="white" font-family="sans-serif">A</text>
                            <text x="285" y="44" text-anchor="middle" font-size="8" fill="white" font-family="sans-serif">A</text>
                            <text x="200" y="54" text-anchor="middle" font-size="8" fill="white" font-family="sans-serif">F</text>
                            <text x="600" y="54" text-anchor="middle" font-size="8" fill="white" font-family="sans-serif">F</text>

                            <!-- Equipment Tooltip overlay (hidden by default) -->
                            <g id="equipTooltip" style="display:none;">
                                <rect id="tooltipBg" rx="6" fill="#1e293b" opacity="0.95"/>
                                <text id="tooltipText" fill="white" font-size="11" font-family="Inter,sans-serif"/>
                            </g>
                        </svg>
                    </div>
                </div>

                <!-- Legend -->
                <div style="padding:12px 16px;border-top:1px solid var(--border);display:flex;gap:16px;flex-wrap:wrap;background:#fafbfc;">
                    <span style="font-size:11px;font-weight:600;color:var(--text-muted);">LEGEND:</span>
                    @foreach([['#3b82f6','HVAC'],['#ef4444','Fire Alarm'],['#f59e0b','Lighting'],['#22c55e','Access Control'],['#8b5cf6','Sensors']] as $l)
                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:var(--text-muted);">
                        <span style="width:10px;height:10px;border-radius:50%;background:{{ $l[0] }};display:inline-block;"></span>
                        {{ $l[1] }}
                    </span>
                    @endforeach
                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:var(--text-muted);">
                        <span style="width:12px;height:12px;border-radius:2px;background:#dbeafe;border:1px solid #93c5fd;display:inline-block;"></span> Office
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:var(--text-muted);">
                        <span style="width:12px;height:12px;border-radius:2px;background:#d1fae5;border:1px solid #6ee7b7;display:inline-block;"></span> Meeting
                    </span>
                    <span style="display:inline-flex;align-items:center;gap:6px;font-size:11px;color:var(--text-muted);">
                        <span style="width:12px;height:12px;border-radius:2px;background:#fee2e2;border:1px solid #fca5a5;display:inline-block;"></span> Server
                    </span>
                </div>
            </div>
        </div>

        <!-- Room List -->
        <div class="nx-card mt-3">
            <div class="nx-card-header">
                <div class="nx-card-title"><i class="fa-solid fa-door-open" style="color:var(--primary);margin-right:6px;"></i>Room List</div>
                <span class="nx-badge badge-info">{{ $selectedFloor->rooms->count() }} Rooms</span>
            </div>
            <div class="nx-card-body" style="padding:0;">
                <table class="nx-table">
                    <thead><tr><th>Room Name</th><th>Type</th><th>Area</th><th>Equipment</th></tr></thead>
                    <tbody>
                    @forelse($selectedFloor->rooms as $room)
                    <tr>
                        <td><strong>{{ $room->name }}</strong></td>
                        <td><span class="nx-badge badge-info">{{ ucfirst($room->type) }}</span></td>
                        <td>{{ $room->area ? number_format($room->area).' m²' : '-' }}</td>
                        <td>{{ $room->equipment->count() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:20px;">No rooms found</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="col-xl-3">
        <!-- Floor Summary -->
        <div class="nx-card mb-3">
            <div class="nx-card-header">
                <div class="nx-card-title">Floor Summary</div>
            </div>
            <div class="nx-card-body">
                @foreach([['fa-door-open','Rooms',$selectedFloor->rooms->count(),'blue'],['fa-microchip','Equipment',$selectedFloor->equipment->count(),'green'],['fa-bell','Active Alarms',$selectedFloor->alarms->count(),'red']] as $item)
                <div class="detail-row">
                    <span class="detail-key"><i class="fa-solid {{ $item[0] }}" style="width:16px;color:var(--{{ $item[3] }})"></i> {{ $item[1] }}</span>
                    <span class="detail-val">{{ $item[2] }}</span>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Asset Summary by Category -->
        <div class="nx-card mb-3">
            <div class="nx-card-header">
                <div class="nx-card-title">Asset Summary</div>
            </div>
            <div class="nx-card-body">
                @php
                    $catGroups = $selectedFloor->equipment->groupBy(fn($e) => $e->category?->name ?? 'Other');
                @endphp
                @foreach($catGroups as $catName => $items)
                <div class="detail-row">
                    <span class="detail-key">{{ $catName }}</span>
                    <span class="detail-val">{{ $items->count() }}</span>
                </div>
                @endforeach
                @if($catGroups->isEmpty())
                <p style="color:var(--text-muted);font-size:12px;text-align:center;">No equipment on this floor</p>
                @endif
            </div>
        </div>

        <!-- Selected Equipment Info -->
        <div class="nx-card" id="equipDetailCard" style="display:none;">
            <div class="nx-card-header">
                <div class="nx-card-title" id="equipDetailTitle">Equipment Detail</div>
            </div>
            <div class="nx-card-body" id="equipDetailBody"></div>
        </div>
    </div>
</div>

@else
<!-- No building selected -->
<div class="nx-card" style="padding:60px;text-align:center;">
    <i class="fa-solid fa-layer-group" style="font-size:64px;color:#e2e8f0;margin-bottom:20px;display:block;"></i>
    <h5 style="color:var(--text-muted);">Select a building to view floor plans</h5>
    <p style="color:var(--text-light);font-size:13px;">Choose a building from the dropdown above to explore floor plans and equipment positions.</p>
</div>
@endif

</div>
@endsection

@section('scripts')
<script>
let scale = 1, isDragging = false, startX, startY, translateX = 0, translateY = 0;
const inner = document.getElementById('floorPlanInner');

function applyTransform() {
    if (inner) inner.style.transform = `translate(${translateX}px,${translateY}px) scale(${scale})`;
}
function zoomIn() { scale = Math.min(scale + 0.15, 3); applyTransform(); }
function zoomOut() { scale = Math.max(scale - 0.15, 0.5); applyTransform(); }
function resetZoom() { scale = 1; translateX = 0; translateY = 0; applyTransform(); }

function startDrag(e) {
    isDragging = true; startX = e.clientX - translateX; startY = e.clientY - translateY;
    document.onmousemove = (e) => { if(isDragging){ translateX=e.clientX-startX; translateY=e.clientY-startY; applyTransform(); }};
    document.onmouseup = () => { isDragging = false; };
}

// Equipment dot interaction
document.querySelectorAll('.equip-dot').forEach(dot => {
    dot.addEventListener('click', function() {
        const card = document.getElementById('equipDetailCard');
        const title = document.getElementById('equipDetailTitle');
        const body = document.getElementById('equipDetailBody');
        card.style.display = '';
        title.textContent = this.dataset.name;
        body.innerHTML = `
            <div class="detail-row"><span class="detail-key">Type</span><span class="detail-val">${this.dataset.type.toUpperCase()}</span></div>
            <div class="detail-row"><span class="detail-key">Code</span><span class="detail-val">${this.dataset.name}</span></div>
            <div class="detail-row"><span class="detail-key">Status</span><span><span class="nx-badge badge-success"><i class="fa-solid fa-circle" style="font-size:6px"></i> Active</span></span></div>
            <div class="detail-row"><span class="detail-key">Health</span><span class="detail-val">95%</span></div>
            <a href="/equipment" class="nx-btn nx-btn-outline nx-btn-sm mt-2 w-100 justify-content-center"><i class="fa-solid fa-arrow-right"></i> View Details</a>
        `;
    });
});

// Room hover
document.querySelectorAll('.floor-room').forEach(room => {
    room.style.cursor = 'pointer';
    room.addEventListener('mouseenter', function() { this.style.opacity = '0.85'; });
    room.addEventListener('mouseleave', function() { this.style.opacity = '1'; });
});
</script>
@endsection
