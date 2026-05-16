# Edge Gateway Examples

Reference scripts that bridge field-device protocols to the Nexus BMS REST API.

| File                              | Purpose                                              |
|-----------------------------------|------------------------------------------------------|
| `modbus_to_nexus.py`              | Poll Modbus TCP/RTU devices and POST readings        |
| `modbus_config.example.yaml`      | Example device/register mapping (Schneider, Eastron, Advantech) |
| `mqtt_to_nexus.py`                | Subscribe to MQTT topics and forward as REST calls   |
| `esp32_push.ino`                  | Arduino sketch: read sensor on ESP32 → POST direct   |
| `node-red-flow.json`              | Node-RED flow: Modbus poll → HTTP request            |

See `docs/IOT_INTEGRATION.md` at the repo root for the full guide, including
architecture, protocol selection, hardware recommendations, and security notes.

## Quick start (Modbus)

```bash
pip install pymodbus>=3.6 pyyaml requests

cp modbus_config.example.yaml modbus_config.yaml
# edit modbus_config.yaml: IP, slave_id, registers

NEXUS_URL=https://bms.example.com \
NEXUS_TOKEN=nexus-iot-secret-2026 \
python3 modbus_to_nexus.py modbus_config.yaml
```

## Quick start (MQTT)

```bash
pip install paho-mqtt requests

NEXUS_URL=https://bms.example.com \
NEXUS_TOKEN=nexus-iot-secret-2026 \
MQTT_HOST=192.168.1.50 \
python3 mqtt_to_nexus.py
```

Then publish from a device:

```bash
mosquitto_pub -h 192.168.1.50 -t 'nexus/meter/Tower-A-MDB/reading' \
              -m '{"value":125.5,"peak_demand":85.2}'
```

## Run as a systemd service (Linux)

```ini
# /etc/systemd/system/nexus-gateway.service
[Unit]
Description=Nexus BMS Modbus Gateway
After=network.target

[Service]
Type=simple
User=pi
WorkingDirectory=/home/pi/gateway
Environment=NEXUS_URL=https://bms.example.com
Environment=NEXUS_TOKEN=nexus-iot-secret-2026
ExecStart=/usr/bin/python3 /home/pi/gateway/modbus_to_nexus.py modbus_config.yaml
Restart=on-failure
RestartSec=5

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now nexus-gateway
sudo journalctl -u nexus-gateway -f
```
