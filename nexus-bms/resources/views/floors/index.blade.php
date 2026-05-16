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
                <div class="d-flex gap-2 align-items-center">
                    <button class="nx-btn nx-btn-outline nx-btn-sm" onclick="zoomIn()" type="button"><i class="fa-solid fa-plus"></i></button>
                    <button class="nx-btn nx-btn-outline nx-btn-sm" onclick="zoomOut()" type="button"><i class="fa-solid fa-minus"></i></button>
                    <button class="nx-btn nx-btn-outline nx-btn-sm" onclick="resetZoom()" type="button"><i class="fa-solid fa-arrows-to-circle"></i></button>
                    @if(auth()->user()?->hasPermission('floors', 'edit'))
                    <span class="text-muted small ms-2">|</span>
                    <button id="editModeBtn" class="nx-btn nx-btn-outline nx-btn-sm" onclick="toggleEditMode()" type="button"><i class="fa-solid fa-pen-to-square"></i> Edit Positions</button>
                    <button id="savePosBtn" class="nx-btn nx-btn-primary nx-btn-sm" onclick="savePositions()" type="button" style="display:none;"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                    <span id="posStatus" class="text-muted small ms-2"></span>
                    @endif
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
                            <style>
                                .equip-dot {
                                    vector-effect: non-scaling-stroke;
                                    filter: drop-shadow(0 2px 4px rgba(15, 23, 42, 0.18));
                                }
                                .equip-label {
                                    pointer-events: none;
                                    user-select: none;
                                }
                            </style>

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
                            <circle cx="58" cy="46" r="9" fill="#3b82f6" stroke="white" stroke-width="2" class="equip-dot" data-type="hvac" data-name="AHU-07" style="cursor:pointer;"/>
                            <circle cx="335" cy="42" r="9" fill="#3b82f6" stroke="white" stroke-width="2" class="equip-dot" data-type="hvac" data-name="AHU-08" style="cursor:pointer;"/>
                            <circle cx="52" cy="306" r="9" fill="#3b82f6" stroke="white" stroke-width="2" class="equip-dot" data-type="hvac" data-name="FCU-01" style="cursor:pointer;"/>

                            <!-- Fire Alarm -->
                            <circle cx="176" cy="46" r="9" fill="#ef4444" stroke="white" stroke-width="2" class="equip-dot" data-type="fire" data-name="FAP-03" style="cursor:pointer;"/>
                            <circle cx="615" cy="42" r="9" fill="#ef4444" stroke="white" stroke-width="2" class="equip-dot" data-type="fire" data-name="FAP-04" style="cursor:pointer;"/>
                            <circle cx="312" cy="306" r="9" fill="#ef4444" stroke="white" stroke-width="2" class="equip-dot" data-type="fire" data-name="FAD-01" style="cursor:pointer;"/>

                            <!-- Lighting -->
                            <circle cx="58" cy="166" r="9" fill="#f59e0b" stroke="white" stroke-width="2" class="equip-dot" data-type="light" data-name="LGT-2F-12" style="cursor:pointer;"/>
                            <circle cx="456" cy="306" r="9" fill="#f59e0b" stroke="white" stroke-width="2" class="equip-dot" data-type="light" data-name="LGT-3F-01" style="cursor:pointer;"/>
                            <circle cx="760" cy="42" r="9" fill="#f59e0b" stroke="white" stroke-width="2" class="equip-dot" data-type="light" data-name="LGT-3F-02" style="cursor:pointer;"/>

                            <!-- Access Control -->
                            <circle cx="176" cy="166" r="9" fill="#22c55e" stroke="white" stroke-width="2" class="equip-dot" data-type="access" data-name="ACD-2F-12" style="cursor:pointer;"/>
                            <circle cx="470" cy="292" r="9" fill="#22c55e" stroke="white" stroke-width="2" class="equip-dot" data-type="access" data-name="ACD-3F-01" style="cursor:pointer;"/>

                            <!-- Sensors -->
                            <circle cx="472" cy="42" r="7" fill="#8b5cf6" stroke="white" stroke-width="2" class="equip-dot" data-type="sensor" data-name="SENS-T01" style="cursor:pointer;"/>
                            <circle cx="625" cy="300" r="7" fill="#8b5cf6" stroke="white" stroke-width="2" class="equip-dot" data-type="sensor" data-name="SENS-H01" style="cursor:pointer;"/>

                            <!-- Icons in dots -->
                            <text x="58" y="50" text-anchor="middle" font-size="8" fill="white" font-family="sans-serif" class="equip-label">A</text>
                            <text x="335" y="46" text-anchor="middle" font-size="8" fill="white" font-family="sans-serif" class="equip-label">A</text>
                            <text x="52" y="310" text-anchor="middle" font-size="8" fill="white" font-family="sans-serif" class="equip-label">A</text>
                            <text x="176" y="50" text-anchor="middle" font-size="8" fill="white" font-family="sans-serif" class="equip-label">F</text>
                            <text x="615" y="46" text-anchor="middle" font-size="8" fill="white" font-family="sans-serif" class="equip-label">F</text>
                            <text x="312" y="310" text-anchor="middle" font-size="8" fill="white" font-family="sans-serif" class="equip-label">F</text>

                            <!-- Equipment Tooltip overlay (hidden by default) -->
                            <g id="equipTooltip" style="display:none;">
                                <rect id="tooltipBg" rx="6" fill="#1e293b" opacity="0.95"/>
                                <text id="tooltipText" fill="white" font-size="11" font-family="Inter,sans-serif"/>
                            </g>

                            <!-- Real Equipment from DB (positioned via x_position/y_position) -->
                            <g id="dbEquipment">
                                @php
                                    $catColors = ['HVAC'=>'#3b82f6','Fire Alarm'=>'#ef4444','Lighting'=>'#f59e0b','Access Control'=>'#22c55e','Sensors'=>'#8b5cf6','Pumps'=>'#06b6d4','Chillers'=>'#0ea5e9','Power'=>'#eab308','Security'=>'#10b981','Elevators'=>'#a855f7'];
                                    $defaultX = 100; $defaultY = 100;
                                @endphp
                                @foreach($selectedFloor->equipment as $i => $eq)
                                    @php
                                        $x = $eq->x_position ?? (80 + ($i % 8) * 90);
                                        $y = $eq->y_position ?? (60 + intdiv($i, 8) * 80);
                                        $color = $catColors[$eq->category?->name ?? ''] ?? ($eq->category?->color ?? '#64748b');
                                    @endphp
                                    <g class="db-equip" data-id="{{ $eq->id }}" data-name="{{ $eq->code }}" data-fullname="{{ $eq->name }}" data-category="{{ $eq->category?->name }}" data-status="{{ $eq->status }}" data-health="{{ $eq->health_score }}" transform="translate({{ $x }}, {{ $y }})" style="cursor:pointer;">
                                        <circle r="11" fill="{{ $color }}" stroke="white" stroke-width="2.5" class="equip-dot" style="filter:drop-shadow(0 2px 4px rgba(15,23,42,0.25));"/>
                                        <text text-anchor="middle" y="4" font-size="8" fill="white" font-weight="700" font-family="Inter,sans-serif" class="equip-label">{{ strtoupper(substr($eq->category?->name ?? 'E', 0, 1)) }}</text>
                                    </g>
                                @endforeach
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

// DB equipment click handler — show details
document.querySelectorAll('.db-equip').forEach(g => {
    g.addEventListener('click', function(ev) {
        if (editMode) return;
        ev.stopPropagation();
        const card = document.getElementById('equipDetailCard');
        const title = document.getElementById('equipDetailTitle');
        const body = document.getElementById('equipDetailBody');
        card.style.display = '';
        title.textContent = this.dataset.fullname || this.dataset.name;
        body.innerHTML = `
            <div class="detail-row"><span class="detail-key">Code</span><span class="detail-val">${this.dataset.name}</span></div>
            <div class="detail-row"><span class="detail-key">Category</span><span class="detail-val">${this.dataset.category || '-'}</span></div>
            <div class="detail-row"><span class="detail-key">Status</span><span><span class="nx-badge badge-success">${this.dataset.status}</span></span></div>
            <div class="detail-row"><span class="detail-key">Health</span><span class="detail-val">${this.dataset.health}%</span></div>
        `;
    });
});

// === FLOOR PLAN EDITOR — drag/drop equipment positions ===
let editMode = false;
const floorSvg = document.getElementById('floorSvg');
@if(isset($selectedFloor))
const FLOOR_ID = {{ $selectedFloor->id }};
const POS_UPDATE_URL = "{{ route('floors.update-positions', $selectedFloor->id) }}";
const CSRF = '{{ csrf_token() }}';
@endif

function toggleEditMode() {
    editMode = !editMode;
    const btn = document.getElementById('editModeBtn');
    const save = document.getElementById('savePosBtn');
    if (editMode) {
        btn.classList.remove('nx-btn-outline');
        btn.classList.add('nx-btn-primary');
        btn.innerHTML = '<i class="fa-solid fa-xmark"></i> Cancel';
        save.style.display = '';
        document.getElementById('floorPlanContainer').style.cursor = 'default';
        document.querySelectorAll('.db-equip').forEach(g => g.style.cursor = 'move');
    } else {
        btn.classList.add('nx-btn-outline');
        btn.classList.remove('nx-btn-primary');
        btn.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> Edit Positions';
        save.style.display = 'none';
        document.getElementById('floorPlanContainer').style.cursor = 'grab';
        document.querySelectorAll('.db-equip').forEach(g => g.style.cursor = 'pointer');
    }
}

let dragTarget = null, dragOffset = {x:0, y:0};
const dirtyEquipment = new Map(); // id → {x, y}

function getSvgPoint(evt) {
    if (!floorSvg) return {x:0, y:0};
    const pt = floorSvg.createSVGPoint();
    pt.x = evt.clientX;
    pt.y = evt.clientY;
    return pt.matrixTransform(floorSvg.getScreenCTM().inverse());
}

document.querySelectorAll('.db-equip').forEach(g => {
    g.addEventListener('mousedown', function(ev) {
        if (!editMode) return;
        ev.preventDefault();
        ev.stopPropagation();
        dragTarget = this;
        const t = this.transform.baseVal.consolidate();
        const m = t ? t.matrix : null;
        const cur = m ? {x: m.e, y: m.f} : {x:0, y:0};
        const p = getSvgPoint(ev);
        dragOffset = {x: p.x - cur.x, y: p.y - cur.y};
        this.querySelector('circle').setAttribute('stroke', '#facc15');
        this.querySelector('circle').setAttribute('stroke-width', '3.5');
    });
});

document.addEventListener('mousemove', function(ev) {
    if (!editMode || !dragTarget) return;
    const p = getSvgPoint(ev);
    const nx = Math.max(0, Math.min(800, p.x - dragOffset.x));
    const ny = Math.max(0, Math.min(500, p.y - dragOffset.y));
    dragTarget.setAttribute('transform', `translate(${nx}, ${ny})`);
    dirtyEquipment.set(parseInt(dragTarget.dataset.id), {x: Math.round(nx*100)/100, y: Math.round(ny*100)/100});
    document.getElementById('posStatus').textContent = `${dirtyEquipment.size} unsaved change(s)`;
});

document.addEventListener('mouseup', function(ev) {
    if (dragTarget) {
        dragTarget.querySelector('circle').setAttribute('stroke', 'white');
        dragTarget.querySelector('circle').setAttribute('stroke-width', '2.5');
        dragTarget = null;
    }
});

function savePositions() {
    if (dirtyEquipment.size === 0) {
        document.getElementById('posStatus').textContent = 'Nothing to save.';
        return;
    }
    const positions = Array.from(dirtyEquipment.entries()).map(([id, p]) => ({id, x: p.x, y: p.y}));
    fetch(POS_UPDATE_URL, {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'},
        body: JSON.stringify({positions})
    }).then(r => r.json()).then(res => {
        if (res.success) {
            document.getElementById('posStatus').textContent = `Saved ${res.updated} position(s).`;
            dirtyEquipment.clear();
            setTimeout(() => document.getElementById('posStatus').textContent = '', 3000);
        } else {
            document.getElementById('posStatus').textContent = 'Save failed.';
        }
    }).catch(err => {
        document.getElementById('posStatus').textContent = 'Error: ' + err.message;
    });
}
</script>
@endsection
