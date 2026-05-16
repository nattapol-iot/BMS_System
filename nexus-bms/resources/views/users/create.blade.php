@extends('layouts.app')

@section('title', 'Create User - Nexus BMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0 text-white">Create User</h2>
        <p class="text-muted small mb-0">Add a new platform user</p>
    </div>
    <a href="{{ route('users.index') }}" class="nx-btn nx-btn-outline">
        <i class="fa-solid fa-arrow-left me-2"></i>Back
    </a>
</div>

<div class="nx-card p-4" style="max-width: 900px;">
    <form action="{{ route('users.store') }}" method="POST">
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
            <div class="col-md-6">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" minlength="8" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select">
                    <option value="">&mdash; No Role &mdash;</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Department</label>
                <input type="text" name="department" class="form-control" value="{{ old('department') }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="locked" {{ old('status') === 'locked' ? 'selected' : '' }}>Locked</option>
                </select>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <button type="submit" class="nx-btn nx-btn-primary">
                <i class="fa-solid fa-save me-2"></i>Save User
            </button>
            <a href="{{ route('users.index') }}" class="nx-btn nx-btn-outline">Cancel</a>
        </div>
    </form>
</div>
@endsection
