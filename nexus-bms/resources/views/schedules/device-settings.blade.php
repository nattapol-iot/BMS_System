@extends('layouts.app')

@section('title', __('menu.device_time_setting') ?? 'Device Time Setting')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Section Header --}}
    <div class="section-header d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-0 fw-bold text-white">
                <i class="fa-solid fa-sliders me-2" style="color:var(--nx-cyan)"></i>
                {{ __('menu.device_time_setting') ?? 'Device Time Setting' }}
            </h4>
            <small class="text-muted">{{ __('menu.device_settings_sub') ?? 'Configure on/off time windows per device for each schedule' }}</small>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <div class="d-flex gap-2 mb-4">
        <a href="{{ route('schedules.index') }}"
           class="nx-chip">
            <i class="fa-solid fa-list-ul me-1"></i>
            {{ __('menu.schedule_overview') ?? 'Schedule Overview' }}
        </a>
        <a href="{{ route('schedules.calendar') }}"
           class="nx-chip">
            <i class="fa-solid fa-calendar-days me-1"></i>
            {{ __('menu.calendar_view') ?? 'Calendar View' }}
        </a>
        <a href="{{ route('schedules.device-settings') }}"
           class="nx-chip nx-chip-active">
            <i class="fa-solid fa-sliders me-1"></i>
            {{ __('menu.device_time_setting') ?? 'Device Time Setting' }}
        </a>
    </div>

    <div class="row g-3">
        {{-- Left Panel: Schedule List --}}
        <div class="col-xl-3 col-lg-4">
            <div class="nx-card h-100">
                <div class="nx-card-header">
                    <i class="fa-solid fa-calendar-check me-2" style="color:var(--nx-cyan)"></i>
                    {{ __('menu.schedules') ?? 'Schedules' }}
                </div>
                <div class="nx-card-body p-0">
                    @if($schedules->isEmpty())
                        <div class="text-center py-4 text-muted small">
                            <i class="fa-solid fa-calendar-xmark d-block mb-2 opacity-40"></i>
                            No schedules available.
                        </div>
                    @else
                        <div class="list-group list-group-flush" style="background:transparent;">
                            @foreach($schedules as $s)
                            @php
                                $isSelected = optional($selectedSchedule)->id == $s->id;
                                $typeColors = [
                                    'hvac'           => '#06b6d4',
                                    'lighting'       => '#f59e0b',
                                    'access_control' => '#10b981',
                                    'general'        => '#6b7280',
                                ];
                                $tc = $typeColors[$s->schedule_type] ?? '#6b7280';
                            @endphp
                            <a href="{{ route('schedules.device-settings', ['schedule_id' => $s->id]) }}"
                               class="list-group-item list-group-item-action d-flex align-items-center gap-2 py-3"
                               style="
                                   background:{{ $isSelected ? 'rgba(29,78,216,0.18)' : 'transparent' }};
                                   border-color:rgba(255,255,255,0.06);
                                   color:{{ $isSelected ? '#fff' : '#8898aa' }};
                                   border-left:3px solid {{ $isSelected ? '#1d4ed8' : 'transparent' }};
                               ">
                                <i class="fa-solid fa-circle" style="font-size:.4rem;color:{{ $tc }};margin-top:2px;flex-shrink:0;"></i>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-semibold text-truncate" style="font-size:.875rem;color:{{ $isSelected ? '#fff' : '#d1d5db' }}">
                                        {{ $s->name }}
                                    </div>
                                    <div style="font-size:.72rem;color:#6b7280;">
                                        {{ \Carbon\Carbon::parse($s->start_time)->format('H:i') }}
                                        –
                                        {{ \Carbon\Carbon::parse($s->end_time)->format('H:i') }}
                                    </div>
                                </div>
                                @if($s->is_active)
                                    <span style="width:7px;height:7px;background:#10b981;border-radius:50%;flex-shrink:0;"></span>
                                @endif
                            </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Panel: Device List --}}
        <div class="col-xl-9 col-lg-8">
            @if(!$selectedSchedule)
                <div class="nx-card">
                    <div class="nx-card-body text-center py-5 text-muted">
                        <i class="fa-solid fa-hand-pointer fa-2x mb-3 d-block opacity-40"></i>
                        <p class="mb-0">{{ __('menu.select_schedule_prompt') ?? 'Select a schedule from the left panel to manage its devices.' }}</p>
                    </div>
                </div>
            @else
            <div class="nx-card">
                <div class="nx-card-header d-flex align-items-center justify-content-between">
                    <span>
                        <i class="fa-solid fa-microchip me-2" style="color:var(--nx-cyan)"></i>
                        {{ $selectedSchedule->name }}
                        <span class="text-muted small ms-2">
                            — {{ \Carbon\Carbon::parse($selectedSchedule->start_time)->format('H:i') }}
                            to {{ \Carbon\Carbon::parse($selectedSchedule->end_time)->format('H:i') }}
                        </span>
                    </span>
                    <button type="button" class="nx-btn nx-btn-primary" data-bs-toggle="modal" data-bs-target="#addDeviceModal"
                            style="font-size:.8rem;padding:6px 14px;">
                        <i class="fa-solid fa-plus me-1"></i>
                        {{ __('menu.add_device') ?? 'Add Device' }}
                    </button>
                </div>
                <div class="nx-card-body p-0">
                    <form method="POST" action="{{ route('schedules.device-settings.save', $selectedSchedule->id) }}" id="device-settings-form">
                        @csrf
                        @method('PUT')

                        @if($devices->isEmpty())
                            <div class="text-center py-5 text-muted">
                                <i class="fa-solid fa-plug-circle-xmark fa-2x mb-3 d-block opacity-40"></i>
                                <p>{{ __('menu.no_devices_in_schedule') ?? 'No devices assigned to this schedule yet.' }}</p>
                                <button type="button" class="nx-btn nx-btn-outline" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
                                    <i class="fa-solid fa-plus me-1"></i>
                                    {{ __('menu.add_first_device') ?? 'Add First Device' }}
                                </button>
                            </div>
                        @else
                        <div class="table-responsive">
                            <table class="nx-table w-100">
                                <thead>
                                    <tr>
                                        <th>{{ __('menu.equipment') ?? 'Equipment' }}</th>
                                        <th>{{ __('menu.category') ?? 'Category' }}</th>
                                        <th>{{ __('menu.building') ?? 'Building' }}</th>
                                        <th>{{ __('menu.floor') ?? 'Floor' }}</th>
                                        <th>{{ __('menu.on_time') ?? 'On Time' }}</th>
                                        <th>{{ __('menu.off_time') ?? 'Off Time' }}</th>
                                        <th>{{ __('menu.days') ?? 'Days' }}</th>
                                        <th class="text-center">{{ __('menu.remove') ?? 'Remove' }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($devices as $dev)
                                    @php
                                        $eq = $dev->equipment;
                                        $devDays = is_array($dev->days) ? $dev->days : json_decode($dev->days ?? '[]', true);
                                        $dayMap  = [0=>'Su',1=>'Mo',2=>'Tu',3=>'We',4=>'Th',5=>'Fr',6=>'Sa'];
                                    @endphp
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-white">{{ optional($eq)->name ?? '—' }}</div>
                                            <div class="text-muted" style="font-size:.72rem;">ID: {{ $dev->equipment_id }}</div>
                                        </td>
                                        <td class="text-muted small">
                                            {{ optional(optional($eq)->category)->name ?? '—' }}
                                        </td>
                                        <td class="text-muted small">
                                            {{ optional(optional(optional($eq)->floor)->building)->name ?? '—' }}
                                        </td>
                                        <td class="text-muted small">
                                            {{ optional(optional($eq)->floor)->name ?? '—' }}
                                        </td>
                                        <td>
                                            <input type="time"
                                                   name="devices[{{ $dev->id }}][on_time]"
                                                   value="{{ $dev->on_time ?? '' }}"
                                                   class="form-control form-control-sm"
                                                   style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;width:110px;">
                                        </td>
                                        <td>
                                            <input type="time"
                                                   name="devices[{{ $dev->id }}][off_time]"
                                                   value="{{ $dev->off_time ?? '' }}"
                                                   class="form-control form-control-sm"
                                                   style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;width:110px;">
                                        </td>
                                        <td>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach($dayMap as $num => $abbr)
                                                    @php $active = in_array($num, $devDays) || in_array($abbr, $devDays); @endphp
                                                    <label style="cursor:pointer;">
                                                        <input type="checkbox"
                                                               name="devices[{{ $dev->id }}][days][]"
                                                               value="{{ $num }}"
                                                               {{ $active ? 'checked' : '' }}
                                                               class="d-none day-check"
                                                               data-id="{{ $dev->id }}">
                                                        <span class="nx-chip day-label {{ $active ? 'day-active' : '' }}"
                                                              style="font-size:.65rem;padding:1px 5px;cursor:pointer;{{ $active ? 'background:rgba(29,78,216,0.25);color:#93c5fd;' : 'background:rgba(255,255,255,0.05);color:#4b5563;' }}">
                                                            {{ $abbr }}
                                                        </span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <button type="button"
                                                    class="nx-btn"
                                                    style="padding:4px 10px;background:rgba(239,68,68,0.12);color:#ef4444;border:1px solid rgba(239,68,68,0.3);"
                                                    onclick="removeDevice(this, {{ $dev->id }})">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                            <input type="hidden" name="devices[{{ $dev->id }}][_remove]" value="0" class="remove-flag">
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="d-flex justify-content-end gap-2 p-3 border-top" style="border-color:rgba(255,255,255,0.06)!important;">
                            <button type="button" class="nx-btn nx-btn-outline" onclick="history.back()">
                                {{ __('menu.cancel') ?? 'Cancel' }}
                            </button>
                            <button type="submit" class="nx-btn nx-btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i>
                                {{ __('menu.save_settings') ?? 'Save Settings' }}
                            </button>
                        </div>
                        @endif
                    </form>
                </div>
            </div>

            {{-- Add Device Modal --}}
            <div class="modal fade" id="addDeviceModal" tabindex="-1" aria-labelledby="addDeviceModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content" style="background:#0f1e38;border:1px solid rgba(255,255,255,0.1);">
                        <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,0.08);">
                            <h5 class="modal-title text-white" id="addDeviceModalLabel">
                                <i class="fa-solid fa-plus-circle me-2" style="color:var(--nx-cyan)"></i>
                                {{ __('menu.add_device_to_schedule') ?? 'Add Device to Schedule' }}
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label text-white-50 small">
                                    {{ __('menu.search_equipment') ?? 'Search Equipment' }}
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text" style="background:#1a2a4a;border-color:#2d4a7a;">
                                        <i class="fa-solid fa-search text-muted"></i>
                                    </span>
                                    <input type="text" id="equip-search" class="form-control"
                                           placeholder="{{ __('menu.search_equipment_ph') ?? 'Search by name or category...' }}"
                                           style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;"
                                           oninput="filterEquipment(this.value)">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-white-50 small">
                                    {{ __('menu.filter_category') ?? 'Filter by Category' }}
                                </label>
                                <select id="cat-filter" class="form-select form-select-sm"
                                        style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;"
                                        onchange="filterEquipment(document.getElementById('equip-search').value)">
                                    <option value="">— {{ __('menu.all_categories') ?? 'All Categories' }} —</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div id="equip-list" style="max-height:300px;overflow-y:auto;">
                                <div class="text-center text-muted py-3">
                                    <i class="fa-solid fa-keyboard me-1"></i>
                                    {{ __('menu.type_to_search') ?? 'Type to search equipment...' }}
                                </div>
                            </div>

                            <div id="selected-equip" class="mt-3" style="display:none;">
                                <div class="alert" style="background:rgba(29,78,216,0.15);border:1px solid rgba(29,78,216,0.3);color:#93c5fd;">
                                    <i class="fa-solid fa-check-circle me-1"></i>
                                    <span id="selected-equip-name"></span>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,0.08);">
                            <input type="hidden" id="modal-equip-id" value="">
                            <button type="button" class="nx-btn nx-btn-outline" data-bs-dismiss="modal">
                                {{ __('menu.cancel') ?? 'Cancel' }}
                            </button>
                            <button type="button" class="nx-btn nx-btn-primary" onclick="addDeviceToSchedule()" id="btn-add-device" disabled>
                                <i class="fa-solid fa-plus me-1"></i>
                                {{ __('menu.add') ?? 'Add' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

</div>

@push('scripts')
<script>
// Toggle day chip styling
document.querySelectorAll('.day-check').forEach(cb => {
    cb.addEventListener('change', function () {
        const label = this.nextElementSibling;
        if (this.checked) {
            label.style.background = 'rgba(29,78,216,0.25)';
            label.style.color = '#93c5fd';
        } else {
            label.style.background = 'rgba(255,255,255,0.05)';
            label.style.color = '#4b5563';
        }
    });
});

// Remove device row
function removeDevice(btn, devId) {
    const row = btn.closest('tr');
    row.style.opacity = '0.3';
    row.querySelectorAll('input,select').forEach(el => el.disabled = true);
    const flag = row.querySelector('.remove-flag');
    if (flag) flag.value = '1';
    btn.innerHTML = '<i class="fa-solid fa-rotate-left"></i>';
    btn.style.background = 'rgba(107,114,128,0.15)';
    btn.style.color = '#9ca3af';
    btn.onclick = function () { restoreDevice(btn, devId, row); };
}

function restoreDevice(btn, devId, row) {
    row.style.opacity = '1';
    row.querySelectorAll('input,select').forEach(el => el.disabled = false);
    const flag = row.querySelector('.remove-flag');
    if (flag) flag.value = '0';
    btn.innerHTML = '<i class="fa-solid fa-xmark"></i>';
    btn.style.background = 'rgba(239,68,68,0.12)';
    btn.style.color = '#ef4444';
    btn.onclick = function () { removeDevice(btn, devId); };
}

// Equipment search
let selectedEquipId = null;

function filterEquipment(query) {
    const catId = document.getElementById('cat-filter').value;
    if (!query && !catId) {
        document.getElementById('equip-list').innerHTML =
            '<div class="text-center text-muted py-3"><i class="fa-solid fa-keyboard me-1"></i>Type to search equipment...</div>';
        return;
    }
    const url = new URL('{{ route("api.equipment.search") ?? "/api/equipment/search" }}', window.location.origin);
    if (query) url.searchParams.set('q', query);
    if (catId) url.searchParams.set('category_id', catId);
    url.searchParams.set('schedule_id', '{{ optional($selectedSchedule)->id ?? "" }}');

    fetch(url)
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('equip-list');
            if (!data.length) {
                list.innerHTML = '<div class="text-center text-muted py-3">No equipment found.</div>';
                return;
            }
            list.innerHTML = data.map(eq => `
                <div class="list-group-item list-group-item-action equip-item"
                     data-id="${eq.id}" data-name="${eq.name}"
                     onclick="selectEquipment(${eq.id}, '${eq.name.replace(/'/g,"\\'")}', '${eq.category ?? ''}')"
                     style="background:rgba(255,255,255,0.03);border-color:rgba(255,255,255,0.06);color:#d1d5db;cursor:pointer;margin-bottom:2px;border-radius:6px;">
                    <div class="fw-semibold">${eq.name}</div>
                    <small class="text-muted">${eq.category ?? ''} &bull; ${eq.building ?? ''} ${eq.floor ? '/ ' + eq.floor : ''}</small>
                </div>
            `).join('');
        })
        .catch(() => {
            document.getElementById('equip-list').innerHTML =
                '<div class="text-center text-danger py-3">Error loading equipment list.</div>';
        });
}

function selectEquipment(id, name, cat) {
    selectedEquipId = id;
    document.getElementById('modal-equip-id').value = id;
    document.getElementById('selected-equip-name').textContent = name + (cat ? ' (' + cat + ')' : '');
    document.getElementById('selected-equip').style.display = 'block';
    document.getElementById('btn-add-device').disabled = false;

    document.querySelectorAll('.equip-item').forEach(el => {
        el.style.background = el.dataset.id == id
            ? 'rgba(29,78,216,0.2)'
            : 'rgba(255,255,255,0.03)';
        el.style.borderColor = el.dataset.id == id
            ? 'rgba(29,78,216,0.4)'
            : 'rgba(255,255,255,0.06)';
    });
}

function addDeviceToSchedule() {
    if (!selectedEquipId) return;
    const schedId = '{{ optional($selectedSchedule)->id ?? "" }}';
    nexusPost(`/schedules/${schedId}/devices`, { equipment_id: selectedEquipId })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('addDeviceModal')).hide();
                window.location.reload();
            } else {
                alert(data.message || 'Failed to add device.');
            }
        })
        .catch(() => alert('Network error.'));
}
</script>
@endpush
@endsection
