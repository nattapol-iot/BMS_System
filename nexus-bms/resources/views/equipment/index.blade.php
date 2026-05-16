@extends('layouts.app')
@section('title', 'Equipment')
@section('page-title', 'Equipment / อุปกรณ์')
@section('page-subtitle', 'Monitor, manage, and control all building equipment')

@section('content')
<div class="fade-in">

<!-- STATS ROW -->
<div class="row g-3 mb-3 equipment-stats">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-microchip"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ number_format($totalEquipment) }}</div>
                <div class="stat-label">Total Equipment</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ number_format($activeDevices) }}</div>
                <div class="stat-label">Active Devices</div>
                <div class="stat-trend up">
                    <i class="fa-solid fa-arrow-up"></i>
                    {{ $totalEquipment > 0 ? round($activeDevices/$totalEquipment*100) : 0 }}% operational
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-screwdriver-wrench"></i></div>
            <div class="stat-content">
                <div class="stat-value">{{ $maintenanceDue }}</div>
                <div class="stat-label">Maintenance Due</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon slate"><i class="fa-solid fa-plug-circle-xmark"></i></div>
            <div class="stat-content">
                <div class="stat-value" style="color:var(--danger)">{{ $offlineDevices }}</div>
                <div class="stat-label">Offline Devices</div>
            </div>
        </div>
    </div>
</div>

<!-- FILTER BAR -->
<div class="filter-bar mb-3">
    <form method="GET" action="{{ route('equipment.index') }}" class="d-flex align-items-center gap-3 flex-wrap w-100" id="filterForm">
        <div style="position:relative;flex:1;min-width:200px;">
            <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px;"></i>
            <input type="text" name="search" class="nx-input" style="padding-left:32px;" placeholder="Search equipment, code..." value="{{ request('search') }}">
        </div>
        <select name="building_id" class="nx-select" style="width:160px;">
            <option value="">All Buildings</option>
            @foreach($buildings as $b)
            <option value="{{ $b->id }}" {{ request('building_id')==$b->id?'selected':'' }}>{{ $b->name }}</option>
            @endforeach
        </select>
        <select name="status" class="nx-select" style="width:130px;">
            <option value="">All Status</option>
            <option value="active" {{ request('status')==='active'?'selected':'' }}>Active</option>
            <option value="maintenance" {{ request('status')==='maintenance'?'selected':'' }}>Maintenance</option>
            <option value="offline" {{ request('status')==='offline'?'selected':'' }}>Offline</option>
            <option value="inactive" {{ request('status')==='inactive'?'selected':'' }}>Inactive</option>
        </select>
        <button type="submit" class="nx-btn nx-btn-primary nx-btn-sm"><i class="fa-solid fa-filter"></i> Filter</button>
        <a href="{{ route('equipment.index') }}" class="nx-btn nx-btn-outline nx-btn-sm">Reset</a>
        <button type="button" class="nx-btn nx-btn-outline nx-btn-sm ms-auto"><i class="fa-solid fa-file-export"></i> Export</button>
        <a href="{{ route('equipment.create') }}" class="nx-btn nx-btn-primary nx-btn-sm">
            <i class="fa-solid fa-plus"></i> Add Equipment
        </a>
    </form>
</div>

<!-- CATEGORY CHIPS -->
<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
    <a href="{{ route('equipment.index') }}" class="nx-chip {{ !request('category_id') ? 'active' : '' }}" data-group="cat">
        <i class="fa-solid fa-border-all"></i> All
    </a>
    @foreach($categories as $cat)
    <a href="{{ route('equipment.index', array_merge(request()->all(), ['category_id'=>$cat->id])) }}"
       class="nx-chip {{ request('category_id')==$cat->id ? 'active' : '' }}" data-group="cat"
       style="{{ request('category_id')==$cat->id ? "background:{$cat->color};border-color:{$cat->color};color:white;" : '' }}">
        <i class="fa-solid {{ $cat->icon }}"></i> {{ $cat->name }}
    </a>
    @endforeach
</div>

<!-- MAIN CONTENT -->
<div class="row g-3">
    <!-- EQUIPMENT TABLE -->
    <div class="{{ $selectedEquipment ? 'col-xl-8' : 'col-12' }}">
        <div class="nx-card">
            <div class="nx-card-header">
                <div class="nx-card-title"><i class="fa-solid fa-list" style="color:var(--primary);margin-right:6px;"></i>Equipment List</div>
                <span style="font-size:12px;color:var(--text-muted);">{{ $equipment->total() }} items</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="nx-table">
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" style="accent-color:var(--primary);"></th>
                            <th>Equipment ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th style="width:100px;">Health</th>
                            <th>Runtime</th>
                            <th style="width:100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($equipment as $eq)
                        <tr style="cursor:pointer;" onclick="window.location='{{ route('equipment.index', array_merge(request()->all(), ['detail'=>$eq->id])) }}'">
                            <td onclick="event.stopPropagation()"><input type="checkbox" style="accent-color:var(--primary);"></td>
                            <td><span style="font-family:monospace;font-size:12px;color:var(--primary);font-weight:600;">{{ $eq->code }}</span></td>
                            <td>
                                <div style="font-weight:600;font-size:13px;">{{ $eq->name }}</div>
                                <div style="font-size:11px;color:var(--text-muted);">{{ $eq->manufacturer ?? '-' }} {{ $eq->model_number ?? '' }}</div>
                            </td>
                            <td>
                                <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:500;">
                                    <i class="fa-solid {{ $eq->category?->icon ?? 'fa-cog' }}" style="color:{{ $eq->category?->color ?? '#3b82f6' }};"></i>
                                    {{ $eq->category?->name ?? '-' }}
                                </span>
                            </td>
                            <td>
                                <div style="font-size:12px;">{{ $eq->building?->name }}</div>
                                <div style="font-size:11px;color:var(--text-muted);">{{ $eq->floor ? 'Floor '.$eq->floor->floor_number : '-' }}</div>
                            </td>
                            <td>
                                <span class="nx-badge {{ $eq->status_badge_class }}">
                                    <i class="fa-solid fa-circle" style="font-size:6px;"></i>
                                    {{ ucfirst($eq->status) }}
                                </span>
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <div class="health-bar" style="flex:1;">
                                        <div class="health-fill {{ $eq->health_score >= 80 ? 'good' : ($eq->health_score >= 50 ? 'warn' : 'crit') }}" style="width:{{ $eq->health_score }}%"></div>
                                    </div>
                                    <span style="font-size:11px;font-weight:600;color:{{ $eq->health_score >= 80 ? 'var(--success)' : ($eq->health_score >= 50 ? 'var(--warning)' : 'var(--danger)') }};min-width:28px;">{{ $eq->health_score }}%</span>
                                </div>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted);">{{ number_format($eq->runtime_hours, 0) }} h</td>
                            <td onclick="event.stopPropagation()">
                                <div class="d-flex gap-1">
                                    <a href="{{ route('equipment.index', array_merge(request()->all(), ['detail'=>$eq->id])) }}" class="nx-btn nx-btn-ghost nx-btn-icon nx-btn-sm" title="View"><i class="fa-solid fa-eye"></i></a>
                                    <a href="{{ route('equipment.edit', $eq->id) }}" class="nx-btn nx-btn-ghost nx-btn-icon nx-btn-sm" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="9" style="text-align:center;padding:40px;color:var(--text-muted);">No equipment found</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="nx-card-footer d-flex justify-content-between align-items-center">
                <span style="font-size:12px;color:var(--text-muted);">Showing {{ $equipment->firstItem() }}-{{ $equipment->lastItem() }} of {{ $equipment->total() }}</span>
                {{ $equipment->appends(request()->all())->links() }}
            </div>
        </div>
    </div>

    <!-- DETAIL PANEL -->
    @if($selectedEquipment)
    <div class="col-xl-4">
        <div class="detail-panel" style="position:sticky;top:calc(64px + 24px);">
            <div class="detail-panel-header">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid {{ $selectedEquipment->category?->icon ?? 'fa-cog' }}" style="font-size:18px;"></i>
                    </div>
                    <div>
                        <div style="font-size:11px;opacity:0.6;">{{ $selectedEquipment->code }}</div>
                        <div style="font-size:16px;font-weight:700;">{{ $selectedEquipment->name }}</div>
                    </div>
                </div>
                <div style="margin-top:10px;">
                    <span class="nx-badge {{ $selectedEquipment->status_badge_class }}">{{ ucfirst($selectedEquipment->status) }}</span>
                </div>
            </div>
            <div class="detail-panel-body">
                <!-- Health Score -->
                <div style="text-align:center;padding:16px 0;border-bottom:1px solid var(--border);margin-bottom:16px;">
                    <div style="position:relative;display:inline-block;">
                        <svg width="100" height="100" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="40" fill="none" stroke="#f1f5f9" stroke-width="8"/>
                            <circle cx="50" cy="50" r="40" fill="none"
                                stroke="{{ $selectedEquipment->health_score >= 80 ? '#22c55e' : ($selectedEquipment->health_score >= 50 ? '#f59e0b' : '#ef4444') }}"
                                stroke-width="8" stroke-linecap="round"
                                stroke-dasharray="{{ round($selectedEquipment->health_score * 2.51) }} 251"
                                transform="rotate(-90 50 50)"/>
                        </svg>
                        <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                            <span style="font-size:20px;font-weight:800;color:var(--text);">{{ $selectedEquipment->health_score }}</span>
                            <span style="font-size:10px;color:var(--text-muted);">Health</span>
                        </div>
                    </div>
                    <div style="font-size:12px;font-weight:600;color:{{ $selectedEquipment->health_score >= 80 ? '#22c55e' : '#f59e0b' }};margin-top:4px;">
                        {{ $selectedEquipment->health_score >= 80 ? 'Healthy' : ($selectedEquipment->health_score >= 50 ? 'Fair' : 'Critical') }}
                    </div>
                </div>

                @foreach([
                    ['fa-building','Building',$selectedEquipment->building?->name ?? '-'],
                    ['fa-layer-group','Floor',$selectedEquipment->floor ? 'Floor '.$selectedEquipment->floor->floor_number : '-'],
                    ['fa-industry','Manufacturer',$selectedEquipment->manufacturer ?? '-'],
                    ['fa-barcode','Model',$selectedEquipment->model_number ?? '-'],
                    ['fa-wifi','Protocol',$selectedEquipment->protocol],
                    ['fa-clock','Runtime',number_format($selectedEquipment->runtime_hours).' hrs'],
                    ['fa-calendar','Installed',$selectedEquipment->installation_date?->format('d M Y') ?? '-'],
                    ['fa-shield','Warranty',$selectedEquipment->warranty_expiry?->format('d M Y') ?? '-'],
                ] as $row)
                <div class="detail-row">
                    <span class="detail-key"><i class="fa-solid {{ $row[0] }}" style="width:14px;margin-right:4px;"></i>{{ $row[1] }}</span>
                    <span class="detail-val" style="font-size:12px;">{{ $row[2] }}</span>
                </div>
                @endforeach

                @if($selectedEquipment->notes)
                <div style="margin-top:12px;padding:10px;background:#f8fafc;border-radius:6px;font-size:12px;color:var(--text-muted);">
                    {{ $selectedEquipment->notes }}
                </div>
                @endif

                <div style="margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;">
                    <a href="{{ route('equipment.edit', $selectedEquipment->id) }}" class="nx-btn nx-btn-primary nx-btn-sm"><i class="fa-solid fa-pen"></i> Edit</a>
                    <a href="{{ route('alarms.index', ['search'=>$selectedEquipment->code]) }}" class="nx-btn nx-btn-outline nx-btn-sm"><i class="fa-solid fa-bell"></i> Alarms</a>
                </div>

                <!-- Recent Alarms -->
                @if($selectedEquipment->alarms->isNotEmpty())
                <div style="margin-top:16px;">
                    <div style="font-size:11px;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px;">Recent Faults</div>
                    @foreach($selectedEquipment->alarms->take(3) as $alarm)
                    <div style="padding:8px;background:#fef2f2;border-radius:6px;margin-bottom:6px;border-left:3px solid {{ $alarm->severity==='critical'?'#ef4444':'#f59e0b' }};">
                        <div style="font-size:11px;font-weight:600;color:var(--danger);">{{ Str::limit($alarm->description, 50) }}</div>
                        <div style="font-size:10px;color:var(--text-muted);margin-top:2px;">{{ $alarm->triggered_at?->diffForHumans() }}</div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>

</div>
@endsection
