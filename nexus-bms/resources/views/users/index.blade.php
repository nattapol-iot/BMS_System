@extends('layouts.app')

@section('title', __('menu.users') ?? 'Users & Access')
@section('page-title', 'Users & Access / ผู้ใช้และสิทธิ์')
@section('page-subtitle', 'Manage system users and role-based access permissions')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Flash Messages --}}
    @if(session('success'))
    <div class="alert mb-4 d-flex align-items-center gap-2"
         style="background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.3);color:#6ee7b7;border-radius:8px;">
        <i class="fa-solid fa-circle-check"></i>
        {{ session('success') }}
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"></button>
    </div>
    @endif
    @if(session('error'))
    <div class="alert mb-4 d-flex align-items-center gap-2"
         style="background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;border-radius:8px;">
        <i class="fa-solid fa-triangle-exclamation"></i>
        {{ session('error') }}
        <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="alert"></button>
    </div>
    @endif

    {{-- Section Header --}}
    <div class="section-header d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0 fw-bold text-white">
                <i class="fa-solid fa-users me-2" style="color:var(--nx-cyan)"></i>
                {{ __('menu.users_access') ?? 'Users & Access Management' }}
            </h4>
            <small class="text-muted">{{ __('menu.users_sub') ?? 'Manage system users and role-based access permissions' }}</small>
        </div>
    </div>

    {{-- Stat Cards --}}
    @php
        $fifteenMinAgo = now()->subMinutes(15);
        $allUsers = $users->getCollection();
        $totalUsers  = $users->total();
        $activeUsers = $allUsers->where('status', 'active')->count();
        $adminUsers  = $allUsers->filter(fn($u) => optional($u->role)->name === 'Admin')->count();
        $onlineNow   = $allUsers->filter(fn($u) => $u->last_login_at && \Carbon\Carbon::parse($u->last_login_at)->gte($fifteenMinAgo))->count();
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(29,78,216,0.15)">
                    <i class="fa-solid fa-users" style="color:#1d4ed8"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.total_users') ?? 'Total Users' }}</div>
                    <div class="stat-value">{{ $totalUsers }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(16,185,129,0.15)">
                    <i class="fa-solid fa-user-check" style="color:#10b981"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.active') ?? 'Active' }}</div>
                    <div class="stat-value">{{ $activeUsers }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(245,158,11,0.15)">
                    <i class="fa-solid fa-user-shield" style="color:#f59e0b"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.admins') ?? 'Admins' }}</div>
                    <div class="stat-value">{{ $adminUsers }}</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="stat-card">
                <div class="stat-icon" style="background:rgba(6,182,212,0.15)">
                    <i class="fa-solid fa-signal" style="color:#06b6d4"></i>
                </div>
                <div class="stat-body">
                    <div class="stat-label">{{ __('menu.online_now') ?? 'Online Now' }}</div>
                    <div class="stat-value">{{ $onlineNow }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="filter-bar mb-3">
        <form method="GET" action="{{ route('users.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
            <div class="input-group" style="max-width:280px;">
                <span class="input-group-text" style="background:#1a2a4a;border-color:#2d4a7a;">
                    <i class="fa-solid fa-search text-muted"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="form-control" placeholder="{{ __('menu.search_users') ?? 'Search name or email...' }}"
                       style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
            </div>

            <select name="role_id" class="form-select" style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;max-width:160px;">
                <option value="">{{ __('menu.all_roles') ?? 'All Roles' }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="form-select" style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;max-width:140px;">
                <option value="">{{ __('menu.all_status') ?? 'All Status' }}</option>
                <option value="active"   {{ request('status')==='active'   ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status')==='inactive' ? 'selected' : '' }}>Inactive</option>
            </select>

            <button type="submit" class="nx-btn nx-btn-outline">
                <i class="fa-solid fa-filter me-1"></i>
                {{ __('menu.filter') ?? 'Filter' }}
            </button>

            @if(request()->hasAny(['search','role_id','status']))
            <a href="{{ route('users.index') }}" class="nx-btn" style="background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid rgba(239,68,68,0.3);">
                <i class="fa-solid fa-xmark me-1"></i>
                {{ __('menu.clear') ?? 'Clear' }}
            </a>
            @endif

            <div class="ms-auto">
                <button type="button" class="nx-btn nx-btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fa-solid fa-user-plus me-1"></i>
                    {{ __('menu.add_user') ?? 'Add User' }}
                </button>
            </div>
        </form>
    </div>

    {{-- Users Table --}}
    <div class="nx-card">
        <div class="nx-card-header d-flex align-items-center justify-content-between">
            <span>
                <i class="fa-solid fa-table-list me-2" style="color:var(--nx-cyan)"></i>
                {{ __('menu.user_list') ?? 'User List' }}
            </span>
            <span class="text-muted small">{{ $users->total() }} {{ __('menu.users') ?? 'users' }}</span>
        </div>
        <div class="nx-card-body p-0">
            @if($users->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-user-slash fa-2x mb-3 d-block opacity-40"></i>
                    <p>{{ __('menu.no_users') ?? 'No users found.' }}</p>
                </div>
            @else
            <div class="table-responsive">
                <table class="nx-table w-100">
                    <thead>
                        <tr>
                            <th>{{ __('menu.user') ?? 'User' }}</th>
                            <th>{{ __('menu.role') ?? 'Role' }}</th>
                            <th>{{ __('menu.department') ?? 'Department' }}</th>
                            <th>{{ __('menu.phone') ?? 'Phone' }}</th>
                            <th class="text-center">{{ __('menu.status') ?? 'Status' }}</th>
                            <th>{{ __('menu.last_login') ?? 'Last Login' }}</th>
                            <th class="text-center">{{ __('menu.actions') ?? 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        @php
                            $avatarColors = ['#1d4ed8','#06b6d4','#10b981','#f59e0b','#8b5cf6','#ec4899'];
                            $avatarColor  = $avatarColors[crc32($user->name) % count($avatarColors)];
                            $initial      = strtoupper(substr($user->name, 0, 1));

                            $roleColors = [
                                'Admin'    => ['bg'=>'rgba(239,68,68,0.15)','color'=>'#ef4444'],
                                'Manager'  => ['bg'=>'rgba(245,158,11,0.15)','color'=>'#f59e0b'],
                                'Operator' => ['bg'=>'rgba(29,78,216,0.15)','color'=>'#1d4ed8'],
                            ];
                            $roleName = optional($user->role)->name ?? 'User';
                            $rc = $roleColors[$roleName] ?? ['bg'=>'rgba(107,114,128,0.15)','color'=>'#9ca3af'];

                            $isOnline = $user->last_login_at && \Carbon\Carbon::parse($user->last_login_at)->gte($fifteenMinAgo);
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div style="
                                        width:36px;height:36px;border-radius:50%;
                                        background:{{ $avatarColor }};
                                        display:flex;align-items:center;justify-content:center;
                                        font-weight:700;color:#fff;font-size:.875rem;
                                        flex-shrink:0;position:relative;
                                    ">
                                        {{ $initial }}
                                        @if($isOnline)
                                        <span style="
                                            position:absolute;bottom:1px;right:1px;
                                            width:9px;height:9px;
                                            background:#10b981;border-radius:50%;
                                            border:2px solid #0f1e38;
                                        "></span>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-white">{{ $user->name }}</div>
                                        <div class="text-muted" style="font-size:.78rem;">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="nx-badge" style="background:{{ $rc['bg'] }};color:{{ $rc['color'] }}">
                                    {{ $roleName }}
                                </span>
                            </td>
                            <td class="text-muted small">{{ $user->department ?? '—' }}</td>
                            <td class="text-muted small">{{ $user->phone ?? '—' }}</td>
                            <td class="text-center">
                                @if($user->status === 'active')
                                    <span class="nx-badge" style="background:rgba(16,185,129,0.15);color:#10b981;">
                                        <i class="fa-solid fa-circle me-1" style="font-size:.4rem;vertical-align:middle;"></i>
                                        {{ __('menu.active') ?? 'Active' }}
                                    </span>
                                @else
                                    <span class="nx-badge" style="background:rgba(107,114,128,0.15);color:#9ca3af;">
                                        <i class="fa-solid fa-circle me-1" style="font-size:.4rem;vertical-align:middle;"></i>
                                        {{ __('menu.inactive') ?? 'Inactive' }}
                                    </span>
                                @endif
                            </td>
                            <td class="text-muted small">
                                @if($user->last_login_at)
                                    <span title="{{ \Carbon\Carbon::parse($user->last_login_at)->format('d M Y H:i:s') }}">
                                        {{ \Carbon\Carbon::parse($user->last_login_at)->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-muted">{{ __('menu.never') ?? 'Never' }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('users.edit', $user->id) }}"
                                       class="nx-btn nx-btn-outline" style="padding:4px 10px;font-size:.78rem;"
                                       title="{{ __('menu.edit') ?? 'Edit' }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    @if($user->status === 'active')
                                    <form method="POST" action="{{ route('users.deactivate', $user->id) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="nx-btn"
                                                style="padding:4px 10px;font-size:.78rem;background:rgba(245,158,11,0.12);color:#f59e0b;border:1px solid rgba(245,158,11,0.3);"
                                                title="{{ __('menu.deactivate') ?? 'Deactivate' }}"
                                                onclick="return confirm('{{ __('menu.confirm_deactivate') ?? 'Deactivate this user?' }}')">
                                            <i class="fa-solid fa-user-slash"></i>
                                        </button>
                                    </form>
                                    @else
                                    <form method="POST" action="{{ route('users.activate', $user->id) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit"
                                                class="nx-btn"
                                                style="padding:4px 10px;font-size:.78rem;background:rgba(16,185,129,0.12);color:#10b981;border:1px solid rgba(16,185,129,0.3);"
                                                title="{{ __('menu.activate') ?? 'Activate' }}">
                                            <i class="fa-solid fa-user-check"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
            <div class="d-flex justify-content-end p-3">
                {{ $users->appends(request()->query())->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>

</div>

{{-- Add User Modal --}}
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background:#0f1e38;border:1px solid rgba(255,255,255,0.1);">
            <div class="modal-header" style="border-bottom:1px solid rgba(255,255,255,0.08);">
                <h5 class="modal-title text-white" id="addUserModalLabel">
                    <i class="fa-solid fa-user-plus me-2" style="color:var(--nx-cyan)"></i>
                    {{ __('menu.add_user') ?? 'Add New User' }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.full_name') ?? 'Full Name' }} <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="name" class="form-control"
                                   placeholder="{{ __('menu.full_name_ph') ?? 'e.g. John Doe' }}"
                                   style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.email') ?? 'Email Address' }} <span class="text-danger">*</span>
                            </label>
                            <input type="email" name="email" class="form-control"
                                   placeholder="john@example.com"
                                   style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.password') ?? 'Password' }} <span class="text-danger">*</span>
                            </label>
                            <input type="password" name="password" id="modal-password" class="form-control"
                                   placeholder="Min. 8 characters"
                                   style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;" required minlength="8">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.confirm_password') ?? 'Confirm Password' }} <span class="text-danger">*</span>
                            </label>
                            <input type="password" name="password_confirmation" id="modal-confirm-password" class="form-control"
                                   placeholder="Re-enter password"
                                   style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.role') ?? 'Role' }} <span class="text-danger">*</span>
                            </label>
                            <select name="role_id" class="form-select"
                                    style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;" required>
                                <option value="">— {{ __('menu.select_role') ?? 'Select Role' }} —</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.status') ?? 'Status' }}
                            </label>
                            <select name="status" class="form-select"
                                    style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.department') ?? 'Department' }}
                            </label>
                            <input type="text" name="department" class="form-control"
                                   placeholder="{{ __('menu.department_ph') ?? 'e.g. Facilities Management' }}"
                                   style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-white-50 small">
                                {{ __('menu.phone') ?? 'Phone Number' }}
                            </label>
                            <input type="text" name="phone" class="form-control"
                                   placeholder="{{ __('menu.phone_ph') ?? '+66 81 234 5678' }}"
                                   style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid rgba(255,255,255,0.08);">
                    <button type="button" class="nx-btn nx-btn-outline" data-bs-dismiss="modal">
                        {{ __('menu.cancel') ?? 'Cancel' }}
                    </button>
                    <button type="submit" class="nx-btn nx-btn-primary">
                        <i class="fa-solid fa-user-plus me-1"></i>
                        {{ __('menu.create_user') ?? 'Create User' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Validate password match in modal
document.querySelector('[data-bs-target="#addUserModal"]') && (function () {
    const form = document.querySelector('#addUserModal form');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        const pwd  = document.getElementById('modal-password').value;
        const conf = document.getElementById('modal-confirm-password').value;
        if (pwd !== conf) {
            e.preventDefault();
            document.getElementById('modal-confirm-password').style.borderColor = '#ef4444';
            alert('{{ __("menu.password_mismatch") ?? "Passwords do not match." }}');
        }
    });
})();
</script>
@endpush
@endsection
