/*
 * esp32_push.ino
 * ==============
 *
 * Reference firmware for ESP32 boards to push readings directly into the
 * Nexus BMS Platform REST API over WiFi.
 *
 * Typical use:
 *   - Read a sensor (DHT22, BME280, PZEM-004T, etc.) connected via I2C / UART / GPIO
 *   - Send the value to /api/iot/meter/{name}/reading every 60 seconds
 *
 * Dependencies (Arduino IDE → Library Manager):
 *   - ArduinoJson (v6 or v7)
 *
 * Board: ESP32 Dev Module (any).
 * License: MIT.
 */

#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// ---------- CONFIG — edit these ----------
const char* WIFI_SSID     = "your-wifi-ssid";
const char* WIFI_PASSWORD = "your-wifi-password";

const char* NEXUS_HOST    = "https://bms.example.com";    // no trailing slash
const char* NEXUS_TOKEN   = "nexus-iot-secret-2026";
const char* METER_NAME    = "Edge Sensor Tower B 5F";    // URL-encoded by code below

const unsigned long PUSH_INTERVAL_MS = 60UL * 1000UL;     // every 60s
// ------------------------------------------

unsigned long lastPushMs = 0;

void connectWiFi() {
  Serial.printf("WiFi: connecting to %s\n", WIFI_SSID);
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  int tries = 0;
  while (WiFi.status() != WL_CONNECTED && tries < 40) {
    delay(500);
    Serial.print(".");
    tries++;
  }
  Serial.println();
  if (WiFi.status() == WL_CONNECTED) {
    Serial.printf("WiFi: connected, IP=%s\n", WiFi.localIP().toString().c_str());
  } else {
    Serial.println("WiFi: FAILED — will retry");
  }
}

// Minimal URL encoder for the meter name path segment
String urlEncode(const String& s) {
  String out;
  for (size_t i = 0; i < s.length(); i++) {
    char c = s[i];
    if ((c >= '0' && c <= '9') || (c >= 'A' && c <= 'Z')
        || (c >= 'a' && c <= 'z') || c == '-' || c == '_' || c == '.' || c == '~') {
      out += c;
    } else {
      char buf[4];
      snprintf(buf, sizeof(buf), "%%%02X", (unsigned char) c);
      out += buf;
    }
  }
  return out;
}

bool pushMeterReading(float kwh, float peakDemand, float powerFactor) {
  if (WiFi.status() != WL_CONNECTED) return false;

  String url = String(NEXUS_HOST) + "/api/iot/meter/" + urlEncode(METER_NAME) + "/reading";

  // Build JSON body
  StaticJsonDocument<128> doc;
  doc["value"]        = kwh;
  doc["peak_demand"]  = peakDemand;
  doc["power_factor"] = powerFactor;
  String body;
  serializeJson(doc, body);

  HTTPClient http;
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-API-Token", NEXUS_TOKEN);
  http.setTimeout(10000);

  int code = http.POST(body);
  String resp = http.getString();
  http.end();

  Serial.printf("POST %s  →  %d  %s\n", url.c_str(), code, resp.substring(0, 100).c_str());
  return code >= 200 && code < 300;
}

// ----- Replace this stub with your real sensor read -----
float readKwh() {
  // Example: PZEM-004T over Serial, or accumulator from an INA219
  // For demo we just return a fake increasing value
  static float energy = 100.0f;
  energy += 0.05f;
  return energy;
}

float readPeakDemand() { return 4.2f; }
float readPowerFactor() { return 0.95f; }

void setup() {
  Serial.begin(115200);
  delay(500);
  connectWiFi();
}

void loop() {
  // Keep WiFi alive
  if (WiFi.status() != WL_CONNECTED) {
    connectWiFi();
    delay(2000);
    return;
  }

  unsigned long now = millis();
  if (now - lastPushMs >= PUSH_INTERVAL_MS || lastPushMs == 0) {
    lastPushMs = now;
    float kwh   = readKwh();
    float pdmd  = readPeakDemand();
    float pf    = readPowerFactor();
    pushMeterReading(kwh, pdmd, pf);
  }

  delay(200);
}
