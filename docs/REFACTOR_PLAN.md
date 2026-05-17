# Refactor Plan — From BMS to Nexus Platform

A phased, backward-compatible migration from the current single-app BMS to
the multi-module Nexus Platform. **Each phase ends with a green CI build and
all 17 existing feature tests passing.**

## Guiding principles

1. **No big-bang.** Each phase is mergeable on its own.
2. **Old works.** Existing URLs, routes, tests, DB queries keep working until
   the phase that explicitly replaces them.
3. **Aliases first, moves second.** When code moves namespace, leave a
   `class_alias()` behind for one phase so external references don't break.
4. **Tests before deletes.** Never delete the old until tests cover the new.
5. **Small commits.** Aim for ≤ 300 LOC changed per commit.

## Risk register

| Risk                              | Mitigation                                              |
|-----------------------------------|---------------------------------------------------------|
| Moving a model breaks FK migration history | Migrations reference table names (strings), not classes — safe |
| Renaming controllers breaks `route(...)` calls | Keep route **names** unchanged across moves       |
| View path changes break Blade `@extends('layouts.app')` | Theme manager prepends paths; legacy `layouts.app` keeps resolving |
| Tests use `App\Models\User`       | Either keep alias or update in same commit              |
| `tools/gateways/` already references API URLs | URLs unchanged — gateways unaffected                  |
| External integrations using the IoT API | API contract frozen — only internal class moves    |
| Seeders break after move          | Run `db:seed` after every phase as a smoke check        |

---

## Phase 0 — Documentation & Decision (THIS COMMIT)

**Goal:** Get architectural docs in place, no code changes yet.

- [x] Write `PROJECT_ANALYSIS.md`
- [x] Write `SYSTEM_ARCHITECTURE.md`
- [x] Write `MODULE_STRUCTURE.md`
- [x] Write `REFACTOR_PLAN.md` (this file)
- [ ] **STOP — wait for user approval before proceeding to Phase 1**

Acceptance: docs reviewed, plan confirmed, scope and pace agreed.

---

## Phase 1 — Skeleton (~ 1 commit)

**Goal:** Create the new directory layout. Nothing moves yet. All files have
README placeholders.

```bash
mkdir -p app/{Core/{Auth,Permissions,Users,AuditLog,Notifications,Settings,Reports,Backup,Alarms,Schedules,Assets,Theme},Modules/{BMS,Energy,SCADA,WMS,IIoT},Widgets,Integrations/{Rest,Modbus,Mqtt,OpcUa,Bacnet}}
mkdir -p resources/views/themes/{nexus-bms,nexus-scada,nexus-energy,nexus-wms,nexus-iiot}
```

Add `README.md` in each top-level directory describing its purpose and pointing
to `MODULE_STRUCTURE.md`.

**Files created:**
- `app/Core/README.md`
- `app/Modules/README.md`
- `app/Widgets/README.md`
- `app/Integrations/README.md`
- `resources/views/themes/README.md`
- `bootstrap/providers.php` — unchanged (no new providers active yet)

**Tests must pass.** No code moved. Just empty scaffolding.

Commit message: `Phase 1: Add Nexus Platform skeleton (Core/Modules/Widgets/Integrations/Themes)`

---

## Phase 2 — Theme Manager + view path fallback (~ 1 commit)

**Goal:** Introduce `ThemeManager` and configure view paths so that BOTH
`view('dashboard.index')` (legacy) and `view('nexus-bms::dashboard.index')`
(new) resolve to the same file.

Steps:
1. Create `app/Core/Theme/ThemeManager.php`
2. Create `config/themes.php` declaring `default => 'nexus-bms'`
3. Register a custom view finder that searches:
   - `resources/views/themes/{active}/...`
   - `resources/views/...` (legacy fallback)
4. **Copy** (not move) `resources/views/layouts/app.blade.php` to
   `resources/views/themes/nexus-bms/layouts/app.blade.php` — identical copy.
5. Smoke test: every page still renders.

After this phase, both view paths work. We will switch consumers in later phases.

Tests: 17/17 pass.

Commit: `Phase 2: ThemeManager + theme path resolver (backward-compatible)`

---

## Phase 3 — Core layer extraction (~ 3-4 commits)

**Goal:** Move CORE code into `app/Core/` namespace. Each commit moves one
logical group with full backward-compat aliases.

### Phase 3.1 — Auth & Permissions

- Move `app/Http/Controllers/Auth/LoginController` → `app/Core/Auth/Controllers/`
- Move `app/Http/Middleware/CheckPermission` → `app/Core/Permissions/Middleware/`
- Move `app/Models/{Role,Permission}` → `app/Core/Permissions/Models/`
- Add aliases: `class_alias(App\Core\Permissions\Models\Role::class, App\Models\Role::class)` in `AppServiceProvider::register()` for the transition.
- Update `routes/web.php` controller imports.

### Phase 3.2 — Audit & Notifications & Settings

- Move `LogActivity` middleware, `ActivityLog` model, `LogController` → `app/Core/AuditLog/`
- Move `NotificationService` → `app/Core/Notifications/`
- Move `SystemSetting`, `SettingController` → `app/Core/Settings/`

### Phase 3.3 — Reports & Backup & Users

- Move `ReportController`, `Report` model → `app/Core/Reports/`
- Move `BackupController` → `app/Core/Backup/`
- Move `UserController`, `User` model → `app/Core/Users/`

### Phase 3.4 — Shared Concepts (Alarms, Schedules, Assets)

- Move alarm models + controller → `app/Core/Alarms/`
- Move schedule models + controller + `RunSchedulesCommand` → `app/Core/Schedules/`
- Rename Equipment → Asset *conceptually* but **keep the table name `equipment`**
  for backward compat. Add `app/Core/Assets/Models/Asset.php` extending Equipment.

After Phase 3, `app/Models/` is empty (or contains only `_DEPRECATED.md`).
Tests: 17/17 pass.

Commits:
- `Phase 3.1: Move Auth + Permissions to app/Core`
- `Phase 3.2: Move AuditLog + Notifications + Settings to app/Core`
- `Phase 3.3: Move Reports + Backup + Users to app/Core`
- `Phase 3.4: Move Alarms + Schedules + Assets to app/Core`

---

## Phase 4 — BMS module extraction (~ 1-2 commits)

**Goal:** Move BMS-specific code into `app/Modules/BMS/`.

Steps:
1. Create `app/Modules/BMS/Providers/BmsServiceProvider.php`
2. Move `DashboardController`, `BuildingController`, `FloorController` → `app/Modules/BMS/Http/Controllers/`
3. Move `Building`, `Floor`, `Room` models → `app/Modules/BMS/Models/`
4. Register `BmsServiceProvider` in `bootstrap/providers.php`
5. Create `app/Modules/BMS/routes.php` — but **also** keep entries in
   `routes/web.php` for backward compat (URLs unchanged).
6. Move BMS views to `resources/views/themes/nexus-bms/{dashboard,buildings,floors}/`
   (already in place from Phase 2 fallback — just shift the canonical home)

Tests: 17/17 pass.

Commit: `Phase 4: Extract BMS module to app/Modules/BMS`

---

## Phase 5 — Energy module extraction (~ 1 commit)

**Goal:** Same as Phase 4 but for Energy.

Steps:
1. `app/Modules/Energy/Providers/EnergyServiceProvider.php`
2. Move `EnergyController` → `app/Modules/Energy/Http/Controllers/`
3. Move `EnergyMeter`, `EnergyLog`, `AdvancedMeterSeeder` → `app/Modules/Energy/`
4. Energy uses Core/Assets for equipment lookups (already does via FK)

Commit: `Phase 5: Extract Energy module`

---

## Phase 6 — Widget Engine (~ 2 commits)

**Goal:** Extract reusable UI components.

### 6.1 — Widget framework

- Create `app/Widgets/{BaseWidget,WidgetRegistry}.php`
- Create `<x-widget>` Blade component
- Register service binding

### 6.2 — First widgets

Implement and replace in BMS views:
- `KpiCardWidget` → replace 5 stat cards in dashboard
- `TrendChartWidget` → replace ApexCharts area chart blocks
- `AlarmTableWidget` → replace alarm table partial
- `DeviceStatusWidget` → replace equipment status grid

Each replacement = visually identical, behaviour identical, but rendered via widget.

Tests: 17/17 pass (Blade output equivalence).

Commits:
- `Phase 6.1: Widget engine foundation`
- `Phase 6.2: Implement KPI/Trend/Alarm/Device widgets and adopt in BMS views`

---

## Phase 7 — Integration Layer (~ 1-2 commits)

**Goal:** Move protocol bridges into `app/Integrations/`.

### 7.1 — REST

- Move `Api/IoTController` → `app/Integrations/Rest/Controllers/`
- Move `VerifyApiToken` → `app/Integrations/Rest/Middleware/`
- Move `SimulateIotCommand` → `app/Integrations/Rest/Console/`

### 7.2 — Modbus + MQTT scaffolding

Create empty classes (just interfaces + stubs):
- `app/Integrations/Modbus/ModbusTcpClient.php` (interface + simple wrapper)
- `app/Integrations/Mqtt/MqttSubscriber.php` (stub command that connects to broker)

The Python gateway scripts in `tools/gateways/` stay where they are — they are
the recommended production-grade implementations. PHP-side classes are for
in-process polling when the edge runs Laravel too.

Commits:
- `Phase 7.1: Move REST integration to app/Integrations/Rest`
- `Phase 7.2: Modbus + MQTT integration stubs`

---

## Phase 8 — Stub modules (~ 1 commit)

**Goal:** Create empty SCADA, WMS, IIoT modules with placeholder routes.

Each gets:
- `ServiceProvider.php`
- `routes.php` with a single `/scada` (etc) URL showing "Coming soon"
- `config.php`
- Menu entry in the sidebar with a "lock" icon

Permissions are seeded so admins can see the menu items but every other role
gets 403. This validates that the platform truly supports multiple modules
before any real code is written for them.

Commit: `Phase 8: Stub SCADA/WMS/IIoT modules`

---

## Phase 9 — Documentation refresh (~ 1 commit)

- Update README.md to reflect platform architecture
- Update `docs/IOT_INTEGRATION.md` with module-aware examples
- Update `docs/DEVICE_ONBOARDING.md` with the new permission keys (`bms.equipment.*`)
- Add `CHANGELOG.md` summarising the refactor

Commit: `Phase 9: Refresh docs for Nexus Platform v2`

---

## Phase 10 — Cleanup (~ 1 commit)

- Remove class aliases left for backward compat
- Delete empty `app/Models/` if everything has moved
- Delete duplicated view files left from Phase 2 copy
- Final pass: `composer dump-autoload`, run all tests

Commit: `Phase 10: Cleanup — drop transitional aliases`

---

## Estimated effort

| Phase | Description                          | Commits | LOC moved | Time |
|-------|--------------------------------------|---------|-----------|------|
| 0     | Docs (this commit)                   | 1       | ~1500 doc | done |
| 1     | Skeleton                             | 1       | ~30       | 15m  |
| 2     | Theme manager                        | 1       | ~200      | 1h   |
| 3.1-3.4 | Core extraction                    | 4       | ~2000     | 3h   |
| 4     | BMS module                           | 1       | ~1500     | 1.5h |
| 5     | Energy module                        | 1       | ~600      | 1h   |
| 6.1-6.2 | Widget engine                      | 2       | ~800      | 2h   |
| 7.1-7.2 | Integration layer                  | 2       | ~400      | 1h   |
| 8     | Stub modules                         | 1       | ~300      | 45m  |
| 9     | Docs refresh                         | 1       | ~200      | 30m  |
| 10    | Cleanup                              | 1       | ~200 del  | 30m  |
| **Total** | **15 commits**                   |         |           | **~12h** |

Each commit is independently shippable and reviewable.

## Rollback strategy

Every phase is a git commit on `master`. To roll back any phase:

```bash
git revert <commit-sha>
php artisan optimize:clear
php artisan test
```

No database rollback is required — schema is never broken by these phases.

## Decision gates

After each major phase (3, 4, 5, 6), pause and confirm:

- [ ] Existing pages render correctly
- [ ] Existing routes return same status codes
- [ ] Feature test suite passes (17/17)
- [ ] CI is green on master
- [ ] No new errors in `storage/logs/laravel.log`
- [ ] Manual smoke: log in as admin, click through all 12 module pages
- [ ] Manual smoke: `php artisan iot:simulate --count=1`

If any of these fails, fix forward in a small follow-up commit before
proceeding to the next phase.

## Open questions to resolve before Phase 1

These need user decision:

1. **Equipment rename to Asset?** If yes, when? Phase 3.4 introduces an Asset
   alias; full rename in Phase 10. Or keep `Equipment` permanently and just
   move it to `Core/Assets/Models/Equipment.php`.

2. **URL prefix per module?** Should BMS pages move from `/buildings` to
   `/bms/buildings`? Pros: clarity, namespacing. Cons: breaking change for
   any external bookmarks. Recommended: yes, but keep legacy `/buildings` →
   redirect to `/bms/buildings` for one major version.

3. **Theme switcher in UI?** Phase 2 makes theme switching technically
   possible. Should we expose a UI for users to pick a theme? Or fix per
   module? Recommended: per-module default with admin override in Settings.

4. **Migration consolidation?** 26 migrations is a lot for a fresh install.
   Should we squash them into a single "v1.0 baseline" migration after the
   refactor? Recommended: yes, in Phase 10 — saves install time and is
   cleaner for new deployments.

5. **Module-local migrations vs single migrations folder?** Laravel supports
   both. Recommendation: keep single `database/migrations/` folder (simpler,
   `migrate` runs in order). Modules' service providers can still load
   additional migrations from their own folder if they want.

Once these are answered, Phase 1 can start.
