# app/Integrations

Protocol bridges — translate field-bus protocols into Nexus internal events.

| Folder    | Protocol                | Status                                            |
|-----------|-------------------------|---------------------------------------------------|
| `Rest/`   | HTTP/REST + token auth  | Existing (current `Api/IoTController`)            |
| `Modbus/` | Modbus TCP & RTU        | Stub (Phase 7.2) — Python gateway in `tools/`     |
| `Mqtt/`   | MQTT pub/sub            | Stub (Phase 7.2) — Python bridge in `tools/`      |
| `OpcUa/`  | OPC UA                  | Stub — recommend external gateway (BAC0/Python)   |
| `Bacnet/` | BACnet/IP               | Stub — recommend external gateway                 |

## Rule

**Controllers and module business logic MUST NOT import protocol libraries
directly.** They go through this layer.

## Edge gateways stay outside

The recommended production setup runs protocol pollers as **separate Python
processes** outside Laravel. See `tools/gateways/` for the reference
implementations:

- `tools/gateways/modbus_to_nexus.py` — Schneider PM5560, Eastron SDM630, Advantech ADAM
- `tools/gateways/mqtt_to_nexus.py`   — Tasmota, LoRaWAN bridges
- `tools/gateways/esp32_push.ino`     — direct ESP32 HTTP push
- `tools/gateways/node-red-flow.json` — no-code Modbus → REST

The classes in this directory are for **in-process polling** when the edge
node also runs Laravel — e.g. small deployments or Modbus simulators.

**Currently contains routes/api.php integration wiring only** — class
extraction happens in Phase 7 of the refactor.

See `docs/IOT_INTEGRATION.md` and `docs/DEVICE_ONBOARDING.md` for the data
contract.
