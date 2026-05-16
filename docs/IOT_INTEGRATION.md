# IoT Integration Guide

How to connect real-world field devices (Power Meters, Remote I/O modules,
HVAC controllers, sensors) to the Nexus BMS Platform.

---

## Architecture

```
┌───────────────────┐     ┌─────────────────────┐     ┌──────────────────────┐
│  Field Devices    │     │   Edge Gateway      │     │   Nexus BMS Platform │
│                   │     │                     │     │                      │
│  • Power meter    │◄────│  • Polls/subscribes │────►│  REST API            │
│    (Modbus RTU)   │ RS- │  • Translates       │HTTP │  POST /api/iot/...   │
│  • Remote I/O     │ 485 │    protocol         │ /   │                      │
│    (Modbus TCP)   │◄────│  • Buffers offline  │MQTT │  X-API-Token auth    │
│  • HVAC FCU       │ LAN │  • Pushes to REST   │ →   │                      │
│    (BACnet/IP)    │     │                     │REST │  Auto-creates alarms │
│  • Sensors        │MQTT │                     │     │  on critical events  │
│    (MQTT)         │     │                     │     │                      │
└───────────────────┘     └─────────────────────┘     └──────────────────────┘
        ↑                          ↑                            ↑
   field layer                edge layer                  cloud / on-prem
   (Modbus, BACnet,         (Python script,              (Laravel app on
    MQTT, 4-20mA)            Node-RED, gateway HW)        server / VPS / NAS)
```

The platform **does not** speak Modbus/BACnet directly. Instead, a small
**edge gateway** translates field protocols into HTTP POSTs to the Nexus REST
API. This decouples the platform from the physical layer and means devices
behind firewalls, cellular gateways, or NAT all work the same way.

---

## REST API Recap

All field data flows through these three endpoints (auth: `X-API-Token` header).

### 1. Push equipment status / health

```
POST /api/iot/equipment/{code}/status
Content-Type: application/json
X-API-Token: <token>

{ "status": "active", "health_score": 92, "runtime_hours": 12340.5 }
```

Auto-creates a **critical alarm** if status transitions to `offline` (or
warning alarm if `health_score < 50`).

### 2. Push energy meter reading

```
POST /api/iot/meter/{name}/reading
Content-Type: application/json
X-API-Token: <token>

{ "value": 125.5, "peak_demand": 85.2, "power_factor": 0.96, "cost": 565.0 }
```

`{name}` is URL-encoded meter name as stored in `energy_meters.name`.

### 3. Pull live dashboard snapshot

```
GET /api/dashboard/live
X-API-Token: <token>
```

Returns JSON: active_alarms, critical_alarms, total_equipment, today_energy,
system_health, recent_alarms[].

---

## Protocol Bridges

Choose the bridge that matches your field device's protocol.

### A. Modbus (RTU over RS-485, or TCP over LAN)

Most **power meters** (Schneider PM5xxx, Carlo Gavazzi EM24, Eastron SDM630,
Acrel ADL) and **remote I/O** (Advantech ADAM, Moxa ioLogik, WAGO 750) speak
Modbus.

Use `tools/gateways/modbus_to_nexus.py` — a working Python polling script that:

- Connects to a Modbus TCP slave or USB-to-RS485 dongle
- Reads holding registers per a YAML config
- POSTs readings to Nexus every N seconds
- Buffers locally when network is down

Run it on a Raspberry Pi, industrial PC, or any always-on host on the LAN.

### B. MQTT

Modern wireless sensors (LoRaWAN bridges, ESP32 firmware, Tasmota devices)
typically publish to an MQTT broker.

Use `tools/gateways/mqtt_to_nexus.py` — subscribes to topics and forwards each
message to the appropriate Nexus endpoint.

### C. BACnet/IP

For HVAC equipment (VAV/AHU controllers from Siemens, Honeywell, Distech,
ABB), use an open-source BACnet stack such as
[BAC0 (Python)](https://github.com/ChristianTremblay/BAC0). Pattern is the
same: poll the BACnet object → POST to Nexus.

### D. Direct HTTP from device firmware

For custom ESP32 / Arduino / STM32 firmware, push readings directly using the
REST API. See `tools/gateways/esp32_push.ino`.

### E. Node-RED

For no-code integration (drag-and-drop flows), use the Node-RED flow at
`tools/gateways/node-red-flow.json`. Imports as: **Menu → Import → paste JSON**.

---

## Simulating Devices (no hardware needed)

For testing without real devices, use the built-in artisan simulator. It pushes
realistic readings to the local API the same way a real gateway would.

```bash
# Push 1 cycle of readings to every active equipment + meter, then exit
php artisan iot:simulate --count=1

# Continuous: every 10s, forever (Ctrl+C to stop)
php artisan iot:simulate --interval=10

# Only specific devices
php artisan iot:simulate --equipment=AHU-07 --equipment=CHILLER-01 --meters="Nexus Tower A Electricity"

# Trigger a critical alarm (one-shot)
php artisan iot:simulate --offline=AHU-07

# Dry-run: print what would be sent, don't POST
php artisan iot:simulate --dry-run --count=1
```

The simulator uses a realistic 24-hour load curve (peak ~14:00, trough ~03:00)
and a probabilistic equipment status mix (95% active, 3.5% maintenance,
1% inactive, 0.5% offline) — so leaving it running overnight produces
believable trend data and the occasional alarm. To override the target URL or
token, pass `--url=` / `--token=` or set `APP_URL` / `IOT_API_TOKEN`.

## Provisioning Devices in Nexus

Before a gateway can push data, the equipment/meter must exist in the database
so Nexus knows what `{code}` and `{name}` refer to.

1. Sign in as admin → **Equipment → Add Equipment**
   - Set a unique **Code** (e.g., `PM-NTA-MDB-01`) — this is what the gateway
     uses in the URL.
   - Link to building / floor.
2. For meters: **Settings → Energy Meters** (or seed via migration)
   - Set a unique **Name** (e.g., `Nexus Tower A MDB Electricity`).

You can also bulk-import via SQL or a custom artisan command if you have a
device list.

---

## Recommended Edge Hardware

| Use case                      | Hardware                                  | OS / Runtime     |
|-------------------------------|-------------------------------------------|------------------|
| 1–10 Modbus devices, RS-485   | Raspberry Pi 4 + USB-to-RS485 dongle      | Raspberry Pi OS, Python |
| Industrial / DIN-rail mount   | Advantech UNO-2271, Moxa UC-2100, eWON    | Linux / built-in |
| BACnet aggregation             | NUC / mini-PC                             | Ubuntu Server + BAC0 |
| MQTT broker on-site            | Same edge HW + Mosquitto / EMQX           | Docker recommended |
| Cellular / remote sites        | Teltonika RUT240 + Modbus gateway         | OpenWrt / Linux  |

---

## Security Notes

1. **Token rotation** — change `IOT_API_TOKEN` in `.env` periodically. Each
   gateway needs the new value.
2. **HTTPS** — in production, terminate TLS at the reverse proxy (Caddy /
   Nginx). Gateways should POST to `https://`, never plain `http://`.
3. **Per-gateway tokens** — for stricter isolation, replace the single static
   `IOT_API_TOKEN` with a `gateway_tokens` table keyed by gateway ID and update
   `VerifyApiToken` middleware to look up the DB. Out of scope for this guide.
4. **Network isolation** — gateways should live on a separate VLAN; only
   outbound HTTPS to the Nexus URL is required.
5. **Rate limiting** — add Laravel `throttle:60,1` middleware to API routes if
   gateways might burst.

---

## Troubleshooting

| Symptom                            | Likely cause / fix                                          |
|------------------------------------|-------------------------------------------------------------|
| 401 Unauthorized                   | Wrong/missing `X-API-Token` header                          |
| 404 on `/equipment/{code}/status`  | Equipment code does not exist — add it via UI first         |
| 404 on `/meter/{name}/reading`     | Meter name mismatch (case-sensitive, watch URL-encoding)    |
| 422 Validation error               | Body missing required fields (e.g., `value`)                |
| Modbus timeout                     | Wrong baud rate / slave ID / wiring polarity                |
| Readings off by 10× or 100×        | Forgot the meter's register scale factor                    |
| Gateway can't reach Nexus          | Firewall blocking outbound HTTPS, or DNS issue              |

---

## Quick Start: Schneider PM5560 → Nexus in 15 minutes

1. **Provision in Nexus:**
   - Add meter named `MDB-Main-Tower-A` in Energy Meters list.

2. **Connect physically:**
   - USB-to-RS485 dongle → meter terminals 50 (A+) / 51 (B−).
   - Set meter address = 1, baud = 19200, parity = even (default).

3. **Install gateway:**

   ```bash
   pip install pymodbus pyyaml requests
   cp tools/gateways/modbus_to_nexus.py /home/pi/
   cp tools/gateways/modbus_config.example.yaml /home/pi/modbus_config.yaml
   # edit modbus_config.yaml: point to your meter
   ```

4. **Run:**

   ```bash
   NEXUS_URL=https://bms.yourcompany.com \
   NEXUS_TOKEN=nexus-iot-secret-2026 \
   python3 modbus_to_nexus.py modbus_config.yaml
   ```

5. **Verify:** Open Energy page in Nexus → meter should show fresh reading
   within the poll interval (default 60s).

---

See the scripts in `tools/gateways/` for working examples you can copy and
adapt to your devices.
