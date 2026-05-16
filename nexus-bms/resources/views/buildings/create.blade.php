@extends('layouts.app')

@section('title', 'Create Building - Nexus BMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0 text-white">Create Building</h2>
        <p class="text-muted small mb-0">Add a new building to the system</p>
    </div>
    <a href="{{ route('buildings.index') }}" class="nx-btn nx-btn-outline">
        <i class="fa-solid fa-arrow-left me-2"></i>Back
    </a>
</div>

<div class="nx-card p-4" style="max-width: 900px;">
    <form action="{{ route('buildings.store') }}" method="POST">
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
            <div class="col-md-3">
                <label class="form-label">Code <span class="text-danger">*</span></label>
                <input type="text" name="code" class="form-control" value="{{ old('code') }}" required>
            </div>

            <div class="col-md-5">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Thai Name</label>
                <input type="text" name="name_th" class="form-control" value="{{ old('name_th') }}">
            </div>

            <div class="col-md-12">
                <label class="form-label">Address</label>
                <textarea name="address" rows="2" class="form-control">{{ old('address') }}</textarea>
            </div>

            <div class="col-md-4">
                <label class="form-label">City</label>
                <input type="text" name="city" class="form-control" value="{{ old('city') }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Floors Count <span class="text-danger">*</span></label>
                <input type="number" name="floors_count" min="1" class="form-control" value="{{ old('floors_count', 1) }}" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Total Area (m&sup2;)</label>
                <input type="number" step="0.01" name="total_area" class="form-control" value="{{ old('total_area') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
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
</div>
@endsection
