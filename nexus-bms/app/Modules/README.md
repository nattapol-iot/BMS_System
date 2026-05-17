# app/Modules

Each module is a self-contained industrial product built on top of the Nexus
Platform Core.

| Module     | Status                              | Notes                                     |
|------------|-------------------------------------|-------------------------------------------|
| `BMS/`     | Existing product — to be extracted  | Buildings, Floors, Equipment, Alarms      |
| `Energy/`  | Existing product — to be extracted  | Meters, Logs, Solar PV, Cost calculation  |
| `SCADA/`   | Stub (Phase 8) — coming soon        | Process mimic, Tag database, HMI          |
| `WMS/`     | Stub (Phase 8) — coming soon        | Inventory, Picking, Receiving             |
| `IIoT/`    | Stub (Phase 8) — coming soon        | Edge device fleet, MQTT broker mgmt       |

## Adding a new module

See `docs/MODULE_STRUCTURE.md` → "How a new module is added".

Each module:

1. Lives in its own namespace `App\Modules\<Name>`
2. Has its own `ServiceProvider` registered in `bootstrap/providers.php`
3. Owns its routes, views, models, migrations, seeders
4. Depends only on `App\Core` and `App\Integrations` — **never on other modules directly**
5. Seeds its own permissions (`<module>.<resource>.<action>`)
6. Picks a default theme from `resources/views/themes/`

**Currently empty** — content arrives during Phases 4–5 (BMS, Energy) and
Phase 8 (SCADA, WMS, IIoT stubs).
