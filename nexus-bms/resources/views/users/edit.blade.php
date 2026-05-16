@extends('layouts.app')

@section('title', 'Edit User - Nexus BMS')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-0 text-white">Edit User</h2>
        <p class="text-muted small mb-0">{{ $user->name }} &mdash; {{ $user->email }}</p>
    </div>
    <a href="{{ route('users.index') }}" class="nx-btn nx-btn-outline">
        <i class="fa-solid fa-arrow-left me-2"></i>Back
    </a>
</div>

<div class="nx-card p-4" style="max-width: 900px;">
    <form action="{{ route('users.update', $user) }}" method="POST">
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
            <div class="col-md-6">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" minlength="8" placeholder="Leave blank to keep current password">
            </div>

            <div class="col-md-6">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" class="form-control" minlength="8" placeholder="Leave blank to keep current password">
            </div>

            <div class="col-md-4">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select">
                    <option value="">&mdash; No Role &mdash;</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Department</label>
                <input type="text" name="department" class="form-control" value="{{ old('department', $user->department) }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
            </div>

            <div class="col-md-6">
                <label class="form-label">Status <span class="text-danger">*</span></label>
                <select name="status" class="form-select" required>
                    <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="locked" {{ old('status', $user->status) === 'locked' ? 'selected' : '' }}>Locked</option>
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

    @if ($user->id !== auth()->id())
        <hr class="my-4" style="border-color: rgba(255,255,255,0.1);">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="text-danger mb-1">Danger Zone</h6>
                <p class="text-muted small mb-0">Permanently delete this user account.</p>
            </div>
            <form action="{{ route('users.destroy', $user) }}" method="POST"
                  onsubmit="return confirm('Delete this user? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="nx-btn" style="background:var(--nx-red); color:#fff;">
                    <i class="fa-solid fa-trash me-2"></i>Delete User
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
