@extends('layouts.auth')
@section('title', 'Login')
@section('content')
<div style="display:flex; min-height:100vh;">

    <!-- LEFT PANEL -->
    <div style="flex:1.2; background:linear-gradient(135deg,#0d1b34 0%,#0f2040 50%,#0a1628 100%); display:flex; flex-direction:column; justify-content:center; align-items:center; padding:60px 40px; position:relative; overflow:hidden;">

        <!-- Background dots -->
        <div style="position:absolute;inset:0;background-image:radial-gradient(rgba(29,78,216,0.15) 1px,transparent 1px);background-size:30px 30px;"></div>

        <!-- Glow -->
        <div style="position:absolute;top:30%;left:50%;transform:translate(-50%,-50%);width:400px;height:400px;background:radial-gradient(circle,rgba(29,78,216,0.15),transparent 70%);"></div>

        <div style="position:relative;z-index:1;text-align:center;max-width:420px;">
            <!-- Logo -->
            <div class="platform-logo-card login-hero">
                <img src="{{ asset('images/nexus-logo-cropped.png') }}" alt="Nexus BMS Platform">
            </div>

            <h1 style="font-size:32px;font-weight:700;color:white;margin-bottom:12px;line-height:1.3;">
                Smart Building<br>Management System
            </h1>
            <p style="color:rgba(255,255,255,0.5);font-size:15px;margin-bottom:48px;">ระบบบริหารจัดการอาคารอัจฉริยะ</p>

            <!-- Feature list -->
            <div style="display:flex;flex-direction:column;gap:16px;text-align:left;">
                @foreach([['fa-gauge-high','Real-time Monitoring','ตรวจสอบสถานะแบบเรียลไทม์'],['fa-bolt','Energy Management','บริหารจัดการพลังงาน'],['fa-bell','Smart Alerts','ระบบแจ้งเตือนอัจฉริยะ'],['fa-calendar-days','Auto Scheduling','กำหนดการทำงานอัตโนมัติ']] as $feat)
                <div style="display:flex;align-items:center;gap:14px;">
                    <div style="width:40px;height:40px;background:rgba(29,78,216,0.2);border-radius:10px;display:flex;align-items:center;justify-content:center;border:1px solid rgba(29,78,216,0.3);">
                        <i class="fa-solid {{ $feat[0] }}" style="color:#60a5fa;font-size:16px;"></i>
                    </div>
                    <div>
                        <div style="font-size:14px;font-weight:600;color:white;">{{ $feat[1] }}</div>
                        <div style="font-size:12px;color:rgba(255,255,255,0.4);">{{ $feat[2] }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- City SVG art at bottom -->
        <div style="position:absolute;bottom:0;left:0;right:0;height:80px;display:flex;align-items:flex-end;padding:0 20px;gap:4px;opacity:0.15;">
            @foreach([30,50,40,70,55,80,45,65,35,75,50,60,40,55,45,70] as $h)
            <div style="flex:1;height:{{ $h }}px;background:rgba(255,255,255,0.8);border-radius:2px 2px 0 0;"></div>
            @endforeach
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div style="width:480px;background:white;display:flex;flex-direction:column;justify-content:center;padding:60px 50px;position:relative;">

        <!-- Lang switcher -->
        <div style="position:absolute;top:24px;right:24px;">
            <div class="lang-switcher">
                <a href="{{ route('lang.switch','th') }}" class="lang-btn {{ app()->getLocale()==='th'?'active':'' }}">TH</a>
                <a href="{{ route('lang.switch','en') }}" class="lang-btn {{ app()->getLocale()==='en'?'active':'' }}">EN</a>
            </div>
        </div>

        <div style="max-width:380px;width:100%;margin:0 auto;">
            <!-- Header -->
            <div style="margin-bottom:36px;">
                <div class="platform-logo-card login-panel">
                    <img src="{{ asset('images/nexus-logo-cropped.png') }}" alt="Nexus BMS Platform">
                </div>
                <h2 style="font-size:26px;font-weight:700;color:#1e293b;margin-bottom:6px;">{{ __('auth.welcome_back') }}</h2>
                <p style="color:#64748b;font-size:14px;">{{ __('auth.login_subtitle') }}</p>
            </div>

            <!-- Errors -->
            @if($errors->any())
            <div class="nx-alert nx-alert-danger" style="margin-bottom:20px;">
                <i class="fa-solid fa-circle-xmark"></i>
                {{ $errors->first() }}
            </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('login.post') }}">
                @csrf
                <div style="margin-bottom:20px;">
                    <label class="nx-label">{{ __('auth.email') }}</label>
                    <div style="position:relative;">
                        <i class="fa-solid fa-envelope" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;"></i>
                        <input type="email" name="email" class="nx-input" style="padding-left:38px;" value="{{ old('email','admin@nexus.com') }}" placeholder="email@example.com" required>
                    </div>
                </div>

                <div style="margin-bottom:20px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <label class="nx-label" style="margin:0;">{{ __('auth.password') }}</label>
                        <a href="#" style="font-size:12px;color:#1d4ed8;text-decoration:none;">{{ __('auth.forgot_password') }}</a>
                    </div>
                    <div style="position:relative;">
                        <i class="fa-solid fa-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;"></i>
                        <input type="password" name="password" class="nx-input" id="passwordInput" style="padding-left:38px;padding-right:40px;" placeholder="••••••••" required>
                        <button type="button" onclick="togglePwd()" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);border:none;background:none;color:#94a3b8;cursor:pointer;">
                            <i class="fa-solid fa-eye" id="pwdEye"></i>
                        </button>
                    </div>
                </div>

                <div style="display:flex;align-items:center;gap:8px;margin-bottom:28px;">
                    <input type="checkbox" name="remember" id="remember" style="width:16px;height:16px;accent-color:#1d4ed8;">
                    <label for="remember" style="font-size:13px;color:#64748b;cursor:pointer;margin:0;">{{ __('auth.remember_me') }}</label>
                </div>

                <button type="submit" class="nx-btn nx-btn-primary w-100" style="height:46px;font-size:15px;justify-content:center;border-radius:10px;">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    {{ __('auth.login') }}
                </button>
            </form>

            <!-- Default credentials hint -->
            <div style="margin-top:24px;padding:12px 16px;background:#f8fafc;border-radius:8px;border:1px solid #e2e8f0;">
                <div style="font-size:11px;color:#94a3b8;font-weight:600;margin-bottom:4px;">DEFAULT CREDENTIALS</div>
                <div style="font-size:12px;color:#475569;">Email: <strong>admin@nexus.com</strong></div>
                <div style="font-size:12px;color:#475569;">Password: <strong>admin1234</strong></div>
            </div>
        </div>

        <!-- Footer -->
        <div style="position:absolute;bottom:24px;left:50%;transform:translateX(-50%);text-align:center;">
            <p style="font-size:11px;color:#94a3b8;">© {{ date('Y') }} Nexus Corporation. All rights reserved.</p>
        </div>
    </div>
</div>

<script>
function togglePwd() {
    const input = document.getElementById('passwordInput');
    const eye = document.getElementById('pwdEye');
    if (input.type === 'password') {
        input.type = 'text';
        eye.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        eye.className = 'fa-solid fa-eye';
    }
}
</script>
@endsection
