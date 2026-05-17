# Module Structure

How a single module is organized internally, with concrete examples.

## Directory layout per module

```
app/Modules/<ModuleName>/
├── Providers/
│   └── <Module>ServiceProvider.php   ← registers routes, views, migrations
├── Http/
│   ├── Controllers/
│   ├── Middleware/   (optional, module-specific only)
│   └── Requests/     (form requests if any)
├── Models/           (module-specific entities only)
├── Services/         (module business logic)
├── Console/
│   └── Commands/
├── Database/
│   ├── Migrations/   (module-specific migrations; optional — main migrations folder is fine too)
│   └── Seeders/
├── Views/            (only if NOT using the Theme system)
├── routes.php        (web + api combined or split)
└── config.php
```

## Concrete example — BMS module

After refactor, `app/Modules/BMS/` will look like this:

```
app/Modules/BMS/
├── Providers/
│   └── BmsServiceProvider.php
├── Http/
│   └── Controllers/
│       ├── DashboardController.php
│       ├── BuildingController.php
│       ├── FloorController.php
│       └── SchedulerController.php (BMS-specific schedule UI; logic in Core/Schedules)
├── Models/
│   ├── Building.php
│   ├── Floor.php
│   └── Room.php
├── Services/
│   └── FloorPlanService.php (SVG editing, equipment positions)
├── Console/Commands/
│   └── (none initially — schedules:run lives in Core)
├── Database/Seeders/
│   ├── BuildingSeeder.php
│   ├── BmsPermissionSeeder.php  (seeds bms.* permissions)
│   └── SampleFloorPlanSeeder.php
├── routes.php
└── config.php
```

## ServiceProvider template

Every module follows this skeleton:

```php
<?php
namespace App\Modules\BMS\Providers;

use Illuminate\Support\ServiceProvider;

class BmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config.php', 'modules.bms');
    }

    public function boot(): void
    {
        // Web + API routes
        $this->loadRoutesFrom(__DIR__.'/../routes.php');

        // Database migrations (if module-local)
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Views — accessible as view('bms::dashboard')
        // Optional: if using the Theme system, views live under
        //          resources/views/themes/nexus-bms/ instead
        $this->loadViewsFrom(__DIR__.'/../Views', 'bms');

        // Translations — view('bms::messages.xyz')
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'bms');

        // Register module-specific widgets
        if ($this->app->bound('widgets')) {
            $widgets = $this->app->make('widgets');
            $widgets->register('building-card', \App\Modules\BMS\Widgets\BuildingCardWidget::class);
            $widgets->register('floor-plan-mimic', \App\Modules\BMS\Widgets\FloorPlanMimicWidget::class);
        }

        // Register seeders (run with `php artisan db:seed --class=BmsSeeder`)
        if ($this->app->runningInConsole()) {
            $this->commands([
                // module-specific artisan commands here
            ]);
        }
    }
}
```

Register in `bootstrap/providers.php`:

```php
return [
    App\Providers\AppServiceProvider::class,
    App\Core\Providers\CoreServiceProvider::class,
    App\Modules\BMS\Providers\BmsServiceProvider::class,
    App\Modules\Energy\Providers\EnergyServiceProvider::class,
    // App\Modules\SCADA\Providers\ScadaServiceProvider::class,    // future
    // App\Modules\WMS\Providers\WmsServiceProvider::class,        // future
    // App\Modules\IIoT\Providers\IiotServiceProvider::class,      // future
];
```

## Module routes file

```php
<?php
// app/Modules/BMS/routes.php
use Illuminate\Support\Facades\Route;
use App\Modules\BMS\Http\Controllers\DashboardController;
use App\Modules\BMS\Http\Controllers\BuildingController;
// ...

Route::middleware(['web', 'auth'])
    ->prefix('bms')        // (optional) namespace URLs as /bms/buildings
    ->name('bms.')
    ->group(function () {
        Route::middleware('can.permission:bms.dashboard,view')
            ->get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::middleware('can.permission:bms.buildings,view')->group(function () {
            Route::get('/buildings', [BuildingController::class, 'index'])->name('buildings.index');
            Route::get('/buildings/{building}', [BuildingController::class, 'show'])->whereNumber('building')->name('buildings.show');
        });
        // ...
    });
```

> **Note for backward compatibility:** during the transition, the old top-level
> routes (`/dashboard`, `/buildings`) remain mapped to the same controllers via
> a compatibility layer in `routes/web.php`. We add the prefixed `/bms/...`
> routes alongside, then phase out the unprefixed routes in a later release.

## Module config file

```php
<?php
// app/Modules/BMS/config.php
return [
    'name' => 'Nexus BMS',
    'version' => '1.0.0',
    'description' => 'Building Management System',
    'theme' => 'nexus-bms',       // default theme for this module
    'route_prefix' => 'bms',       // URL prefix (optional)
    'menu' => [
        // shown in the side navigation when this module is active
        ['key' => 'dashboard',  'label' => 'Dashboard',   'icon' => 'fa-gauge',     'route' => 'bms.dashboard'],
        ['key' => 'buildings',  'label' => 'Buildings',   'icon' => 'fa-building',  'route' => 'bms.buildings.index'],
        ['key' => 'floors',     'label' => 'Floor Plans', 'icon' => 'fa-map',       'route' => 'bms.floors.index'],
        // ...
    ],
    'permissions' => [
        // permissions seeded for this module — used by BmsPermissionSeeder
        'bms.dashboard'  => ['view'],
        'bms.buildings'  => ['view', 'create', 'edit', 'delete'],
        'bms.floors'     => ['view', 'edit'],
        'bms.equipment'  => ['view', 'create', 'edit', 'delete'],
        'bms.alarms'     => ['view', 'edit'],
        'bms.schedules'  => ['view', 'create', 'edit', 'delete'],
    ],
];
```

## Core layer structure

`app/Core/` is also organized as sub-namespaces, but as a single library
(no ServiceProvider per Core sub-component; one `CoreServiceProvider`
registers everything).

```
app/Core/
├── Providers/
│   └── CoreServiceProvider.php
├── Auth/
│   ├── Controllers/LoginController.php
│   └── Models/User.php
├── Permissions/
│   ├── Middleware/CheckPermission.php
│   ├── Models/Role.php
│   ├── Models/Permission.php
│   └── BladeDirectives.php   ← @hasPermission, @isRole
├── Users/
│   └── Controllers/UserController.php
├── AuditLog/
│   ├── Middleware/LogActivity.php
│   ├── Models/ActivityLog.php
│   └── Controllers/LogController.php
├── Notifications/
│   ├── NotificationService.php
│   └── Channels/
│       ├── LineNotifyChannel.php
│       └── MailChannel.php
├── Settings/
│   ├── Models/SystemSetting.php
│   └── Controllers/SettingController.php
├── Reports/
│   ├── Controllers/ReportController.php
│   ├── Models/Report.php
│   └── Generators/
│       ├── HtmlReportGenerator.php
│       └── CsvReportGenerator.php
├── Backup/
│   └── Controllers/BackupController.php
├── Assets/                ← generic Equipment/Asset model used by modules
│   ├── Models/Asset.php   (renamed from Equipment, alias kept)
│   ├── Models/AssetCategory.php
│   └── Models/AssetStatusLog.php
├── Alarms/
│   ├── Models/Alarm.php
│   ├── Models/AlarmEvent.php
│   └── Controllers/AlarmController.php
├── Schedules/
│   ├── Models/Schedule.php
│   ├── Models/ScheduleDevice.php
│   ├── Models/ScheduleRun.php
│   ├── Controllers/ScheduleController.php
│   └── Console/Commands/RunSchedulesCommand.php
└── Theme/
    ├── ThemeManager.php
    ├── ThemeRegistry.php
    └── ViewFinder.php
```

## Widget engine

A Widget is a class implementing `BaseWidget` that knows how to render itself
from a parameter array.

```php
<?php
namespace App\Widgets;

abstract class BaseWidget
{
    abstract public function render(array $params): string;
    abstract public function defaultParams(): array;
    public function permissions(): array { return []; }   // optional
}
```

Example: KPI card widget

```php
<?php
namespace App\Widgets;

class KpiCardWidget extends BaseWidget
{
    public function defaultParams(): array
    {
        return ['title' => '', 'value' => 0, 'unit' => '', 'trend' => null, 'icon' => 'fa-chart-bar', 'color' => '#1d4ed8'];
    }

    public function render(array $params): string
    {
        $p = array_merge($this->defaultParams(), $params);
        return view('widgets.kpi-card', $p)->render();
    }
}
```

Blade component `<x-widget>` dispatches:

```php
// resources/views/components/widget.blade.php
@php
    $widget = app('widgets')->resolve($type);
    echo $widget->render($attributes->getAttributes());
@endphp
```

Usage in any module's view:

```blade
<x-widget type="kpi-card" title="Today Energy" value="28100" unit="kWh" trend="+3.2%" />
<x-widget type="trend-chart" :data="$energyTrend" />
<x-widget type="alarm-table" :alarms="$recentAlarms" />
```

## How a new module is added

To add **Nexus WMS** after the refactor:

1. Create directory tree:
   ```
   mkdir -p app/Modules/WMS/{Providers,Http/Controllers,Models,Services,Database/Seeders}
   ```

2. Create `WmsServiceProvider.php` using the template above.

3. Create `routes.php` and `config.php`.

4. Add the provider to `bootstrap/providers.php`.

5. Run `php artisan db:seed --class=WmsPermissionSeeder` to register the
   module's permissions.

6. (Optional) Add a theme at `resources/views/themes/nexus-wms/`.

7. Drop in controllers, models, views.

Result: WMS module is live, has its own URL prefix (`/wms/...`), its own
permissions (`wms.*`), its own menu entries.

## Naming conventions

| Element            | Convention                                              | Example                          |
|--------------------|---------------------------------------------------------|----------------------------------|
| Module namespace   | `App\Modules\<Name>`                                    | `App\Modules\BMS`                |
| Module config key  | `modules.<name>`                                        | `modules.bms.theme`              |
| Permission key     | `<module>.<resource>.<action>`                          | `bms.buildings.edit`             |
| Route name         | `<module>.<resource>.<action>`                          | `bms.buildings.show`             |
| URL prefix         | `/<module>/...` (lowercase)                             | `/bms/buildings/42`              |
| View key (theme)   | `<theme>::<resource>.<view>`                            | `nexus-bms::buildings.index`     |
| View key (legacy)  | `<resource>.<view>` (still works via fallback resolver) | `buildings.index`                |
| Widget name        | kebab-case                                              | `kpi-card`, `process-mimic`      |
| Theme directory    | kebab-case                                              | `nexus-bms`, `nexus-scada`       |

## Dependencies between layers

```
   Modules  ─────────┐
       │             │
       ▼             ▼
     Core      Integrations
       │             │
       └──────┬──────┘
              ▼
        Laravel + DB
```

**Allowed:**
- Modules use Core, Integrations
- Modules use other Modules' **interfaces** only if a Core abstraction exists (no direct cross-module class imports)
- Core uses Laravel
- Integrations use Laravel

**Forbidden:**
- Core imports from Modules
- One Module imports from another Module directly
- Integrations imports from Modules
- Anything imports Laravel internals (`Illuminate\Foundation\...`) — use facades / contracts
