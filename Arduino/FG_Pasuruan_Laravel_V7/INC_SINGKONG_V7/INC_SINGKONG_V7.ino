// ====================================================================
// PROJECT: WiFi Scale System V7.3 (Incoming Singkong Pasuruan)
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

// ============================================================
// === [PART 1] KONFIGURASI SISTEM ===
// ============================================================

const char* SERVER_BASE_URL = "http://192.168.10.204:8000"; 
const String DEVICE_TOKEN = "INC-SNG-001";                   
const String MODULE_TYPE = "incoming_singkong"; 

const char* REVERB_HOST = "192.168.10.204";
const int REVERB_PORT = 8080;
const char* REVERB_APP_KEY = "timbanganreverbkey";
WebSocketsClient webSocket;

const char* WIFI_SSID_1 = "Intranet";
const char* WIFI_PASS_1 = "aaaabbbb";

#define I2C_SDA D2
#define I2C_SCL D1
#define RX_PIN_SCALE D7
#define TX_PIN_SCALE D8
#define PIN_BTN_MENU D3
#define PIN_PROXIMITY D4
#define PIN_BTN_MANUAL D5  
#define PIN_BTN_TARE D6    

const int SCALE_BAUDRATE = 9600; 
const int LCD_UPDATE_INTERVAL = 1000;
const float MIN_VALID_WEIGHT = 0.5;
const unsigned long TRIGGER_DELAY = 800; 

// ============================================================
// === [PART 2] OBJEK & VARIABEL ===
// ============================================================

LiquidCrystal_I2C lcd(0x27, 20, 4);
ESP8266WiFiMulti wifiMulti;
SoftwareSerial scaleSerial(RX_PIN_SCALE, -1);

float rawWeight = 0.0;
float tareOffset = 0.0;
float currentWeight = 0.0;
bool objectDetected = false;
bool dataSentForThisObject = false;
unsigned long proximityStartTime = 0;
unsigned long resetWeightTimer = 0;

String lcdProduk = "Belum Sync";
String currentKodeProduksi = "-";
String lcdPetugas = "-";
String currentStatus = "idle";
bool apiConnected = false;
String lastDataHistory[3] = {"-", "-", "-"};

unsigned long lastWifiReconnectAttempt = 0;
unsigned long lastWsHealthCheck = 0;

int currentPage = 0;
unsigned long lastLcdUpdate = 0;
bool forceLcdClear = true;
int lastPage = -1;

// ============================================================
// === [PART 3] SETUP ===
// ============================================================

void setup() {
    Serial.begin(115200);
    scaleSerial.begin(SCALE_BAUDRATE);

    pinMode(PIN_BTN_MENU, INPUT_PULLUP);
    pinMode(PIN_BTN_MANUAL, INPUT_PULLUP);
    pinMode(PIN_BTN_TARE, INPUT_PULLUP);
    pinMode(PIN_PROXIMITY, INPUT_PULLUP);

    Wire.begin(I2C_SDA, I2C_SCL);
    lcd.init();
    lcd.backlight();
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("  INC SINGKONG    ");
    lcd.setCursor(0, 1); lcd.print("PT LADANG LIMA INDO  ");

    WiFi.mode(WIFI_STA);
    WiFi.setSleepMode(WIFI_NONE_SLEEP);
    WiFi.setAutoReconnect(true);
    wifiMulti.addAP(WIFI_SSID_1, WIFI_PASS_1);

    connectToWiFi();
    if (WiFi.status() == WL_CONNECTED) syncSettings();
    connectWebSocket();
    forceLcdClear = true;
}

// ============================================================
// === [PART 4] MAIN LOOP ===
// ============================================================

void loop() {
    static unsigned long lastSyncRetry = 0;
    
    if (WiFi.status() != WL_CONNECTED) {
        if (millis() - lastWifiReconnectAttempt > 5000) {
            lastWifiReconnectAttempt = millis();
            wifiMulti.run();
        }
    } else {
        webSocket.loop();
        if (!apiConnected && (millis() - lastSyncRetry > 10000)) {
            syncSettings();
            lastSyncRetry = millis();
        }
        if (millis() - lastWsHealthCheck > 30000) {
            lastWsHealthCheck = millis();
            if (!webSocket.isConnected()) connectWebSocket();
        }
    }

    while (scaleSerial.available() > 0) {
        char c = scaleSerial.read();
        static String buffer = "";
        if (c == '\n') {
            buffer.trim();
            if (buffer.length() > 0) {
                rawWeight = buffer.toFloat();
                currentWeight = rawWeight - tareOffset;
            }
            buffer = "";
        } else {
            if (buffer.length() < 20) buffer += c;
        }
    }

    if (currentWeight < (MIN_VALID_WEIGHT / 2)) {
        if (resetWeightTimer == 0) resetWeightTimer = millis();
        if (millis() - resetWeightTimer > 1000) {
            dataSentForThisObject = false;
            resetWeightTimer = 0;
            objectDetected = false;
        }
    } else {
        resetWeightTimer = 0;
    }

    handleTriggers();
    handleButtons();
    updateDisplay();
}

// ============================================================
// === [PART 5] API FUNCTIONS ===
// ============================================================

void connectWebSocket() {
    String wsUrl = String("/app/") + REVERB_APP_KEY + "?protocol=7&client=arduino&version=1.0.0";
    webSocket.begin(REVERB_HOST, REVERB_PORT, wsUrl.c_str());
    webSocket.onEvent(webSocketEvent);
}

void webSocketEvent(WStype_t type, uint8_t * payload, size_t length) {
    switch(type) {
        case WStype_CONNECTED: {
            String msg = "{\"event\":\"pusher:subscribe\",\"data\":{\"channel\":\"iot-weights." + MODULE_TYPE + "\"}}";
            webSocket.sendTXT(msg);
            break;
        }
        case WStype_TEXT: {
            String text = (char*)payload;
            DynamicJsonDocument doc(1024);
            deserializeJson(doc, text);
            String eventName = doc["event"].as<String>();
            if(eventName == "pusher:ping") webSocket.sendTXT("{\"event\":\"pusher:pong\"}");
            if(eventName == "WeightReceived") {
                String dataStr = doc["data"].as<String>();
                DynamicJsonDocument dataDoc(512);
                deserializeJson(dataDoc, dataStr);
                lcdProduk = dataDoc["nama_produk"].as<String>();
                currentKodeProduksi = dataDoc["kode_produksi"].as<String>();
                lcdPetugas = dataDoc["operator"].as<String>();
                forceLcdClear = true;
            }
            break;
        }
    }
}

void syncSettings() {
    HTTPClient http;
    WiFiClient client;
    String url = String(SERVER_BASE_URL) + "/api/iot/incoming-singkong/settings?token=" + DEVICE_TOKEN;
    http.begin(client, url);
    int code = http.GET();
    if (code == 200) {
        apiConnected = true;
        DynamicJsonDocument doc(512);
        deserializeJson(doc, http.getString());
        currentStatus = doc["status"].as<String>();
        if (currentStatus == "ready") {
            currentKodeProduksi = doc["kode_produksi"].as<String>();
            lcdProduk = doc["nama_produk"].as<String>();
            lcdPetugas = doc["operator"].as<String>();
        }
    }
    http.end();
}

void sendWeightToLaravel() {
    HTTPClient http;
    WiFiClient client;
    String url = String(SERVER_BASE_URL) + "/api/iot/incoming-singkong/weight";
    StaticJsonDocument<200> doc;
    doc["token"] = DEVICE_TOKEN;
    doc["kode_produksi"] = currentKodeProduksi;
    doc["weight"] = currentWeight;
    String body;
    serializeJson(doc, body);
    http.begin(client, url);
    http.addHeader("Content-Type", "application/json");
    int code = http.POST(body);
    if (code == 200 || code == 201) {
        dataSentForThisObject = true;
        updateHistory(currentWeight, currentKodeProduksi);
    }
    http.end();
    forceLcdClear = true;
}

void updateDisplay() {
    if (millis() - lastLcdUpdate < LCD_UPDATE_INTERVAL) return;
    if (forceLcdClear || currentPage != lastPage) { lcd.clear(); forceLcdClear = false; lastPage = currentPage; }
    showMainPage();
    lastLcdUpdate = millis();
}

void showMainPage() {
    lcd.setCursor(0, 0); lcd.print("JS: "); lcd.print(lcdProduk.substring(0, 16));
    lcd.setCursor(0, 1); lcd.print("KP: "); lcd.print(currentKodeProduksi.substring(0, 16));
    lcd.setCursor(0, 2); lcd.print("OP: "); lcd.print(lcdPetugas.substring(0, 11));
    lcd.setCursor(0, 3); lcd.print(dataSentForThisObject ? "SENT: " : "BRT: ");
    lcd.setCursor(6, 3); lcd.print(currentWeight, 2); lcd.print(" kg");
}

void updateHistory(float weight, String lot) {
    for(int i=2; i>0; i--) lastDataHistory[i] = lastDataHistory[i-1];
    lastDataHistory[0] = lot.substring(lot.length()-4) + " " + String(weight, 2) + "kg";
}

void connectToWiFi() {
    while (WiFi.status() != WL_CONNECTED) {
        wifiMulti.run();
        delay(500);
    }
}

void handleTriggers() {
    if (!dataSentForThisObject && currentWeight >= MIN_VALID_WEIGHT && currentKodeProduksi != "-") {
        if (digitalRead(PIN_PROXIMITY) == LOW) {
            if (!objectDetected) { proximityStartTime = millis(); objectDetected = true; }
            else if (millis() - proximityStartTime >= TRIGGER_DELAY) sendWeightToLaravel();
        } else { objectDetected = false; }
        
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
    if (digitalRead(PIN_BTN_MENU) == LOW) {
        delay(50);
        currentPage++; if (currentPage > 2) currentPage = 0;
        forceLcdClear = true;
        while(digitalRead(PIN_BTN_MENU) == LOW) yield();
    }
    if (digitalRead(PIN_BTN_TARE) == LOW) {
        tareOffset = rawWeight;
        forceLcdClear = true;
        while(digitalRead(PIN_BTN_TARE) == LOW) yield();
    }
}
