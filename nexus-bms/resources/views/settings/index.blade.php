@extends('layouts.app')

@section('title', __('menu.settings') ?? 'System Settings')

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
                <i class="fa-solid fa-gear me-2" style="color:var(--nx-cyan)"></i>
                {{ __('menu.system_settings') ?? 'System Settings' }}
            </h4>
            <small class="text-muted">{{ __('menu.settings_sub') ?? 'Configure system-wide preferences and integrations' }}</small>
        </div>
    </div>

    {{-- Tab Navigation --}}
    <ul class="nav nav-pills mb-4 gap-1" id="settingsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active nx-chip" id="tab-general" data-bs-toggle="pill" data-bs-target="#pane-general" type="button" role="tab">
                <i class="fa-solid fa-sliders me-1"></i>
                {{ __('menu.general') ?? 'General' }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link nx-chip" id="tab-notifications" data-bs-toggle="pill" data-bs-target="#pane-notifications" type="button" role="tab">
                <i class="fa-solid fa-bell me-1"></i>
                {{ __('menu.notifications') ?? 'Notifications' }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link nx-chip" id="tab-security" data-bs-toggle="pill" data-bs-target="#pane-security" type="button" role="tab">
                <i class="fa-solid fa-shield-halved me-1"></i>
                {{ __('menu.security') ?? 'Security' }}
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link nx-chip" id="tab-backup" data-bs-toggle="pill" data-bs-target="#pane-backup" type="button" role="tab">
                <i class="fa-solid fa-database me-1"></i>
                {{ __('menu.backup') ?? 'Backup' }}
            </button>
        </li>
    </ul>

    <div class="tab-content" id="settingsTabContent">

        {{-- ====== TAB 1: GENERAL ====== --}}
        <div class="tab-pane fade show active" id="pane-general" role="tabpanel">
            <div class="nx-card">
                <div class="nx-card-header">
                    <i class="fa-solid fa-sliders me-2" style="color:var(--nx-cyan)"></i>
                    {{ __('menu.general_settings') ?? 'General Settings' }}
                </div>
                <div class="nx-card-body">
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        <input type="hidden" name="tab" value="general">

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small">
                                    {{ __('menu.app_name') ?? 'Application Name' }}
                                </label>
                                <input type="text" name="app_name"
                                       value="{{ $settings['app_name'] ?? 'Nexus BMS' }}"
                                       class="form-control"
                                       style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-white-50 small">
                                    {{ __('menu.timezone') ?? 'Timezone' }}
                                </label>
                                <select name="app_timezone" class="form-select"
                                        style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                                    @php
                                        $commonTZ = [
                                            'Asia/Bangkok'     => 'Asia/Bangkok (UTC+7)',
                                            'Asia/Singapore'   => 'Asia/Singapore (UTC+8)',
                                            'Asia/Tokyo'       => 'Asia/Tokyo (UTC+9)',
                                            'Asia/Kolkata'     => 'Asia/Kolkata (UTC+5:30)',
                                            'Asia/Dubai'       => 'Asia/Dubai (UTC+4)',
                                            'Europe/London'    => 'Europe/London (UTC+0/+1)',
                                            'Europe/Paris'     => 'Europe/Paris (UTC+1/+2)',
                                            'America/New_York' => 'America/New_York (UTC-5/-4)',
                                            'America/Chicago'  => 'America/Chicago (UTC-6/-5)',
                                            'America/Denver'   => 'America/Denver (UTC-7/-6)',
                                            'America/Los_Angeles' => 'America/Los_Angeles (UTC-8/-7)',
                                            'Pacific/Auckland' => 'Pacific/Auckland (UTC+12)',
                                            'Australia/Sydney' => 'Australia/Sydney (UTC+10/+11)',
                                            'UTC'              => 'UTC (UTC+0)',
                                        ];
                                        $currentTZ = $settings['app_timezone'] ?? 'Asia/Bangkok';
                                    @endphp
                                    @foreach($commonTZ as $tz => $label)
                                        <option value="{{ $tz }}" {{ $currentTZ === $tz ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-white-50 small">
                                    {{ __('menu.default_language') ?? 'Default Language' }}
                                </label>
                                <select name="app_language" class="form-select"
                                        style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                                    <option value="th" {{ ($settings['app_language'] ?? 'th') === 'th' ? 'selected' : '' }}>
                                        🇹🇭 Thai (ไทย)
                                    </option>
                                    <option value="en" {{ ($settings['app_language'] ?? 'th') === 'en' ? 'selected' : '' }}>
                                        🇬🇧 English
                                    </option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-white-50 small d-block">
                                    {{ __('menu.maintenance_mode') ?? 'Maintenance Mode' }}
                                </label>
                                <div class="d-flex align-items-center gap-3 mt-1">
                                    <div class="form-check form-switch mb-0">
                                        <input type="hidden" name="maintenance_mode" value="0">
                                        <input type="checkbox" name="maintenance_mode" value="1"
                                               class="form-check-input" role="switch"
                                               id="maintenance-toggle"
                                               {{ ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' }}>
                                        <label class="form-check-label text-muted small" for="maintenance-toggle">
                                            {{ __('menu.maintenance_desc') ?? 'Disable public access for maintenance' }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top" style="border-color:rgba(255,255,255,0.06)!important;">
                            <button type="submit" class="nx-btn nx-btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i>
                                {{ __('menu.save_settings') ?? 'Save Settings' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ====== TAB 2: NOTIFICATIONS ====== --}}
        <div class="tab-pane fade" id="pane-notifications" role="tabpanel">
            <div class="nx-card">
                <div class="nx-card-header">
                    <i class="fa-solid fa-bell me-2" style="color:var(--nx-cyan)"></i>
                    {{ __('menu.notification_settings') ?? 'Notification Settings' }}
                </div>
                <div class="nx-card-body">
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        <input type="hidden" name="tab" value="notifications">

                        {{-- SMTP --}}
                        <h6 class="text-white-50 mb-3 fw-semibold" style="font-size:.8rem;letter-spacing:.08em;text-transform:uppercase;">
                            <i class="fa-solid fa-envelope me-2" style="color:var(--nx-blue)"></i>Email (SMTP)
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small">SMTP Host</label>
                                <input type="text" name="smtp_host"
                                       value="{{ $settings['smtp_host'] ?? 'smtp.gmail.com' }}"
                                       class="form-control"
                                       placeholder="smtp.gmail.com"
                                       style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label text-white-50 small">SMTP Port</label>
                                <input type="number" name="smtp_port"
                                       value="{{ $settings['smtp_port'] ?? 587 }}"
                                       class="form-control"
                                       placeholder="587"
                                       style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-white-50 small">SMTP Username</label>
                                <input type="text" name="smtp_username"
                                       value="{{ $settings['smtp_username'] ?? '' }}"
                                       class="form-control"
                                       placeholder="your@email.com"
                                       style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                            </div>
                        </div>

                        {{-- Alarm Notifications --}}
                        <h6 class="text-white-50 mb-3 fw-semibold" style="font-size:.8rem;letter-spacing:.08em;text-transform:uppercase;">
                            <i class="fa-solid fa-bell-ring me-2" style="color:var(--nx-yellow)"></i>
                            {{ __('menu.alarm_notifications') ?? 'Alarm Notifications' }}
                        </h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small d-block">
                                    {{ __('menu.alarm_email_enabled') ?? 'Email Alerts' }}
                                </label>
                                <div class="form-check form-switch mt-1">
                                    <input type="hidden" name="alarm_email_enabled" value="0">
                                    <input type="checkbox" name="alarm_email_enabled" value="1"
                                           class="form-check-input" role="switch" id="alarm-email-toggle"
                                           {{ ($settings['alarm_email_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label text-muted small" for="alarm-email-toggle">
                                        {{ __('menu.alarm_email_desc') ?? 'Send email on critical alarms' }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-white-50 small">
                                    {{ __('menu.report_email') ?? 'Report Delivery Email' }}
                                </label>
                                <input type="email" name="report_email"
                                       value="{{ $settings['report_email'] ?? '' }}"
                                       class="form-control"
                                       placeholder="reports@example.com"
                                       style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                            </div>
                        </div>

                        {{-- LINE Notify --}}
                        <h6 class="text-white-50 mb-3 fw-semibold" style="font-size:.8rem;letter-spacing:.08em;text-transform:uppercase;">
                            <i class="fa-brands fa-line me-2" style="color:#06c755"></i>LINE Notify
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label text-white-50 small">
                                    {{ __('menu.alarm_line_token') ?? 'LINE Notify Token' }}
                                </label>
                                <input type="text" name="alarm_line_token"
                                       value="{{ $settings['alarm_line_token'] ?? '' }}"
                                       class="form-control"
                                       placeholder="{{ __('menu.line_token_ph') ?? 'Paste your LINE Notify access token here' }}"
                                       style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                                <div class="text-muted mt-1" style="font-size:.75rem;">
                                    Get token at
                                    <a href="https://notify-bot.line.me/my/" target="_blank" style="color:#06c755;">notify-bot.line.me/my/</a>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top" style="border-color:rgba(255,255,255,0.06)!important;">
                            <button type="submit" class="nx-btn nx-btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i>
                                {{ __('menu.save_settings') ?? 'Save Settings' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ====== TAB 3: SECURITY ====== --}}
        <div class="tab-pane fade" id="pane-security" role="tabpanel">
            <div class="nx-card">
                <div class="nx-card-header">
                    <i class="fa-solid fa-shield-halved me-2" style="color:var(--nx-cyan)"></i>
                    {{ __('menu.security_settings') ?? 'Security Settings' }}
                </div>
                <div class="nx-card-body">
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        <input type="hidden" name="tab" value="security">

                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label text-white-50 small">
                                    {{ __('menu.session_timeout') ?? 'Session Timeout (minutes)' }}
                                </label>
                                <input type="number" name="session_timeout"
                                       value="{{ $settings['session_timeout'] ?? 120 }}"
                                       min="5" max="1440"
                                       class="form-control"
                                       style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                                <div class="text-muted mt-1" style="font-size:.75rem;">
                                    {{ __('menu.session_timeout_desc') ?? 'Auto logout after inactivity (5–1440 min)' }}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-white-50 small">
                                    {{ __('menu.max_login_attempts') ?? 'Max Login Attempts' }}
                                </label>
                                <input type="number" name="max_login_attempts"
                                       value="{{ $settings['max_login_attempts'] ?? 5 }}"
                                       min="3" max="20"
                                       class="form-control"
                                       style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                                <div class="text-muted mt-1" style="font-size:.75rem;">
                                    {{ __('menu.max_login_desc') ?? 'Lock account after N failed attempts' }}
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-white-50 small">
                                    {{ __('menu.password_min_length') ?? 'Minimum Password Length' }}
                                </label>
                                <input type="number" name="password_min_length"
                                       value="{{ $settings['password_min_length'] ?? 8 }}"
                                       min="6" max="32"
                                       class="form-control"
                                       style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                                <div class="text-muted mt-1" style="font-size:.75rem;">
                                    {{ __('menu.password_min_desc') ?? 'Minimum characters required (6–32)' }}
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-3"
                                     style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:8px;">
                                    <div>
                                        <div class="fw-semibold text-white small">
                                            <i class="fa-solid fa-mobile-screen me-2" style="color:var(--nx-cyan)"></i>
                                            {{ __('menu.two_factor_auth') ?? 'Two-Factor Authentication' }}
                                        </div>
                                        <div class="text-muted" style="font-size:.78rem;margin-top:2px;">
                                            {{ __('menu.two_factor_desc') ?? 'Require 2FA for all admin accounts' }}
                                        </div>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input type="hidden" name="two_factor_enabled" value="0">
                                        <input type="checkbox" name="two_factor_enabled" value="1"
                                               class="form-check-input" role="switch" id="2fa-toggle"
                                               {{ ($settings['two_factor_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-4 pt-3 border-top" style="border-color:rgba(255,255,255,0.06)!important;">
                            <button type="submit" class="nx-btn nx-btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i>
                                {{ __('menu.save_settings') ?? 'Save Settings' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- ====== TAB 4: BACKUP ====== --}}
        <div class="tab-pane fade" id="pane-backup" role="tabpanel">
            <div class="nx-card">
                <div class="nx-card-header">
                    <i class="fa-solid fa-database me-2" style="color:var(--nx-cyan)"></i>
                    {{ __('menu.backup_settings') ?? 'Backup Settings' }}
                </div>
                <div class="nx-card-body">
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        <input type="hidden" name="tab" value="backup">

                        <div class="row g-4">
                            <div class="col-12">
                                <div class="d-flex align-items-center justify-content-between p-3 mb-3"
                                     style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);border-radius:8px;">
                                    <div>
                                        <div class="fw-semibold text-white small">
                                            <i class="fa-solid fa-cloud-arrow-up me-2" style="color:var(--nx-blue)"></i>
                                            {{ __('menu.backup_enabled') ?? 'Automatic Backup' }}
                                        </div>
                                        <div class="text-muted" style="font-size:.78rem;margin-top:2px;">
                                            {{ __('menu.backup_enabled_desc') ?? 'Enable scheduled automatic database backups' }}
                                        </div>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input type="hidden" name="backup_enabled" value="0">
                                        <input type="checkbox" name="backup_enabled" value="1"
                                               class="form-check-input" role="switch" id="backup-toggle"
                                               {{ ($settings['backup_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-white-50 small">
                                    {{ __('menu.backup_frequency') ?? 'Backup Frequency' }}
                                </label>
                                <select name="backup_frequency" class="form-select"
                                        style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                                    <option value="daily"   {{ ($settings['backup_frequency'] ?? 'daily') === 'daily'   ? 'selected' : '' }}>Daily</option>
                                    <option value="weekly"  {{ ($settings['backup_frequency'] ?? 'daily') === 'weekly'  ? 'selected' : '' }}>Weekly</option>
                                    <option value="monthly" {{ ($settings['backup_frequency'] ?? 'daily') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label text-white-50 small">
                                    {{ __('menu.backup_retention_days') ?? 'Retention Period (days)' }}
                                </label>
                                <input type="number" name="backup_retention_days"
                                       value="{{ $settings['backup_retention_days'] ?? 30 }}"
                                       min="1" max="365"
                                       class="form-control"
                                       style="background:#1a2a4a;color:#fff;border-color:#2d4a7a;">
                                <div class="text-muted mt-1" style="font-size:.75rem;">
                                    {{ __('menu.backup_retention_desc') ?? 'Delete backups older than N days' }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top" style="border-color:rgba(255,255,255,0.06)!important;">
                            <form method="POST" action="{{ route('settings.backup-now') }}" class="mb-0">
                                @csrf
                                <button type="submit" class="nx-btn nx-btn-outline"
                                        onclick="return confirm('{{ __('menu.confirm_backup_now') ?? 'Run a manual backup now?' }}')">
                                    <i class="fa-solid fa-play me-1" style="color:#06b6d4"></i>
                                    {{ __('menu.run_backup_now') ?? 'Run Backup Now' }}
                                </button>
                            </form>
                            <button type="submit" class="nx-btn nx-btn-primary">
                                <i class="fa-solid fa-floppy-disk me-1"></i>
                                {{ __('menu.save_settings') ?? 'Save Settings' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>{{-- end tab-content --}}

</div>

@push('scripts')
<script>
// Restore active tab from URL hash or session
(function () {
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector('[data-bs-target="' + hash + '"]');
        if (tab) new bootstrap.Tab(tab).show();
    }
    // Update hash on tab change
    document.querySelectorAll('#settingsTabs [data-bs-toggle="pill"]').forEach(btn => {
        btn.addEventListener('shown.bs.tab', function () {
            history.replaceState(null, '', this.dataset.bsTarget);
        });
    });
})();
</script>
@endpush
@endsection
