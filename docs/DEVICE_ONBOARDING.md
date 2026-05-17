# Device Onboarding Guide

How a field device sends data to Nexus BMS — what to send, what to expect back,
and how to add a new device to the system.

---

## TL;DR

```
Device sends:                              Nexus stores:
─────────────                              ─────────────
POST /api/iot/equipment/{code}/status  →   equipment.status, health_score,
  + status / health / runtime              runtime_hours, last_communication
                                           + auto-alarm if status=offline

POST /api/iot/meter/{name}/reading     →   energy_logs row
  + value / peak_demand / power_factor     (visible on /energy page within seconds)

GET  /api/dashboard/live               →   JSON snapshot for live tiles
```

All endpoints require the `X-API-Token` header. The token is the value of
`IOT_API_TOKEN` in the server's `.env` file.

---

## 1. Equipment Status Push

**Use this when:** a device reports its own health (HVAC FCU, pump VFD, UPS,
Remote I/O input). Send once per minute or on state-change.

### Endpoint

```
POST /api/iot/equipment/{code}/status
```

`{code}` is the value of `equipment.code` in the database (e.g. `AHU-07`).
URL-encode if it contains spaces or special chars.

### Headers

| Header           | Value                              |
|------------------|------------------------------------|
| `X-API-Token`    | `<IOT_API_TOKEN from server .env>` |
| `Content-Type`   | `application/json`                 |
| `Accept`         | `application/json`                 |

### Body (JSON)

```json
{
  "status": "active",       // active | inactive | offline | maintenance
  "health_score": 92,       // 0..100 — how healthy the device is
  "runtime_hours": 1240.5   // optional cumulative running hours
}
```

All fields optional except `status` (if you send any). Send only what you have.

### Response — 200 OK

```json
{
  "success": true,
  "equipment": {
    "id": 1,
    "code": "AHU-07",
    "status": "active",
    "health_score": 92,
    "last_communication": "2026-05-17T04:12:30+00:00"
  }
}
```

### Side effects

| Trigger                                          | Side effect                                |
|--------------------------------------------------|--------------------------------------------|
| Status transitions from anything → `offline`     | **Critical alarm** auto-created + LINE/email notification (if enabled) |
| `health_score` drops below 50%                   | **Warning alarm** auto-created             |
| Always                                           | `equipment.last_communication` set to now  |
| Always                                           | Row inserted into `equipment_status_logs`  |

---

## 2. Energy Meter Reading

**Use this when:** a power meter, water meter, or solar PV inverter reports an
energy value. Send every poll interval (typically 30–60 seconds).

### Endpoint

```
POST /api/iot/meter/{name}/reading
```

`{name}` is the URL-encoded value of `energy_meters.name`
(e.g. `Nexus%20Tower%20A%20Electricity`).

### Body (JSON)

```json
{
  "value": 125.5,            // required — kWh for electricity/solar, m³ for water
  "peak_demand": 85.2,       // optional — instantaneous kW at the time of reading
  "power_factor": 0.96,      // optional — 0..1
  "cost": 565.00,            // optional — pre-computed cost (otherwise Nexus uses electricity_rate × value)
  "logged_at": "2026-05-17T04:12:30+00:00"  // optional — defaults to now()
}
```

### Response — 200 OK

```json
{
  "success": true,
  "log_id": 8939,
  "meter": "Nexus Tower A Electricity",
  "value": 125.5,
  "logged_at": "2026-05-17T04:12:30+00:00"
}
```

The reading appears on the `/energy` page in the meter table within one page
refresh.

---

## 3. Live Dashboard Pull

For dashboards that want to mirror Nexus's KPIs without polling the DB
directly.

```
GET /api/dashboard/live
```

Returns:

```json
{
  "timestamp": "2026-05-17T04:13:00+00:00",
  "active_alarms": 6,
  "critical_alarms": 3,
  "total_equipment": 18,
  "active_equipment": 15,
  "offline_equipment": 1,
  "today_energy": 28089.45,
  "system_health": 94,
  "recent_alarms": [ ... ]
}
```

---

## Adding a New Device — Step by Step

### Step 1 — Create the equipment in Nexus

Sign in as **admin** or **manager**. Go to **Equipment → Add Equipment**.

Fill in at minimum:

| Field          | Example                                  |
|----------------|------------------------------------------|
| Code           | `AHU-NTA-7F-01` *(this is what the gateway sends in the URL — must be unique)* |
| Name           | `Air Handler — Tower A, 7th Floor`       |
| Building       | Nexus Tower A                            |
| Floor          | 7                                        |
| Category       | HVAC                                     |
| Status         | active                                   |

Click **Save**. The equipment now has an internal `id` but the gateway will
reference it by **Code**.

### Step 2 (optional) — Create a sub-meter for this device

If you want **per-equipment energy tracking** (so it shows up in *Top Consumers
by Category*), add an energy meter linked to this equipment. For now this is
done via SQL or seeder:

```sql
INSERT INTO energy_meters (code, name, building_id, floor_id, equipment_id, type, unit, status, created_at, updated_at)
VALUES ('M-AHU-NTA-7F-01', 'AHU-NTA-7F-01 sub-meter',
        (SELECT id FROM buildings WHERE name='Nexus Tower A'),
        (SELECT id FROM floors WHERE building_id=(SELECT id FROM buildings WHERE name='Nexus Tower A') AND floor_number=7),
        (SELECT id FROM equipment WHERE code='AHU-NTA-7F-01'),
        'electricity', 'kWh', 'active', NOW(), NOW());
```

(A UI for this can be added in `/settings` later.)

### Step 3 — Get the API token

The token comes from the server's `.env` file:

```bash
grep IOT_API_TOKEN /path/to/nexus-bms/.env
# IOT_API_TOKEN=nexus-iot-secret-2026
```

Give this to the integrator / device firmware author.

### Step 4 — Wire the device

Pick the bridge that matches the protocol:

| Device protocol      | Use this bridge                                   |
|----------------------|---------------------------------------------------|
| Modbus TCP / RTU     | `tools/gateways/modbus_to_nexus.py`               |
| MQTT broker          | `tools/gateways/mqtt_to_nexus.py`                 |
| ESP32 firmware       | `tools/gateways/esp32_push.ino`                   |
| Node-RED             | `tools/gateways/node-red-flow.json`               |
| Custom               | Any HTTP client — just POST per the spec above    |

For Modbus, edit `modbus_config.yaml`:

```yaml
- name: "AHU-NTA-7F-01"        # equipment.code
  kind: equipment
  slave_id: 5
  connection: { type: tcp, host: 192.168.1.120, port: 502 }
  registers:
    status_code:   { address: 1, count: 1, dtype: uint16 }
    health_score: { address: 2, count: 1, dtype: uint16 }
    runtime_hours: { address: 10, count: 2, dtype: float32 }
```

### Step 5 — Smoke test

From any host that can reach Nexus:

```bash
curl -X POST https://bms.example.com/api/iot/equipment/AHU-NTA-7F-01/status \
     -H "X-API-Token: nexus-iot-secret-2026" \
     -H "Content-Type: application/json" \
     -d '{"status":"active","health_score":95}'
```

Expect HTTP 200 and a JSON response showing the equipment's new state.

### Step 6 — Verify in the UI

1. Open **Equipment** → search for `AHU-NTA-7F-01` → status should be `active`
   and `last_communication` updated within seconds.
2. If you sent `status=offline`, check **Alarms** — a new **critical** alarm
   should appear.
3. If you wired a meter, open **Energy** — the meter row should show today's
   accumulated value.

---

## Quick test from the included simulator

If you don't have a real device yet, the platform ships with a simulator:

```bash
# Push one realistic round of readings to all seeded equipment+meters
php artisan iot:simulate --count=1

# Push offline event to trigger a critical alarm
php artisan iot:simulate --offline=AHU-NTA-7F-01

# Run continuously every 15s (Ctrl+C to stop)
php artisan iot:simulate --interval=15
```

See `docs/IOT_INTEGRATION.md` for the broader architecture (edge gateways,
recommended hardware, security model).

---

## Common errors

| Status | Body                                  | Meaning                                           |
|--------|---------------------------------------|---------------------------------------------------|
| 401    | `{"error":"Unauthorized"}`            | Wrong / missing `X-API-Token` header             |
| 404    | `{"error":"Equipment not found"}`     | The code in the URL is not in the equipment table — create it in the UI first |
| 404    | `{"error":"Meter not found"}`         | Meter name mismatch (case-sensitive)              |
| 422    | `{"message":"...","errors":{...}}`    | Validation error — body missing required field    |

---

## Rates &amp; cost calculation

Cost values shown on the Energy page are computed using rates stored in
`system_settings`. Admins can edit them at **Settings → Energy Rates**:

| Key                  | Default | Used for                                  |
|----------------------|---------|-------------------------------------------|
| `electricity_rate`   | 4.50    | THB per kWh consumed from grid            |
| `water_rate`         | 25.00   | THB per m³                                |
| `solar_feedin_rate`  | 2.20    | THB per kWh of solar exported back to grid |
| `currency`           | THB     | Currency code shown on labels             |
| `currency_symbol`    | ฿       | Symbol prefix                             |

Only users with the `settings.edit` permission can change them; everyone else
sees the values read-only.
