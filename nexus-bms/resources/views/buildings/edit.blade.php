@extends('layouts.app')

@section('title', 'Edit Building - Nexus BMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0 text-white">Edit Building</h2>
        <p class="text-muted small mb-0">{{ $building->code }} &mdash; {{ $building->name }}</p>
    </div>
    <a href="{{ route('buildings.index') }}" class="nx-btn nx-btn-outline">
        <i class="fa-solid fa-arrow-left me-2"></i>Back
    </a>
</div>

<div class="nx-card p-4" style="max-width: 900px;">
    <form action="{{ route('buildings.update', $building) }}" method="POST">
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
            <div class="col-md-3">
                <label class="form-label">Code</label>
                <input type="text" class="form-control" value="{{ $building->code }}" readonly disabled>
            </div>

            <div class="col-md-5">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $building->name) }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Thai Name</label>
                <input type="text" name="name_th" class="form-control" value="{{ old('name_th', $building->name_th) }}">
            </div>

            <div class="col-md-12">
                <label class="form-label">Address</label>
                <textarea name="address" rows="2" class="form-control">{{ old('address', $building->address) }}</textarea>
            </div>

            <div class="col-md-4">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="{{ old('city', $building->city) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Floors Count <span class="text-danger">*</span></label>
                <input type="number" name="floors_count" min="1" class="form-control" value="{{ old('floors_count', $building->floors_count) }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Total Area (m&sup2;)</label>
                <input type="number" step="0.01" name="total_area" class="form-control" value="{{ old('total_area', $building->total_area) }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="active" {{ old('status', $building->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $building->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="nx-btn nx-btn-primary">
                <i class="fa-solid fa-save me-2"></i>Save Building
            </button>
            <a href="{{ route('buildings.index') }}" class="nx-btn nx-btn-outline">Cancel</a>
        </div>
    </form>

    <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h6 class="text-danger mb-1">Danger Zone</h6>
            <p class="text-muted small mb-0">Permanently delete this building and all its data.</p>
        </div>
        <form action="{{ route('buildings.destroy', $building) }}" method="POST"
              onsubmit="return confirm('Delete this building? This cannot be undone.');">
            @csrf
            @method('DELETE')
            <button type="submit" class="nx-btn" style="background:var(--nx-red); color:#fff;">
                <i class="fa-solid fa-trash me-2"></i>Delete
            </button>
        </form>
    </div>
</div>
@endsection
