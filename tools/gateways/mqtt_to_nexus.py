#!/usr/bin/env python3
"""
mqtt_to_nexus.py
================

Subscribes to MQTT topics and forwards JSON messages to the Nexus BMS Platform
REST API. Useful for LoRaWAN bridges, ESP32/Tasmota sensors, and anything that
already publishes to a broker.

Topic conventions (default):
    nexus/meter/{name}/reading       → POST /api/iot/meter/{name}/reading
    nexus/equipment/{code}/status    → POST /api/iot/equipment/{code}/status

The message payload is forwarded verbatim as JSON. Example:

    Topic:   nexus/meter/Tower-A-MDB/reading
    Payload: {"value": 125.5, "peak_demand": 85.2}

Usage:
    NEXUS_URL=https://bms.example.com \
    NEXUS_TOKEN=nexus-iot-secret-2026 \
    MQTT_HOST=192.168.1.50 MQTT_PORT=1883 \
    MQTT_USER=nexus MQTT_PASS=secret \
    python3 mqtt_to_nexus.py

Dependencies:
    pip install paho-mqtt requests
"""

import os
import sys
import json
import logging
import signal
import time

try:
    import paho.mqtt.client as mqtt
except ImportError:
    print("pip install paho-mqtt requests", file=sys.stderr)
    sys.exit(1)

import requests

LOG = logging.getLogger("mqtt_to_nexus")
logging.basicConfig(level=logging.INFO,
                    format="%(asctime)s [%(levelname)s] %(message)s")

NEXUS_URL   = os.environ.get("NEXUS_URL", "http://localhost:8000").rstrip("/")
NEXUS_TOKEN = os.environ["NEXUS_TOKEN"]    # required
MQTT_HOST   = os.environ.get("MQTT_HOST", "localhost")
MQTT_PORT   = int(os.environ.get("MQTT_PORT", 1883))
MQTT_USER   = os.environ.get("MQTT_USER")
MQTT_PASS   = os.environ.get("MQTT_PASS")
TOPIC_BASE  = os.environ.get("TOPIC_BASE", "nexus")


HTTP = requests.Session()
HTTP.headers.update({
    "X-API-Token": NEXUS_TOKEN,
    "Content-Type": "application/json",
    "Accept": "application/json",
})


def forward(endpoint, payload):
    url = f"{NEXUS_URL}{endpoint}"
    try:
        r = HTTP.post(url, data=json.dumps(payload), timeout=10)
        if r.ok:
            LOG.info("→ %s  %s", endpoint, payload)
        else:
            LOG.warning("← %d %s", r.status_code, r.text[:200])
    except requests.RequestException as e:
        LOG.warning("HTTP error: %s", e)


def on_message(client, userdata, msg):
    try:
        payload = json.loads(msg.payload.decode("utf-8"))
    except Exception:
        LOG.warning("Bad JSON on %s", msg.topic)
        return

    parts = msg.topic.split("/")
    # nexus/meter/{name}/reading  or  nexus/equipment/{code}/status
    if len(parts) >= 4 and parts[0] == TOPIC_BASE:
        kind = parts[1]
        identifier = parts[2]
        action = parts[3]
        if kind == "meter" and action == "reading":
            forward(f"/api/iot/meter/{requests.utils.quote(identifier, safe='')}/reading", payload)
        elif kind == "equipment" and action == "status":
            forward(f"/api/iot/equipment/{identifier}/status", payload)
        else:
            LOG.debug("Ignored topic shape: %s", msg.topic)
    else:
        LOG.debug("Ignored topic: %s", msg.topic)


def on_connect(client, userdata, flags, rc, properties=None):
    if rc == 0:
        LOG.info("MQTT connected to %s:%d", MQTT_HOST, MQTT_PORT)
        client.subscribe(f"{TOPIC_BASE}/+/+/+", qos=1)
        LOG.info("Subscribed: %s/+/+/+", TOPIC_BASE)
    else:
        LOG.error("MQTT connect failed: rc=%s", rc)


def main():
    client = mqtt.Client(client_id=f"nexus-bridge-{os.getpid()}",
                         callback_api_version=mqtt.CallbackAPIVersion.VERSION2)
    if MQTT_USER:
        client.username_pw_set(MQTT_USER, MQTT_PASS or "")
    client.on_connect = on_connect
    client.on_message = on_message

    def _bye(*_):
        LOG.info("Stopping…")
        client.disconnect()
    signal.signal(signal.SIGINT, _bye)
    signal.signal(signal.SIGTERM, _bye)

    while True:
        try:
            client.connect(MQTT_HOST, MQTT_PORT, keepalive=30)
            client.loop_forever(retry_first_connection=True)
            break
        except Exception as e:
            LOG.warning("MQTT loop error: %s, retrying in 5s", e)
            time.sleep(5)


if __name__ == "__main__":
    main()
