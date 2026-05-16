@extends('layouts.app')

@section('title', 'Edit Equipment - Nexus BMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0 text-white">Edit Equipment</h2>
        <p class="text-muted small mb-0">{{ $equipment->code }} &mdash; {{ $equipment->name }}</p>
    </div>
    <a href="{{ route('equipment.index') }}" class="nx-btn nx-btn-outline">
        <i class="fa-solid fa-arrow-left me-2"></i>Back
    </a>
</div>

<div class="nx-card p-4" style="max-width: 900px;">
    <form action="{{ route('equipment.update', $equipment) }}" method="POST">
        @csrf
        @method('PUT')

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Code</label>
                <input type="text" class="form-control" value="{{ $equipment->code }}" readonly disabled>
            </div>

            <div class="col-md-8">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $equipment->name) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Manufacturer</label>
                <input type="text" name="manufacturer" class="form-control" value="{{ old('manufacturer', $equipment->manufacturer) }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Model</label>
                <input type="text" name="model_number" class="form-control" value="{{ old('model_number', $equipment->model_number) }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select" required>
                    <option value="">&mdash; Select Category &mdash;</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $equipment->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Building <span class="text-danger">*</span></label>
                <select name="building_id" id="buildingSelect" class="form-select" required>
                    <option value="">&mdash; Select Building &mdash;</option>
                    @foreach ($buildings as $b)
                        <option value="{{ $b->id }}" {{ old('building_id', $equipment->building_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Floor</label>
                <select name="floor_id" id="floor_id" class="form-select" data-current="{{ old('floor_id', $equipment->floor_id) }}">
                    <option value="">&mdash; Select Floor &mdash;</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="active" {{ old('status', $equipment->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $equipment->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="offline" {{ old('status', $equipment->status) === 'offline' ? 'selected' : '' }}>Offline</option>
                    <option value="maintenance" {{ old('status', $equipment->status) === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Health Score</label>
                <input type="number" name="health_score" min="0" max="100" class="form-control" value="{{ old('health_score', $equipment->health_score) }}">
            </div>

            <div class="col-md-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="3" class="form-control">{{ old('notes', $equipment->notes) }}</textarea>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="nx-btn nx-btn-primary">
                <i class="fa-solid fa-save me-2"></i>Save Equipment
            </button>
            <a href="{{ route('equipment.index') }}" class="nx-btn nx-btn-outline">Cancel</a>
        </div>
    </form>

    <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h6 class="text-danger mb-1">Danger Zone</h6>
            <p class="text-muted small mb-0">Permanently delete this equipment and all its data.</p>
        </div>
        <form action="{{ route('equipment.destroy', $equipment) }}" method="POST"
              onsubmit="return confirm('Delete this equipment? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="nx-btn" style="background:var(--nx-red); color:#fff;">
                <i class="fa-solid fa-trash me-2"></i>Delete
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const FLOORS_BY_BUILDING = @json($buildings->mapWithKeys(fn($b) => [$b->id => $b->floors->map(fn($f) => ['id'=>$f->id,'name'=>$f->name])]));
const buildingSel = document.getElementById('buildingSelect');
const floorSel = document.getElementById('floor_id');

function populateFloors(selectedId) {
    const floors = FLOORS_BY_BUILDING[buildingSel.value] || [];
    floorSel.innerHTML = '<option value="">— Select Floor —</option>';
    floors.forEach(f => {
        const o = document.createElement('option');
        o.value = f.id; o.textContent = f.name;
        if (selectedId && String(selectedId) === String(f.id)) o.selected = true;
        floorSel.appendChild(o);
    });
}

buildingSel?.addEventListener('change', () => populateFloors(null));

document.addEventListener('DOMContentLoaded', () => {
    const current = floorSel?.dataset.current;
    if (buildingSel?.value) populateFloors(current);
});
</script>
@endpush
