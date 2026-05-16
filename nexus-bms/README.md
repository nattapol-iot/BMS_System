# Nexus BMS Platform

[![Tests](https://github.com/nattapol-iot/BMS_System/actions/workflows/tests.yml/badge.svg)](https://github.com/nattapol-iot/BMS_System/actions/workflows/tests.yml)

A production-grade Building Management System (BMS) built with Laravel 12 and
Bootstrap 5. Covers building/equipment management, alarm response, energy
analytics, schedule automation, role-based access control, audit logging, and a
REST API for IoT device integration.

## Highlights

- **12 modules** — Dashboard, Buildings, Floor Plans, Equipment, Alarms,
  Energy, Schedules (Overview / Calendar / Device Settings), Reports, Users,
  Settings, Logs
- **Full CRUD** on Buildings, Equipment, Schedules, Users, Reports
- **Role-based ACL** with 11 modules × 5 actions (55 permissions) wired through
  middleware and Blade directives
- **Live polling** — sidebar alarm badge refreshes every 30s, View Composer
  caches nav data
- **REST API for IoT** — push equipment status & meter readings, get live
  dashboard snapshot
- **Notifications** — LINE Notify + email triggered on critical alarms
- **Scheduled equipment control** — `php artisan schedules:run` toggles
  equipment per schedule windows
- **Real backup** — mysqldump-based, downloadable & deletable from Settings
- **Real reports** — generates CSV (Excel-compatible UTF-8 BOM) and printable
  HTML / PDF
- **Floor plan editor** — drag-drop equipment dots on SVG, save via AJAX
- **Feature test suite** — 17 tests / 64 assertions (Auth, Permissions, CRUD,
  IoT API)
- **Bilingual** — Thai + English with header language switcher

## Requirements

- PHP 8.2+ with `pdo_mysql`, `openssl`, `mbstring`, `curl`, `fileinfo`
- MySQL 8.0+ or MariaDB 10.6+
- Composer 2.x
- `mysqldump` available on PATH (or under XAMPP / MariaDB install dir) for the
  backup feature
- Node.js 18+ and npm, only needed when modifying Vite assets

## Installation

```bash
# 1. Clone & enter
git clone <repo-url>
cd nexus-bms

# 2. PHP deps
composer install

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Configure DB + IoT token in .env
#    DB_DATABASE=nexus_bms
#    DB_USERNAME=root
#    DB_PASSWORD=
#    IOT_API_TOKEN=nexus-iot-secret-2026

# 5. Migrate + seed (roles, permissions, users, buildings, equipment,
#    alarms, 30 days of energy logs, schedules, settings)
php artisan migrate
php artisan db:seed

# 6. Build assets (optional)
npm install && npm run build
```

## Running

```bash
php artisan serve              # http://localhost:8000
```

For a fuller dev loop (Vite watcher + queue + tail logs):

```bash
composer run dev
```

To execute due schedules every minute in production, register the Laravel
scheduler with cron / Task Scheduler:

```bash
* * * * * cd /path/to/nexus-bms && php artisan schedule:run >> /dev/null 2>&1
```

## Default Accounts

| Email             | Password       | Role     | Permissions                            |
|-------------------|----------------|----------|----------------------------------------|
| admin@nexus.com   | admin1234      | Admin    | All 55                                 |
| manager@nexus.com | manager1234    | Manager  | 48 (no users.create/delete, settings)  |
| operator@nexus.com| operator1234   | Operator | 25 (view+export all, edit equipment/alarms/schedules) |
| viewer@nexus.com  | viewer1234     | Viewer   | 8 (view-only on most modules)          |

> The viewer account is created via the test harness; if missing, sign in as
> admin and create it from `/users/create`.

## Routes (highlights)

### Web (auth-required, permission-gated)

| Module      | Routes                                                       |
|-------------|--------------------------------------------------------------|
| Dashboard   | `/dashboard`                                                 |
| Buildings   | `/buildings`, `/buildings/create`, `/buildings/{id}/edit`    |
| Floors      | `/floors`, `POST /floors/{id}/equipment-position` (drag-save)|
| Equipment   | `/equipment`, `/equipment/create`, `/equipment/{id}/edit`    |
| Alarms      | `/alarms`, `POST /alarms/{id}/{acknowledge,resolve,silence,assign}` |
| Energy      | `/energy`                                                    |
| Schedules   | `/schedules`, `/calendar`, `/device-settings`, full CRUD     |
| Reports     | `/reports`, `POST /reports`, `GET /reports/{id}/download`    |
| Users       | `/users`, full CRUD + `/users/{id}/deactivate`               |
| Settings    | `/settings`, `POST /settings/backup-now`                     |
| Logs        | `/logs`                                                      |

### REST API (token-required via `X-API-Token` header)

| Method | Endpoint                                  | Purpose                                |
|--------|-------------------------------------------|----------------------------------------|
| POST   | `/api/iot/equipment/{code}/status`        | Update equipment status + health       |
| POST   | `/api/iot/meter/{name}/reading`           | Push energy meter reading              |
| GET    | `/api/dashboard/live`                     | Real-time stats snapshot               |

When equipment transitions to `offline` or health drops below 50%, an alarm is
auto-created and routed through `NotificationService` (LINE + email).

#### Example: push equipment status

```bash
curl -X POST http://localhost:8000/api/iot/equipment/AHU-07/status \
     -H "X-API-Token: nexus-iot-secret-2026" \
     -H "Content-Type: application/json" \
     -d '{"status":"active","health_score":92}'
```

#### Example: push meter reading

```bash
curl -X POST "http://localhost:8000/api/iot/meter/Nexus%20Tower%20A%20Electricity/reading" \
     -H "X-API-Token: nexus-iot-secret-2026" \
     -H "Content-Type: application/json" \
     -d '{"value":125.5,"peak_demand":85.2}'
```

#### Example: live dashboard

```bash
curl http://localhost:8000/api/dashboard/live -H "X-API-Token: nexus-iot-secret-2026"
```

## Permissions

Each route is protected by the `can.permission:{module},{action}` middleware.
Actions are `view`, `create`, `edit`, `delete`, `export`. In Blade views:

```blade
@hasPermission('users', 'create')
    <a href="{{ route('users.create') }}">Add User</a>
@endhasPermission

@isRole('admin')
    <button>Admin-only feature</button>
@endisRole
```

## Backup

From `/settings` → **Backup tab** → **Run Backup Now**.

- Dumps the full `nexus_bms` database via `mysqldump` (auto-detected at
  `C:\xampp\mysql\bin\mysqldump.exe` or system PATH).
- Files stored under `storage/app/private/backups/`.
- Each backup is downloadable and deletable from the same tab.

## Reports

From `/reports` → **Generate Report**:

- Types: Equipment Status, Alarm Log, Energy Summary, Maintenance
- Formats: **PDF** (HTML with print stylesheet, browser → Save as PDF) or
  **Excel** (CSV with UTF-8 BOM for Thai)
- Files stored under `storage/app/private/reports/`, downloadable from the
  history table.

## Schedule Automation

```bash
php artisan schedules:run --dry-run    # preview which equipment would toggle
php artisan schedules:run              # actually toggle (logged to schedule_runs)
```

Registered in `routes/console.php` to run every minute with
`withoutOverlapping()`. Wire it up via cron / Windows Task Scheduler:

```text
* * * * * php /path/to/artisan schedule:run
```

## Floor Plan Editor

`/floors?building_id=X` shows the SVG floor plan. Users with
`floors.edit` permission see an **Edit Positions** button — toggling it makes
equipment dots draggable. **Save** persists `x_position`, `y_position` via
`POST /floors/{id}/equipment-position`.

## Testing

```bash
php artisan test --testsuite=Feature
```

Test suite (17 tests / 64 assertions):

- **AuthTest** — login/logout flow + dashboard guard
- **PermissionTest** — admin vs viewer route access matrix
- **CrudTest** — building / equipment / user create + cleanup
- **IoTApiTest** — token auth, equipment status, meter reading, dashboard JSON

Tests use `DatabaseTransactions` against the seeded `nexus_bms` MySQL DB
(configured in `phpunit.xml`); each test rolls back after running.

## Tech Stack

| Layer       | Choice                                                      |
|-------------|-------------------------------------------------------------|
| Backend     | PHP 8.2+, Laravel 12                                        |
| Frontend    | Bootstrap 5.3, ApexCharts, Font Awesome 6, Vite             |
| Database    | MySQL / MariaDB                                             |
| Fonts       | Google Fonts (Inter, Prompt — Thai)                         |
| Auth        | Session-based (Laravel built-in)                            |
| API auth    | Static token via `IOT_API_TOKEN` env + `VerifyApiToken` mw  |
| Caching     | Default file driver + Cache::remember in View Composer      |
| Notifications | LINE Notify (HTTP) + Laravel Mail                         |

## Database Schema

The project includes **23 migrations** / **23 tables**:

| Group            | Tables                                                          |
|------------------|-----------------------------------------------------------------|
| Auth & ACL       | users, roles, permissions, role_permissions, sessions           |
| Buildings        | buildings, floors, rooms                                        |
| Equipment        | equipment_categories, equipment (+ x/y_position), equipment_status_logs |
| Alarms           | alarms, alarm_events                                            |
| Energy           | energy_meters, energy_logs                                      |
| Schedules        | schedules, schedule_devices, schedule_runs                      |
| Reports          | reports                                                         |
| System           | system_settings, activity_logs                                  |
| Laravel internal | migrations, cache, jobs                                         |

## Project Structure (key directories)

```
nexus-bms/
├── app/
│   ├── Console/Commands/        # RunSchedulesCommand
│   ├── Http/
│   │   ├── Controllers/         # 14 web + 1 API controller
│   │   ├── Middleware/          # CheckPermission, VerifyApiToken, SetLocale, LogActivity
│   ├── Models/                  # 19 Eloquent models
│   ├── Services/                # NotificationService (LINE + email)
│   ├── View/Composers/          # LayoutComposer (nav cache)
│   └── Providers/AppServiceProvider.php  # Blade directives + composers
├── database/
│   ├── migrations/              # 23 migrations
│   └── seeders/                 # 10 seeders incl. PermissionSeeder
├── lang/                        # th/ + en/ translation files
├── public/
│   ├── css/nexus.css            # Theme
│   └── js/nexus.js              # Sidebar, polling, chart helpers
├── resources/views/             # Blade templates (12 module folders + layouts)
├── routes/
│   ├── web.php                  # Permission-grouped web routes
│   ├── api.php                  # Token-protected REST API
│   └── console.php              # Schedule registration
├── storage/app/private/
│   ├── backups/                 # mysqldump output
│   └── reports/                 # generated CSV / HTML reports
└── tests/Feature/               # 4 feature test files
```

## Local Notes

- The `.env.example` ships with sane local defaults for MySQL on `127.0.0.1`.
- If PHP warns about missing `sqlsrv` / `pdo_sqlsrv` extensions, those are
  unrelated to MySQL — comment out the `extension=` lines in `php.ini` if you
  want a clean console.
- The login form auto-fills nothing — use the table above for credentials.
- `php artisan route:list` lists every registered route with its middleware
  stack, which is the fastest way to verify ACL coverage.

## License

MIT
