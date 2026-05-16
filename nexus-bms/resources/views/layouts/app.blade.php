<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Nexus BMS Platform</title>
    <link rel="icon" type="image/png" href="{{ asset('images/nexus-favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Nexus Theme -->
    <link rel="stylesheet" href="{{ asset('css/nexus.css') }}">
    @yield('styles')
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
<aside class="nx-sidebar" id="nxSidebar">
    <!-- Brand -->
    <a href="{{ route('dashboard') }}" class="sidebar-brand">
        <span class="brand-mark">
            <img src="{{ asset('images/nexus-mark.png') }}" alt="" aria-hidden="true">
        </span>
        <span class="brand-text">
            <span class="brand-name">Nexus</span>
            <span class="brand-sub">BMS Platform</span>
        </span>
    </a>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="nav-section-title">{{ __('menu.main_menu') }}</div>

        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fa-solid fa-gauge-high"></i></span>
            <span class="nav-label">{{ __('menu.dashboard') }}</span>
        </a>

        <a href="{{ route('buildings.index') }}" class="nav-item {{ request()->routeIs('buildings.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fa-solid fa-building"></i></span>
            <span class="nav-label">{{ __('menu.buildings') }}</span>
        </a>

        <a href="{{ route('floors.index') }}" class="nav-item {{ request()->routeIs('floors.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fa-solid fa-layer-group"></i></span>
            <span class="nav-label">{{ __('menu.floor_plans') }}</span>
        </a>

        <a href="{{ route('equipment.index') }}" class="nav-item {{ request()->routeIs('equipment.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fa-solid fa-microchip"></i></span>
            <span class="nav-label">{{ __('menu.equipment') }}</span>
        </a>

        <div class="nav-section-title" style="margin-top:8px">{{ __('menu.monitoring') }}</div>

        <a href="{{ route('alarms.index') }}" class="nav-item {{ request()->routeIs('alarms.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fa-solid fa-bell"></i></span>
            <span class="nav-label">{{ __('menu.alarms') }}</span>
            @php $activeAlarms = \App\Models\Alarm::where('status','active')->count(); @endphp
            @if($activeAlarms > 0)
            <span class="nav-badge">{{ $activeAlarms }}</span>
            @endif
        </a>

        <a href="{{ route('energy.index') }}" class="nav-item {{ request()->routeIs('energy.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fa-solid fa-bolt"></i></span>
            <span class="nav-label">{{ __('menu.energy') }}</span>
        </a>

        <a href="{{ route('schedules.index') }}" class="nav-item {{ request()->routeIs('schedules.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fa-solid fa-calendar-days"></i></span>
            <span class="nav-label">{{ __('menu.schedules') }}</span>
        </a>

        <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fa-solid fa-chart-bar"></i></span>
            <span class="nav-label">{{ __('menu.reports') }}</span>
        </a>

        <div class="nav-section-title" style="margin-top:8px">{{ __('menu.administration') }}</div>

        <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fa-solid fa-users"></i></span>
            <span class="nav-label">{{ __('menu.users') }}</span>
        </a>

        <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fa-solid fa-gear"></i></span>
            <span class="nav-label">{{ __('menu.settings') }}</span>
        </a>

        <a href="{{ route('logs.index') }}" class="nav-item {{ request()->routeIs('logs.*') ? 'active' : '' }}">
            <span class="nav-icon"><i class="fa-solid fa-list-check"></i></span>
            <span class="nav-label">{{ __('menu.logs') }}</span>
        </a>
    </nav>

    <!-- City Art -->
    <div class="sidebar-city">
        <div class="city-art">
            <div class="city-building" style="height:40px"></div>
            <div class="city-building" style="height:65px"></div>
            <div class="city-building" style="height:50px"></div>
            <div class="city-building" style="height:75px"></div>
            <div class="city-building" style="height:55px"></div>
            <div class="city-building" style="height:70px"></div>
            <div class="city-building" style="height:45px"></div>
            <div class="city-building" style="height:60px"></div>
            <div class="city-building" style="height:80px"></div>
            <div class="city-building" style="height:50px"></div>
            <div class="city-building" style="height:35px"></div>
            <div class="city-building" style="height:55px"></div>
            <div class="city-glow"></div>
        </div>
        <div style="text-align:center; padding: 8px 0; font-size:10px; color:rgba(255,255,255,0.2);">
            Nexus BMS Platform v2.0
        </div>
    </div>

    <!-- Collapse -->
    <div class="sidebar-collapse-btn" id="sidebarToggle" style="padding:10px 18px; cursor:pointer; border-top:1px solid rgba(255,255,255,0.08);">
        <i class="fa-solid fa-angles-left" id="collapseIcon"></i>
        <span style="font-size:12px; color:rgba(255,255,255,0.4);">{{ __('menu.collapse') }}</span>
    </div>
</aside>

<!-- MAIN -->
<div class="nx-main" id="nxMain">
    <!-- HEADER -->
    <header class="nx-header">
        <button class="header-toggle sidebar-header-toggle" id="sidebarHeaderToggle" type="button" aria-label="Toggle sidebar" aria-expanded="true">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="header-breadcrumb">
            <div>
                <div class="breadcrumb-title">
                    @hasSection('page-title')
                        @yield('page-title')
                    @else
                        @yield('title', 'Dashboard')
                    @endif
                </div>
                <div class="breadcrumb-sub">@yield('page-subtitle', '')</div>
            </div>
        </div>

        <!-- Search -->
        <div class="header-search">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" placeholder="{{ __('menu.search_placeholder') }}" id="globalSearch">
        </div>

        <div class="header-actions">
            <!-- Notifications -->
            <div class="nx-dropdown">
                <button class="header-btn" data-dropdown="notifDropdown" data-tooltip="{{ __('menu.notifications') }}">
                    <i class="fa-solid fa-bell"></i>
                    @if(isset($activeAlarms) && $activeAlarms > 0)
                    <span class="badge-dot"></span>
                    @endif
                </button>
                <div class="nx-dropdown-menu" id="notifDropdown" style="min-width:280px; right:0;">
                    <div style="padding:12px 16px; border-bottom:1px solid var(--border); font-weight:600; font-size:13px;">
                        {{ __('menu.notifications') }}
                    </div>
                    @php $recentAlarms = \App\Models\Alarm::with('building')->where('status','active')->latest('triggered_at')->limit(4)->get(); @endphp
                    @foreach($recentAlarms as $alarm)
                    <a href="{{ route('alarms.index', ['detail'=>$alarm->id]) }}" class="nx-dropdown-item">
                        <span style="width:8px;height:8px;border-radius:50%;background:{{ $alarm->severity==='critical'?'#ef4444':'#f59e0b' }};flex-shrink:0"></span>
                        <div>
                            <div style="font-size:12px;font-weight:600">{{ Str::limit($alarm->description, 40) }}</div>
                            <div style="font-size:11px;color:var(--text-muted)">{{ $alarm->triggered_at?->diffForHumans() }}</div>
                        </div>
                    </a>
                    @endforeach
                    <div class="nx-dropdown-divider"></div>
                    <a href="{{ route('alarms.index') }}" class="nx-dropdown-item" style="justify-content:center;color:var(--primary);font-weight:600">
                        {{ __('menu.view_all') }}
                    </a>
                </div>
            </div>

            <!-- Building Selector -->
            <div class="nx-dropdown">
                <button class="building-selector" data-dropdown="buildingDropdown">
                    <i class="fa-solid fa-building"></i>
                    <span id="selectedBuilding">Nexus Towers</span>
                    <i class="fa-solid fa-chevron-down" style="font-size:10px;margin-left:4px;color:var(--text-muted)"></i>
                </button>
                <div class="nx-dropdown-menu" id="buildingDropdown">
                    <div style="padding:8px 12px;font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase">{{ __('menu.select_building') }}</div>
                    @php $navBuildings = \App\Models\Building::where('status','active')->limit(6)->get(); @endphp
                    @foreach($navBuildings as $nb)
                    <a class="nx-dropdown-item" href="#" onclick="document.getElementById('selectedBuilding').textContent='{{ $nb->name }}'">
                        <i class="fa-solid fa-building" style="color:var(--primary)"></i>
                        {{ $nb->name }}
                    </a>
                    @endforeach
                </div>
            </div>

            <!-- Language -->
            <div class="lang-switcher">
                <a href="{{ route('lang.switch', 'th') }}" class="lang-btn {{ app()->getLocale() === 'th' ? 'active' : '' }}">TH</a>
                <a href="{{ route('lang.switch', 'en') }}" class="lang-btn {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</a>
            </div>

            <!-- User Menu -->
            <div class="nx-dropdown">
                <div class="user-menu" data-dropdown="userDropdown">
                    <div class="user-avatar">{{ substr(auth()->user()->name ?? 'A', 0, 1) }}</div>
                    <div class="user-info d-none d-md-block">
                        <div class="user-name">{{ auth()->user()->name ?? 'Admin' }}</div>
                        <div class="user-role">{{ auth()->user()->role?->display_name ?? 'Administrator' }}</div>
                    </div>
                    <i class="fa-solid fa-chevron-down" style="font-size:10px;color:var(--text-muted);margin-left:4px"></i>
                </div>
                <div class="nx-dropdown-menu" id="userDropdown">
                    <a href="{{ route('settings.index') }}" class="nx-dropdown-item">
                        <i class="fa-solid fa-user-gear"></i> {{ __('menu.profile') }}
                    </a>
                    <div class="nx-dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="nx-dropdown-item w-100" style="color:var(--danger)">
                            <i class="fa-solid fa-right-from-bracket"></i> {{ __('menu.logout') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- CONTENT -->
    <main class="nx-content">
        @if(session('success'))
        <div class="nx-alert nx-alert-success fade-in">
            <i class="fa-solid fa-circle-check"></i>
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="nx-alert nx-alert-danger fade-in">
            <i class="fa-solid fa-circle-xmark"></i>
            {{ session('error') }}
        </div>
        @endif

        @yield('content')
    </main>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="{{ asset('js/nexus.js') }}"></script>
@yield('scripts')
@stack('scripts')

</body>
</html>
