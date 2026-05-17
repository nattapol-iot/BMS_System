# System Architecture — Nexus Platform

The target architecture after refactor. This is **not** a rewrite — it is
the destination the codebase will gradually arrive at via the phased plan
in `REFACTOR_PLAN.md`.

## Vision

> One Laravel application, multiple industrial product modules, shared
> infrastructure, swappable UI themes per module.

```
                              ╔═══════════════════════════╗
                              ║      Nexus Platform       ║
                              ║   (single Laravel app)    ║
                              ╚═══════════════════════════╝
                                          │
        ┌──────────────────┬──────────────┼──────────────┬───────────────┐
        ▼                  ▼              ▼              ▼               ▼
    ╔════════╗         ╔═══════╗      ╔═══════╗     ╔════════╗      ╔═══════╗
    ║ SCADA  ║         ║  BMS  ║      ║  WMS  ║     ║ Energy ║      ║ IIoT  ║
    ╚════════╝         ╚═══════╝      ╚═══════╝     ╚════════╝      ╚═══════╝
        │                  │              │              │               │
        └──────────────────┴──────────────┴──────────────┴───────────────┘
                                          │
                                          ▼
                    ┌─────────────────────────────────────────────┐
                    │              Core Platform                  │
                    │  Auth · RBAC · Audit · Notify · Settings    │
                    │  Reports · Backup · Alarms · Schedules      │
                    │  Assets · Theme · Widgets                   │
                    └─────────────────────────────────────────────┘
                                          │
                                          ▼
                    ┌─────────────────────────────────────────────┐
                    │           Integration Layer                 │
                    │   Modbus TCP · Modbus RTU · MQTT · OPC UA   │
                    │   REST · BACnet (future)                    │
                    └─────────────────────────────────────────────┘
                                          │
                                          ▼
                              ┌──────────────────┐
                              │   Edge Gateways  │
                              │  Python / ESP32  │
                              │  Node-RED / etc  │
                              └──────────────────┘
                                          │
                                          ▼
                              ┌──────────────────┐
                              │   Field Devices  │
                              │  PLC / Meters /  │
                              │  Sensors / DDC   │
                              └──────────────────┘
```

## Five layers

### Layer 1 — Modules (Product features)

Each module is a self-contained product. It owns its own:

- Controllers
- Models (where the schema is truly module-specific)
- Routes file (loaded by the Module ServiceProvider)
- Views (typically inside its theme)
- Seeders
- Console commands (if any)
- Configuration

Modules **MUST** depend only on `Core/` and `Integrations/`. They **MUST NOT**
import classes from other modules. Sharing is via the Core layer.

Initial modules:

| Module       | Status                | Lives in                    |
|--------------|-----------------------|------------------------------|
| BMS          | Existing, to extract  | `app/Modules/BMS/`           |
| Energy       | Existing, to extract  | `app/Modules/Energy/`        |
| SCADA        | Skeleton only         | `app/Modules/SCADA/`         |
| WMS          | Skeleton only         | `app/Modules/WMS/`           |
| IIoT         | Skeleton only         | `app/Modules/IIoT/`          |

### Layer 2 — Core Platform

Generic infrastructure usable by any module. Lives in `app/Core/`.

| Component       | Responsibility                                                       |
|-----------------|----------------------------------------------------------------------|
| Auth            | Login, logout, session, password reset, 2FA-ready                    |
| Permissions     | RBAC: roles, permissions, `CheckPermission` middleware, `@hasPermission` |
| Users           | User CRUD, profile, avatar, locale                                   |
| AuditLog        | Activity logging, viewer, retention                                  |
| Notifications   | Email + LINE Notify + (future) SMS, Slack, Webhook                   |
| Settings        | Key-value system settings, per-tenant overrides                      |
| Reports         | Report generation framework (CSV/PDF/HTML)                           |
| Backup          | DB dump, file backup, restore                                        |
| Alarms          | Generic alarm engine with `module` discriminator                     |
| Schedules       | Time-based job runner used by any module                             |
| Assets          | Generic "thing being monitored" — used by BMS as Equipment, by SCADA as Tag, by WMS as Item |
| Theme           | Theme registration, view path resolution                             |
| Widgets         | Widget registry + base classes                                       |

### Layer 3 — Widgets

Reusable UI components, each implemented once and consumed by all modules.

```
app/Widgets/
├── BaseWidget.php          ← abstract base, defines render() contract
├── KpiCardWidget.php       ← number + label + trend %
├── TrendChartWidget.php    ← ApexCharts area/line wrapper
├── GaugeWidget.php         ← circular gauge for any 0-100 value
├── AlarmTableWidget.php    ← scrollable alarm list
├── DeviceStatusWidget.php  ← grid of equipment with status dots
├── ProcessMimicWidget.php  ← SVG mimic (floor plan, P&ID, conveyor)
└── (custom widgets per module extend BaseWidget)
```

Widgets are rendered in Blade via:

```blade
<x-widget :type="'kpi-card'" :title="'Today Energy'" :value="28100" unit="kWh" trend="+3.2%" />
```

The `<x-widget>` Blade component dispatches to the registered widget class via
`WidgetRegistry`.

### Layer 4 — Integrations

Protocol bridges. Live in `app/Integrations/`.

```
app/Integrations/
├── Rest/                       ← already exists as Api/IoTController
│   ├── IoTController.php
│   ├── VerifyApiToken.php
│   └── routes.php
├── Modbus/
│   ├── ModbusTcpClient.php     ← PHP-side Modbus polling (optional, native)
│   ├── ModbusRtuClient.php
│   └── ModbusGatewayBridge.php ← receives data from external Python gateways
├── Mqtt/
│   ├── MqttSubscriber.php      ← Laravel command running php-mqtt/client
│   └── MqttPublisher.php
├── OpcUa/
│   └── OpcUaBridge.php         ← stub, integrate via external Python/Node gateway
└── Bacnet/
    └── BacnetBridge.php        ← stub
```

**Rule:** Controllers and Module business logic **MUST NOT** import protocol
libraries directly. They go through the `Integrations` layer.

### Layer 5 — Edge & Field

Outside the Laravel codebase. The `tools/gateways/` directory contains the
reference implementations (Python, ESP32, Node-RED). These already exist and
will continue to talk to the platform via Integrations layer.

## Theme system

Each module typically has its own theme but a theme can also be shared. Themes
control look-and-feel only — they don't add functionality.

```
resources/views/themes/
├── nexus-bms/         ← navy + electric blue, Inter+Prompt fonts (current)
│   ├── layouts/app.blade.php
│   ├── dashboard/
│   ├── buildings/
│   ├── ...
│   └── theme.json     ← name, colors, fonts, default for which modules
├── nexus-scada/       ← industrial dark, monospace, mimic-style
├── nexus-energy/      ← reuse nexus-bms with overrides
└── nexus-wms/         ← warehouse-style, larger text, scanner-friendly
```

`ThemeManager` is a Laravel service:

```php
ThemeManager::current();                       // → 'nexus-bms'
ThemeManager::view('dashboard.index');         // → 'themes.nexus-bms.dashboard.index'
ThemeManager::asset('css/nexus.css');          // → /themes/nexus-bms/css/nexus.css
ThemeManager::for($module)->view('dashboard'); // → resolves per-module default theme
```

Behind the scenes, a custom Blade view-finder prepends the active theme path
so existing `view('dashboard.index')` calls keep working.

## Authentication & permissions

Permissions table already keys by `module` string. The refactor only **enforces**
this convention more strictly:

```
permissions
├── bms.buildings.view
├── bms.buildings.create
├── bms.equipment.edit
├── energy.dashboard.view
├── energy.rates.edit
├── scada.tags.view
├── wms.inventory.view
└── core.users.create
```

Old permissions like `users.create` map to `core.users.create`. A migration
adds a `category` column and rewrites existing rows; UI continues to work
because `User::hasPermission()` accepts either form during the transition.

## Database strategy

**Schema changes are additive, never destructive.** Every refactor migration:

1. Adds new columns / tables alongside old ones
2. Backfills data
3. Updates code to read from new
4. Marks old fields as deprecated in a `DEPRECATED.md`
5. (Future) drops old fields in a separate migration after sufficient soak time

No data loss. Ever.

## Routing

```
routes/
├── web.php                ← thin entry — just includes module route files
├── api.php                ← Integrations/Rest endpoints
├── console.php            ← scheduled commands (delegates to modules)
└── web/
    ├── auth.php           ← Core auth routes
    ├── core.php           ← Core routes (users, settings, logs, backup, reports)
    └── modules/
        ├── bms.php
        ├── energy.php
        ├── scada.php
        ├── wms.php
        └── iiot.php
```

Each module's `routes.php` is auto-loaded by its ServiceProvider, so adding a
new module is `composer require nexus/module-foo` + add ServiceProvider →
its routes appear automatically.

## Module ServiceProvider pattern

Every module has a ServiceProvider that wires it into Laravel:

```php
// app/Modules/BMS/Providers/BmsServiceProvider.php
class BmsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config.php', 'bms');
    }

    public function boot(): void
    {
        // Routes
        $this->loadRoutesFrom(__DIR__.'/../routes.php');

        // Views — accessible as view('bms::dashboard.index')
        $this->loadViewsFrom(__DIR__.'/../Views', 'bms');

        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        // Permissions seed
        if ($this->app->runningInConsole()) {
            $this->commands([Commands\SeedBmsPermissions::class]);
        }

        // Widgets
        WidgetRegistry::register('building-card', BuildingCardWidget::class);
    }
}
```

Registered in `bootstrap/providers.php`.

## UI direction (per the brief)

| Concern        | Direction                                                |
|----------------|----------------------------------------------------------|
| Theme          | Dark by default, industrial palette                      |
| Layout         | 16:9-optimised, widget grid (12-col)                     |
| Density        | Higher info density per screen (SCADA-style)             |
| Charts         | ApexCharts (current) — adequate for all modules          |
| Real-time      | Polling now → WebSocket (Laravel Reverb) later           |
| Mimics         | SVG-based, drag-drop editor (extends current floor plan) |
| Mobile         | Responsive, but desktop-first (industrial operators)     |

## Non-goals (explicit)

To prevent scope creep, here is what **NOT** to do during this refactor:

1. **No multi-tenancy.** One DB, one customer per install for now.
2. **No microservices.** This is one Laravel app, period. Modules are logical, not network.
3. **No new programming language.** All Laravel/PHP. (Edge gateways stay Python/Arduino/Node-RED outside.)
4. **No frontend framework swap.** Bootstrap 5 + ApexCharts + vanilla JS. No React/Vue.
5. **No big-bang rewrite.** Phased migration, each phase ships independently.

## Acceptance criteria — when is the refactor "done"?

The refactor is complete when **all** of the following are true:

- [ ] `app/Modules/BMS/` contains all BMS-specific code; `app/Models/` has only Core models
- [ ] BMS views live in `resources/views/themes/nexus-bms/`
- [ ] Stub modules (SCADA, WMS, IIoT) exist with placeholder routes that 404 gracefully or show "coming soon"
- [ ] `ThemeManager` resolves views from theme paths
- [ ] At least 3 reusable Widgets implemented and used in BMS views
- [ ] Modbus + MQTT + OPC UA integration interfaces exist (even if stubbed for OPC UA)
- [ ] All 83 existing web routes still respond with the same status codes
- [ ] All 17 feature tests still pass
- [ ] All API endpoints (4 of them) still respond
- [ ] No data migration required; existing DB works as-is
- [ ] `php artisan iot:simulate` still works
- [ ] CI workflow passes on master
- [ ] One commit per phase (no monolithic refactor commit)

See `REFACTOR_PLAN.md` for how we get there.
