// ====================================================================
// PROJECT: WiFi Scale System V7.3 (CS FG Surabaya)
// DEVICE:  ROBODYN D1 R2 (ESP8266)
// AUTHOR:  PT LADANG LIMA INDONESIA
// VERSION: V7.3 (LARAVEL API + REVERB)
// ====================================================================

#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <Wire.h>
#include <SoftwareSerial.h>
#include <LiquidCrystal_I2C.h>
#include <ArduinoJson.h>
#include <WebSocketsClient.h>

// --- LARAVEL API CONFIG ---
const char* SERVER_BASE_URL = "http://192.168.10.204:8000"; 
const String DEVICE_TOKEN = "CS-FG-001";                   
const String MODULE_TYPE = "cs_fg_sby"; 

// --- REVERB WEBSOCKET CONFIG ---
const char* REVERB_HOST = "192.168.10.204";
const int REVERB_PORT = 8080;
const char* REVERB_APP_KEY = "timbanganreverbkey";
WebSocketsClient webSocket;

// --- WIFI CREDENTIALS ---
const char* WIFI_SSID_1 = "Intranet";
const char* WIFI_PASS_1 = "aaaabbbb";

// --- PIN DEFINITIONS ---
#define I2C_SDA D2
#define I2C_SCL D1
#define RX_PIN_SCALE D7
#define TX_PIN_SCALE D8
#define PIN_BTN_MENU D3
#define PIN_PROXIMITY D4
#define PIN_BTN_MANUAL D5  
#define PIN_BTN_TARE D6    

LiquidCrystal_I2C lcd(0x27, 20, 4);
ESP8266WiFiMulti wifiMulti;
SoftwareSerial scaleSerial(RX_PIN_SCALE, -1);

float rawWeight = 0.0, tareOffset = 0.0, currentWeight = 0.0;
bool dataSentForThisObject = false;
String lcdProduk = "Belum Sync", currentKodeProduksi = "-", lcdPetugas = "-";
unsigned long lastLcdUpdate = 0;

void setup() {
    Serial.begin(115200);
    scaleSerial.begin(9600);
    pinMode(PIN_BTN_MENU, INPUT_PULLUP);
    pinMode(PIN_BTN_MANUAL, INPUT_PULLUP);
    pinMode(PIN_BTN_TARE, INPUT_PULLUP);
    pinMode(PIN_PROXIMITY, INPUT_PULLUP);
    Wire.begin(I2C_SDA, I2C_SCL);
    lcd.init(); lcd.backlight();
    lcd.setCursor(0, 0); lcd.print("  CS FG SURABAYA  ");
    wifiMulti.addAP(WIFI_SSID_1, WIFI_PASS_1);
    while (WiFi.status() != WL_CONNECTED) { wifiMulti.run(); delay(500); }
    syncSettings();
    connectWebSocket();
}

void loop() {
    webSocket.loop();
    while (scaleSerial.available() > 0) {
        char c = scaleSerial.read();
        static String buffer = "";
        if (c == '\n') {
            buffer.trim(); if (buffer.length() > 0) currentWeight = buffer.toFloat() - tareOffset;
            buffer = "";
        } else { buffer += c; }
    }
    handleTriggers();
    handleButtons();
    updateDisplay();
}

void connectWebSocket() {
    String wsUrl = String("/app/") + REVERB_APP_KEY + "?protocol=7&client=arduino&version=1.0.0";
    webSocket.begin(REVERB_HOST, REVERB_PORT, wsUrl.c_str());
    webSocket.onEvent(webSocketEvent);
}

void webSocketEvent(WStype_t type, uint8_t * payload, size_t length) {
    if(type == WStype_CONNECTED) {
        String msg = "{\"event\":\"pusher:subscribe\",\"data\":{\"channel\":\"iot-weights." + MODULE_TYPE + "\"}}";
        webSocket.sendTXT(msg);
    } else if(type == WStype_TEXT) {
        DynamicJsonDocument doc(1024); deserializeJson(doc, (char*)payload);
        if(doc["event"] == "WeightReceived") {
            DynamicJsonDocument dataDoc(512); deserializeJson(dataDoc, doc["data"].as<String>());
            lcdProduk = dataDoc["nama_produk"].as<String>();
            currentKodeProduksi = dataDoc["kode_produksi"].as<String>();
            lcdPetugas = dataDoc["operator"].as<String>();
            lcd.clear();
        }
    }
}

void syncSettings() {
    HTTPClient http; WiFiClient client;
    String url = String(SERVER_BASE_URL) + "/api/iot/cs-fg-sby/settings?token=" + DEVICE_TOKEN;
    http.begin(client, url);
    if (http.GET() == 200) {
        DynamicJsonDocument doc(512); deserializeJson(doc, http.getString());
        if (doc["status"] == "ready") {
            currentKodeProduksi = doc["kode_produksi"].as<String>();
            lcdProduk = doc["nama_produk"].as<String>();
            lcdPetugas = doc["operator"].as<String>();
        }
    }
    http.end();
}

void sendWeightToLaravel() {
    HTTPClient http; WiFiClient client;
    String url = String(SERVER_BASE_URL) + "/api/iot/cs-fg-sby/weight";
    StaticJsonDocument<200> doc;
    doc["token"] = DEVICE_TOKEN; doc["kode_produksi"] = currentKodeProduksi; doc["weight"] = currentWeight;
    String body; serializeJson(doc, body);
    http.begin(client, url); http.addHeader("Content-Type", "application/json");
    if (http.POST(body) == 200) dataSentForThisObject = true;
    http.end(); lcd.clear();
}

void updateDisplay() {
    if (millis() - lastLcdUpdate < 1000) return;
    lcd.setCursor(0, 0); lcd.print("NP: "); lcd.print(lcdProduk.substring(0, 16));
    lcd.setCursor(0, 1); lcd.print("KP: "); lcd.print(currentKodeProduksi.substring(0, 16));
    lcd.setCursor(0, 2); lcd.print("OP: "); lcd.print(lcdPetugas.substring(0, 11));
    lcd.setCursor(0, 3); lcd.print(dataSentForThisObject ? "SENT: " : "BRT: ");
    lcd.print(currentWeight, 2); lcd.print(" kg");
    lastLcdUpdate = millis();
}

void handleTriggers() {
    if (!dataSentForThisObject && currentWeight >= 0.5 && currentKodeProduksi != "-") {
        if (digitalRead(PIN_PROXIMITY) == LOW) { delay(800); if (digitalRead(PIN_PROXIMITY) == LOW) sendWeightToLaravel(); }
        if (digitalRead(PIN_BTN_MANUAL) == LOW) {
            delay(50);
            if (digitalRead(PIN_BTN_MANUAL) == LOW) {
                sendWeightToLaravel();
                while(digitalRead(PIN_BTN_MANUAL) == LOW) yield();
            }
        }
    }
}

void handleButtons() {
    if (digitalRead(PIN_BTN_TARE) == LOW) { tareOffset += currentWeight; lcd.clear(); delay(500); }
}
