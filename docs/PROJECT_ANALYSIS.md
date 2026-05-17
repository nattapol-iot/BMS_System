# Project Analysis — Current State (as of 2026-05-17)

A complete inventory of the existing Laravel 12 codebase before refactor into
the multi-module **Nexus Platform**.

## At a glance

| Aspect              | Count                                        |
|---------------------|----------------------------------------------|
| Web controllers     | 13 + Auth (LoginController) = 14             |
| API controllers     | 1 (`Api/IoTController`)                      |
| Eloquent models     | 19                                           |
| Middleware          | 4                                            |
| Services            | 1 (`NotificationService`)                    |
| View composers      | 1 (`LayoutComposer`)                         |
| Artisan commands    | 2 (`RunSchedulesCommand`, `SimulateIotCommand`) |
| Migrations          | 26                                           |
| Web routes          | 83                                           |
| API routes          | 4                                            |
| Scheduled jobs      | 1 (`schedules:run` every minute)             |
| Feature tests       | 17 (passing)                                 |
| View folders        | 14 (+ `layouts/`, `auth/`, `components/`)    |
| Edge gateway scripts| 4 (Modbus, MQTT, ESP32, Node-RED)            |
| Docs                | 3 (IOT_INTEGRATION, DEVICE_ONBOARDING, README) |

## Component classification — by reusability

Each item is tagged by where it should live after refactor:

- **CORE** — truly shared infrastructure (auth, RBAC, audit, settings, notify)
- **SHARED-CONCEPT** — concept needed by multiple modules but each module has its own data
- **MODULE: BMS** — building management specific
- **MODULE: ENERGY** — energy monitoring specific (already separable)
- **INTEGRATION** — protocol bridges (IoT API)
- **WIDGET** — UI element reused across modules

### Controllers

| File                              | Lines* | Classification          | Notes                                           |
|-----------------------------------|--------|-------------------------|-------------------------------------------------|
| `Auth/LoginController.php`        | ~70    | **CORE**                | Login/logout, generic                           |
| `LanguageController.php`          | tiny   | **CORE**                | TH/EN switcher                                  |
| `UserController.php`              | ~80    | **CORE**                | RBAC user management                            |
| `SettingController.php`           | ~30    | **CORE**                | System settings KV store                        |
| `BackupController.php`            | ~150   | **CORE**                | mysqldump backup mgmt                           |
| `ReportController.php`            | ~200   | **CORE** + module hooks | Framework is core; report *types* are per-module|
| `LogController.php`               | small  | **CORE**                | Audit log viewer                                |
| `AlarmController.php`             | medium | **SHARED-CONCEPT**      | Every module has alarms — needs polymorphism    |
| `DashboardController.php`         | ~180   | **MODULE: BMS**         | BMS-specific tiles                              |
| `BuildingController.php`          | medium | **MODULE: BMS**         | Buildings/Floors/Rooms are BMS-only             |
| `FloorController.php`             | small  | **MODULE: BMS**         | Floor plan editor                               |
| `EquipmentController.php`         | ~110   | **SHARED-CONCEPT**      | "Asset" in SCADA, "Device" in WMS — same idea   |
| `ScheduleController.php`          | ~150   | **SHARED-CONCEPT**      | Time-based automation reused by all             |
| `EnergyController.php`            | ~260   | **MODULE: ENERGY**      | Energy analytics                                |
| `Api/IoTController.php`           | ~150   | **INTEGRATION: REST**   | Move to `app/Integrations/Rest/`                |

\* Approximate; exact LOC differs slightly.

### Models

| Model                  | Classification      | Stays in / moves to                          |
|------------------------|---------------------|----------------------------------------------|
| `User`                 | CORE                | `app/Core/Auth/Models/`                      |
| `Role`                 | CORE                | `app/Core/Permissions/Models/`               |
| `Permission`           | CORE                | `app/Core/Permissions/Models/`               |
| `ActivityLog`          | CORE                | `app/Core/AuditLog/Models/`                  |
| `SystemSetting`        | CORE                | `app/Core/Settings/Models/`                  |
| `Report`               | CORE                | `app/Core/Reports/Models/`                   |
| `Alarm`                | SHARED-CONCEPT      | `app/Core/Alarms/Models/` (with `module` col)|
| `AlarmEvent`           | SHARED-CONCEPT      | `app/Core/Alarms/Models/`                    |
| `Building`             | MODULE: BMS         | `app/Modules/BMS/Models/`                    |
| `Floor`                | MODULE: BMS         | `app/Modules/BMS/Models/`                    |
| `Room`                 | MODULE: BMS         | `app/Modules/BMS/Models/`                    |
| `Equipment`            | SHARED-CONCEPT      | `app/Core/Assets/Models/` (renamed `Asset`?) |
| `EquipmentCategory`    | SHARED-CONCEPT      | `app/Core/Assets/Models/`                    |
| `EquipmentStatusLog`   | SHARED-CONCEPT      | `app/Core/Assets/Models/`                    |
| `EnergyMeter`          | MODULE: ENERGY      | `app/Modules/Energy/Models/`                 |
| `EnergyLog`            | MODULE: ENERGY      | `app/Modules/Energy/Models/`                 |
| `Schedule`             | SHARED-CONCEPT      | `app/Core/Schedules/Models/`                 |
| `ScheduleDevice`       | SHARED-CONCEPT      | `app/Core/Schedules/Models/`                 |
| `ScheduleRun`          | SHARED-CONCEPT      | `app/Core/Schedules/Models/`                 |

### Middleware

All 4 are **CORE** — no module-specific middleware exists yet.

| Middleware           | Move to                              |
|----------------------|--------------------------------------|
| `SetLocale`          | `app/Core/Http/Middleware/`          |
| `LogActivity`        | `app/Core/AuditLog/Middleware/`      |
| `CheckPermission`    | `app/Core/Permissions/Middleware/`   |
| `VerifyApiToken`     | `app/Integrations/Rest/Middleware/`  |

### Services

| Service                  | Classification    | Move to                                  |
|--------------------------|-------------------|------------------------------------------|
| `NotificationService`    | **CORE**          | `app/Core/Notifications/`                |

Need to **add** these new services:
- `app/Core/Theme/ThemeManager.php` — view resolver per theme
- `app/Widgets/WidgetRegistry.php` — register/render widgets
- `app/Integrations/Modbus/ModbusClient.php` — wrap pymodbus equivalent in PHP
- `app/Integrations/Mqtt/MqttClient.php` — Laravel queue + Mosquitto bridge

### Console Commands

| Command                 | Classification        | Move to                                       |
|-------------------------|-----------------------|-----------------------------------------------|
| `RunSchedulesCommand`   | SHARED-CONCEPT        | `app/Core/Schedules/Console/`                 |
| `SimulateIotCommand`    | INTEGRATION           | `app/Integrations/Rest/Console/`              |

### Views

| Folder                 | Classification         | Move to                                            |
|------------------------|------------------------|----------------------------------------------------|
| `auth/`                | CORE                   | stays in `resources/views/auth/` (core layout)     |
| `layouts/app.blade.php`| CORE + Theme           | `resources/views/themes/nexus-bms/layouts/`        |
| `components/`          | shared partials        | `resources/views/components/` (Laravel-native)     |
| `dashboard/`           | MODULE: BMS            | `resources/views/themes/nexus-bms/dashboard/`      |
| `buildings/`           | MODULE: BMS            | `resources/views/themes/nexus-bms/buildings/`      |
| `floors/`              | MODULE: BMS            | `resources/views/themes/nexus-bms/floors/`         |
| `equipment/`           | SHARED-CONCEPT         | `resources/views/themes/nexus-bms/equipment/` (with override hooks) |
| `alarms/`              | SHARED-CONCEPT         | `resources/views/themes/nexus-bms/alarms/`         |
| `schedules/`           | SHARED-CONCEPT         | `resources/views/themes/nexus-bms/schedules/`      |
| `energy/`              | MODULE: ENERGY         | `resources/views/themes/nexus-energy/`             |
| `reports/`             | CORE                   | `resources/views/core/reports/`                    |
| `users/`               | CORE                   | `resources/views/core/users/`                      |
| `settings/`            | CORE                   | `resources/views/core/settings/`                   |
| `logs/`                | CORE                   | `resources/views/core/logs/`                       |

### Routes

`routes/web.php` is **a single 83-route file** with all modules mixed.
**Plan:** keep it as a thin entry that includes per-module route files:

```php
require __DIR__ . '/web/core.php';
require __DIR__ . '/web/modules/bms.php';
require __DIR__ . '/web/modules/energy.php';
// future: scada.php, wms.php, iiot.php
```

### Migrations (26 files)

All migrations stay in `database/migrations/` (Laravel best-practice). Tagging
them by module is enough for documentation — physical move not needed and
would break migration order.

| Migration                                            | Module      |
|------------------------------------------------------|-------------|
| `*_create_roles_table`                               | CORE        |
| `*_create_permissions_table`                         | CORE        |
| `*_create_role_permissions_table`                    | CORE        |
| `*_add_bms_fields_to_users_table`                    | mixed (will be re-classified after refactor) |
| `*_create_buildings_table`                           | BMS         |
| `*_create_floors_table`                              | BMS         |
| `*_create_rooms_table`                               | BMS         |
| `*_create_equipment_categories_table`                | SHARED      |
| `*_create_equipment_table`                           | SHARED      |
| `*_create_equipment_status_logs_table`               | SHARED      |
| `*_add_position_to_equipment`                        | BMS (floor plan) |
| `*_create_alarms_table`                              | SHARED      |
| `*_create_alarm_events_table`                        | SHARED      |
| `*_create_energy_meters_table`                       | ENERGY      |
| `*_add_equipment_and_solar_to_energy_meters`         | ENERGY      |
| `*_create_energy_logs_table`                         | ENERGY      |
| `*_create_schedules_table`                           | SHARED      |
| `*_create_schedule_devices_table`                    | SHARED      |
| `*_add_overrides_to_schedule_devices`                | SHARED      |
| `*_create_schedule_runs_table`                       | SHARED      |
| `*_create_reports_table`                             | CORE        |
| `*_create_system_settings_table`                     | CORE        |
| `*_create_activity_logs_table`                       | CORE        |

## Tightly-coupled spots that need attention

These are the places where a naive "move file" will break things:

1. **`User::hasPermission(string $module, string $action)`** — model method
   queries permissions table by `module` string. Already module-aware, will
   continue to work. New modules just need to seed their own permissions.

2. **`LayoutComposer`** — caches `Building::all()` for the building selector in
   the header. After refactor, header dropdowns should come from a `ThemeManager`
   that knows which module is active.

3. **`AppServiceProvider`** — registers Blade directives `@hasPermission` and
   `@isRole`. These stay; just move into a `CoreServiceProvider`.

4. **`routes/web.php`** — uses `use App\Http\Controllers\...` imports. Need to
   update imports as controllers move. Mitigation: keep old namespaces as
   class aliases for one transition period.

5. **Feature tests** — reference `App\Models\User` directly. Add an alias or
   update test imports in the same commit that moves the model.

6. **Frontend `nexus.css` / `nexus.js`** — global stylesheet. Will move into
   `public/themes/nexus-bms/` and be loaded via the Theme manager. Other themes
   will have their own CSS/JS bundles.

## Frontend asset inventory

| File                          | Size  | Classification | Future home                              |
|-------------------------------|-------|----------------|------------------------------------------|
| `public/css/nexus.css`        | ~30KB | Theme: BMS     | `public/themes/nexus-bms/css/`           |
| `public/js/nexus.js`          | ~5KB  | Theme: BMS     | `public/themes/nexus-bms/js/`            |
| Bootstrap 5.3 CDN             | —     | shared         | stays                                    |
| ApexCharts CDN                | —     | shared         | stays — used by all dashboards           |
| Font Awesome 6 CDN            | —     | shared         | stays                                    |

## What is already module-friendly

Good news — some parts of the codebase are already well-isolated and will
require **no changes**:

- **`routes/api.php`** — IoT endpoints already in `Api/` namespace
- **`tools/gateways/`** — edge gateways are standalone, no Laravel coupling
- **`docs/`** — documentation files
- **`.github/workflows/tests.yml`** — CI is module-agnostic
- **GitHub Actions** — will continue to work as-is
- **Test suite** — uses real DB, will keep passing as long as table schemas don't change

## What is NOT done yet (and should be addressed in the new architecture)

These were already on the roadmap before this pivot:

- WebSocket / Laravel Reverb for live push (instead of 30s polling)
- Per-gateway API tokens (currently single shared `IOT_API_TOKEN`)
- Equipment-level energy roll-up table (for performance at scale)
- Multi-tenant isolation (one platform, many customers)
- Theme switcher in UI (currently hardcoded BMS theme)

The new module structure makes all of these much easier to layer in.
