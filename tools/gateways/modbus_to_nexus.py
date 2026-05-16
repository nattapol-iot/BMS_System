#!/usr/bin/env python3
"""
modbus_to_nexus.py
==================

Polls one or more Modbus devices (power meters, remote I/O, etc.) and forwards
the readings to the Nexus BMS Platform REST API.

Usage:
    NEXUS_URL=https://bms.example.com \
    NEXUS_TOKEN=nexus-iot-secret-2026 \
    python3 modbus_to_nexus.py modbus_config.yaml

Dependencies:
    pip install pymodbus>=3.6 pyyaml requests

Tested with:
    - Schneider PM5560
    - Carlo Gavazzi EM24
    - Eastron SDM630
    - Advantech ADAM-6217 / 6224

Author: Nexus BMS reference gateway. License: MIT.
"""

import os
import sys
import time
import json
import logging
import struct
import signal
from pathlib import Path

import yaml
import requests

try:
    from pymodbus.client import ModbusTcpClient, ModbusSerialClient
except ImportError:
    print("ERROR: pip install pymodbus>=3.6 pyyaml requests", file=sys.stderr)
    sys.exit(1)


# ---------- Config & logging ----------

LOG = logging.getLogger("modbus_to_nexus")
logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)

NEXUS_URL = os.environ.get("NEXUS_URL", "http://localhost:8000").rstrip("/")
NEXUS_TOKEN = os.environ.get("NEXUS_TOKEN", "")
if not NEXUS_TOKEN:
    LOG.error("NEXUS_TOKEN env var is required")
    sys.exit(2)

OFFLINE_BUFFER = []          # readings that failed to upload — retry later
MAX_BUFFER = 1000


# ---------- Helpers ----------

def decode_registers(regs, dtype, byteorder="big", wordorder="big"):
    """Decode a list of 16-bit Modbus registers into a Python value."""
    raw = b""
    if wordorder == "big":
        for r in regs:
            raw += struct.pack(">H", r)
    else:
        for r in reversed(regs):
            raw += struct.pack(">H", r)

    fmt_byte = ">" if byteorder == "big" else "<"

    if dtype == "float32":
        return struct.unpack(fmt_byte + "f", raw[:4])[0]
    if dtype == "float64":
        return struct.unpack(fmt_byte + "d", raw[:8])[0]
    if dtype == "int32":
        return struct.unpack(fmt_byte + "i", raw[:4])[0]
    if dtype == "uint32":
        return struct.unpack(fmt_byte + "I", raw[:4])[0]
    if dtype == "int16":
        return struct.unpack(fmt_byte + "h", raw[:2])[0]
    if dtype == "uint16":
        return struct.unpack(fmt_byte + "H", raw[:2])[0]
    raise ValueError(f"Unsupported dtype: {dtype}")


def make_client(conn_cfg):
    """Build a pymodbus client from connection config."""
    ctype = conn_cfg.get("type", "tcp").lower()
    if ctype == "tcp":
        return ModbusTcpClient(
            host=conn_cfg["host"],
            port=int(conn_cfg.get("port", 502)),
            timeout=float(conn_cfg.get("timeout", 3)),
        )
    if ctype == "rtu":
        return ModbusSerialClient(
            port=conn_cfg["port"],          # e.g. /dev/ttyUSB0 or COM3
            baudrate=int(conn_cfg.get("baudrate", 9600)),
            bytesize=int(conn_cfg.get("bytesize", 8)),
            parity=conn_cfg.get("parity", "N").upper()[0],
            stopbits=int(conn_cfg.get("stopbits", 1)),
            timeout=float(conn_cfg.get("timeout", 1)),
        )
    raise ValueError(f"Unknown connection type: {ctype}")


def read_register_block(client, slave_id, reg_cfg):
    """Read one register and return decoded value (or None on failure)."""
    addr = int(reg_cfg["address"])
    count = int(reg_cfg.get("count", 2))
    func = reg_cfg.get("function", "holding")  # holding | input

    if func == "input":
        rr = client.read_input_registers(address=addr, count=count, slave=slave_id)
    else:
        rr = client.read_holding_registers(address=addr, count=count, slave=slave_id)

    if rr.isError():
        LOG.warning("Modbus error @ slave=%d addr=%d: %s", slave_id, addr, rr)
        return None

    value = decode_registers(
        rr.registers,
        reg_cfg.get("dtype", "float32"),
        reg_cfg.get("byteorder", "big"),
        reg_cfg.get("wordorder", "big"),
    )
    scale = float(reg_cfg.get("scale", 1.0))
    return value * scale


# ---------- HTTP push ----------

SESSION = requests.Session()
SESSION.headers.update({
    "X-API-Token": NEXUS_TOKEN,
    "Content-Type": "application/json",
    "Accept": "application/json",
    "User-Agent": "nexus-modbus-gateway/1.0",
})


def push(endpoint, payload):
    """POST payload to Nexus. Returns True on success, False otherwise."""
    url = f"{NEXUS_URL}{endpoint}"
    try:
        r = SESSION.post(url, data=json.dumps(payload), timeout=10)
        if r.ok:
            LOG.info("→ %s  %s  (%d)", endpoint, payload, r.status_code)
            return True
        LOG.warning("← %s  %d  %s", endpoint, r.status_code, r.text[:200])
        return False
    except requests.RequestException as e:
        LOG.warning("HTTP error to %s: %s", url, e)
        return False


def flush_buffer():
    """Retry uploads we previously failed to send."""
    if not OFFLINE_BUFFER:
        return
    LOG.info("Flushing %d buffered readings...", len(OFFLINE_BUFFER))
    remaining = []
    for item in OFFLINE_BUFFER:
        if not push(item["endpoint"], item["payload"]):
            remaining.append(item)
    OFFLINE_BUFFER.clear()
    OFFLINE_BUFFER.extend(remaining)


def buffer_or_push(endpoint, payload):
    if push(endpoint, payload):
        return
    if len(OFFLINE_BUFFER) >= MAX_BUFFER:
        OFFLINE_BUFFER.pop(0)         # drop oldest
    OFFLINE_BUFFER.append({"endpoint": endpoint, "payload": payload})


# ---------- Polling loop ----------

def poll_device(client, dev):
    """Poll one device per its config, then push results to Nexus."""
    slave_id = int(dev.get("slave_id", 1))
    name = dev["name"]
    LOG.debug("Polling %s (slave=%d)", name, slave_id)

    readings = {}
    for reg_name, reg_cfg in dev.get("registers", {}).items():
        v = read_register_block(client, slave_id, reg_cfg)
        if v is not None:
            readings[reg_name] = round(float(v), 4)

    if not readings:
        LOG.warning("No readings from %s", name)
        return

    # Route per device kind
    kind = dev.get("kind", "meter").lower()
    if kind == "meter":
        # Map common register names to Nexus meter reading body
        body = {
            "value": readings.get("energy_kwh") or readings.get("value") or 0,
        }
        for k in ("peak_demand", "power_factor", "cost"):
            if k in readings:
                body[k] = readings[k]
        buffer_or_push(f"/api/iot/meter/{requests.utils.quote(name, safe='')}/reading", body)
    elif kind == "equipment":
        body = {}
        if "status_code" in readings:
            # Map numeric status: 0=offline, 1=active, 2=maintenance, 3=inactive
            status_map = {0: "offline", 1: "active", 2: "maintenance", 3: "inactive"}
            body["status"] = status_map.get(int(readings["status_code"]), "active")
        if "health_score" in readings:
            body["health_score"] = int(readings["health_score"])
        if "runtime_hours" in readings:
            body["runtime_hours"] = float(readings["runtime_hours"])
        buffer_or_push(f"/api/iot/equipment/{name}/status", body)
    else:
        LOG.warning("Unknown device kind: %s", kind)


def run(config_path):
    with open(config_path) as f:
        cfg = yaml.safe_load(f)

    poll_interval = int(cfg.get("poll_interval_sec", 60))
    devices = cfg.get("devices", [])
    if not devices:
        LOG.error("No devices in config")
        sys.exit(2)

    LOG.info("Loaded %d device(s). Polling every %ds. Target: %s",
             len(devices), poll_interval, NEXUS_URL)

    # Build one client per connection profile (so we don't open & close every poll)
    clients = {}
    for dev in devices:
        key = json.dumps(dev["connection"], sort_keys=True)
        if key not in clients:
            c = make_client(dev["connection"])
            c.connect()
            clients[key] = c
        dev["_client"] = clients[key]

    # Graceful shutdown
    stop = {"flag": False}
    def _sig(*_):
        stop["flag"] = True
        LOG.info("Stopping…")
    signal.signal(signal.SIGINT, _sig)
    signal.signal(signal.SIGTERM, _sig)

    while not stop["flag"]:
        flush_buffer()
        for dev in devices:
            try:
                poll_device(dev["_client"], dev)
            except Exception as e:
                LOG.exception("Error polling %s: %s", dev.get("name"), e)
        # interruptible sleep
        for _ in range(poll_interval):
            if stop["flag"]:
                break
            time.sleep(1)

    for c in clients.values():
        c.close()
    LOG.info("Bye.")


if __name__ == "__main__":
    cfg_path = sys.argv[1] if len(sys.argv) > 1 else "modbus_config.yaml"
    if not Path(cfg_path).exists():
        LOG.error("Config file not found: %s", cfg_path)
        sys.exit(2)
    run(cfg_path)
