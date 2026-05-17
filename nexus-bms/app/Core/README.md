# app/Core

Shared infrastructure used by **every** module (BMS, Energy, SCADA, WMS, IIoT).

Sub-namespaces:

| Folder           | Responsibility                                              |
|------------------|-------------------------------------------------------------|
| `Auth/`          | Login, logout, session, password — generic user auth        |
| `Permissions/`   | Role + Permission models, `CheckPermission` middleware      |
| `Users/`         | User CRUD, profile, locale                                  |
| `AuditLog/`      | Activity logging, `LogActivity` middleware, log viewer      |
| `Notifications/` | `NotificationService` (Email + LINE Notify + future channels) |
| `Settings/`      | System settings KV store + admin UI                         |
| `Reports/`       | Report generation framework (CSV/PDF/HTML)                  |
| `Backup/`        | DB dump (mysqldump) + file backup                           |
| `Alarms/`        | Generic alarm engine with `module` discriminator            |
| `Schedules/`     | Time-based job runner used by any module                    |
| `Assets/`        | Generic "thing being monitored" (Equipment in BMS, Tag in SCADA, Item in WMS) |
| `Theme/`         | Theme registration + view path resolution                   |

**Currently empty** — content arrives during Phase 3 of the refactor.

## Rules

- Core code **MUST NOT** import from `app/Modules/`
- Core code **MUST NOT** depend on a specific industry vertical
- Adding to Core means: it is useful to at least 2 modules

See `docs/MODULE_STRUCTURE.md` for details.
