@extends('layouts.app')

@section('title', 'Create Equipment - Nexus BMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0 text-white">Create Equipment</h2>
        <p class="text-muted small mb-0">Register a new device or system</p>
    </div>
    <a href="{{ route('equipment.index') }}" class="nx-btn nx-btn-outline">
        <i class="fa-solid fa-arrow-left me-2"></i>Back
    </a>
</div>

<div class="nx-card p-4" style="max-width: 900px;">
    <form action="{{ route('equipment.store') }}" method="POST">
        @csrf

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
                <label class="form-label">Code <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
            </div>

            <div class="col-md-8">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Manufacturer</label>
                <input type="text" name="manufacturer" class="form-control" value="{{ old('manufacturer') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Model</label>
                <input type="text" name="model_number" class="form-control" value="{{ old('model_number') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Category <span class="text-danger">*</span></label>
                <select name="category_id" class="form-select" required>
                    <option value="">&mdash; Select Category &mdash;</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Building <span class="text-danger">*</span></label>
                <select name="building_id" id="buildingSelect" class="form-select" required>
                    <option value="">&mdash; Select Building &mdash;</option>
                    @foreach ($buildings as $b)
                        <option value="{{ $b->id }}" {{ old('building_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Floor</label>
                <select name="floor_id" id="floor_id" class="form-select">
                    <option value="">&mdash; Select Floor &mdash;</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="offline" {{ old('status') === 'offline' ? 'selected' : '' }}>Offline</option>
                    <option value="maintenance" {{ old('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Health Score</label>
                <input type="number" name="health_score" min="0" max="100" class="form-control" value="{{ old('health_score', 100) }}">
            </div>

            <div class="col-md-12">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="nx-btn nx-btn-primary">
                <i class="fa-solid fa-save me-2"></i>Save Equipment
            </button>
            <a href="{{ route('equipment.index') }}" class="nx-btn nx-btn-outline">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const FLOORS_BY_BUILDING = @json($buildings->mapWithKeys(fn($b) => [$b->id => $b->floors->map(fn($f) => ['id'=>$f->id,'name'=>$f->name])]));
const buildingSel = document.getElementById('buildingSelect');
const floorSel = document.getElementById('floor_id');
buildingSel?.addEventListener('change', () => {
    const floors = FLOORS_BY_BUILDING[buildingSel.value] || [];
    floorSel.innerHTML = '<option value="">— Select Floor —</option>';
    floors.forEach(f => {
        const o = document.createElement('option');
        o.value = f.id; o.textContent = f.name;
        floorSel.appendChild(o);
    });
});
</script>
@endpush
