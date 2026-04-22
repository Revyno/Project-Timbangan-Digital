/**
 * ====================================================================
 * PROJECT: WiFi Scale System V7.3 (Finished Goods Pasuruan)
 * DEVICE:  ROBODYN D1 R2 (ESP8266)
 * AUTHOR:  PT LADANG  LIMA INDONESIA
 * VERSION: V7.3 (LARAVEL API + V6.6 OLED UI)
 * ====================================================================
 */

#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClient.h>
#include <Wire.h>
#include <SoftwareSerial.h>
#include <LiquidCrystal_I2C.h>
#include <ArduinoJson.h>

// ============================================================
// === [PART 1] KONFIGURASI SISTEM ===
// ============================================================

// --- LARAVEL API CONFIG ---
const char* SERVER_BASE_URL = "http://127.0.0.1:8000";
const String DEVICE_TOKEN = "token_rahasia_ard001";

// --- WIFI CREDENTIALS (KEEPING USER SETTINGS) ---
const char* WIFI_SSID_1 = "LSI";
const char* WIFI_PASS_1 = "Ladang593";
const char* WIFI_SSID_2 = "LSI";
const char* WIFI_PASS_2 = "Ladang593";
const char* WIFI_SSID_3 = "abc";
const char* WIFI_PASS_3 = "aaaabbbb";

// --- PIN DEFINITIONS (WEMOS D1 R2) ---
#define I2C_SDA D2
#define I2C_SCL D1
#define RX_PIN_SCALE D7
#define TX_PIN_SCALE D8
#define PIN_PROXIMITY D4
#define PIN_BTN_MANUAL D5
#define PIN_BTN_MENU D3
#define PIN_BTN_TARE D6

// Settings
const int SCALE_BAUDRATE = 9600;
const int LCD_UPDATE_INTERVAL = 200;
const float MIN_VALID_WEIGHT = 0.5;
const unsigned long TRIGGER_DELAY = 800;

// ============================================================
// === [PART 2] OBJEK & VARIABEL ===
// ============================================================

LiquidCrystal_I2C lcd(0x27, 20, 4);
ESP8266WiFiMulti wifiMulti;
SoftwareSerial scaleSerial(RX_PIN_SCALE, -1);

// Variabel Logika Berat
float rawWeight = 0.0;
float tareOffset = 0.0;
float currentWeight = 0.0;
bool objectDetected = false;
bool dataSentForThisObject = false;
unsigned long proximityStartTime = 0;
unsigned long resetWeightTimer = 0;

// Data Penimbangan (Sync dari Laravel)
String lcdProduk = "Belum Sync";
String currentKodeProduksi = "-";
String lcdShift = "-";
String lcdPetugas = "-";
unsigned int lastLoggedCounter = 0;
String lastDataHistory[3] = {"-", "-", "-"};

// Navigasi UI
int currentPage = 0;
unsigned long lastLcdUpdate = 0;
unsigned long lastSyncTime = 0;
const unsigned long SYNC_INTERVAL = 10000; // Tiap 10 detik
bool forceLcdClear = true;
int lastPage = -1;

// Status Tombol
unsigned long btnMenuStart = 0;
bool btnMenuActive = false;
unsigned long btnManualStart = 0;
bool btnManualActive = false;
bool btnTareActive = false;

// ============================================================
// === [PART 3] SETUP ===
// ============================================================

void setup() {
    Serial.begin(115200);
    scaleSerial.begin(SCALE_BAUDRATE);

    pinMode(PIN_BTN_MENU, INPUT_PULLUP);
    pinMode(PIN_BTN_MANUAL, INPUT_PULLUP);
    pinMode(PIN_PROXIMITY, INPUT_PULLUP);
    pinMode(PIN_BTN_TARE, INPUT_PULLUP);

    Wire.begin(I2C_SDA, I2C_SCL);
    lcd.init();
    lcd.backlight();
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("SYSTEM V7.3 LARAVEL");
    lcd.setCursor(0, 2); lcd.print("Connecting WiFi...");

    wifiMulti.addAP(WIFI_SSID_1, WIFI_PASS_1);
    wifiMulti.addAP(WIFI_SSID_2, WIFI_PASS_2);
    wifiMulti.addAP(WIFI_SSID_3, WIFI_PASS_3);

    connectToWiFi();
    syncSettings();

    lcd.clear();
    lcd.setCursor(0, 1); lcd.print("       READY!       ");
    delay(1000);
}

// ============================================================
// === [PART 4] MAIN LOOP ===
// ============================================================

void loop() {
    // 1. READ SCALE
    while (scaleSerial.available() > 0) {
        String base = scaleSerial.readStringUntil('\n');
        base.trim();
        if (base.length() > 0) {
            rawWeight = base.toFloat();
            currentWeight = rawWeight - tareOffset;
        }
    }

    // 2. AUTO SYNC
    if (millis() - lastSyncTime > SYNC_INTERVAL) {
        syncSettings();
        lastSyncTime = millis();
    }

    // 3. RESET LOGIC
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

    // 4. TRIGGER & BUTTONS
    handleTriggers();
    handleButtons();

    // 5. UPDATE DISPLAY
    updateDisplay();
}

void handleTriggers() {
    bool readyToSend = (!dataSentForThisObject && currentWeight >= MIN_VALID_WEIGHT && lcdProduk != "Belum Sync");

    if (readyToSend) {
        if (digitalRead(PIN_PROXIMITY) == LOW) {
            if (!objectDetected) {
                proximityStartTime = millis();
                objectDetected = true;
            } else if (millis() - proximityStartTime >= TRIGGER_DELAY) {
                sendWeightToLaravel();
            }
        } else {
            objectDetected = false;
        }

        if (digitalRead(PIN_BTN_MANUAL) == LOW) {
            delay(50);
            if (digitalRead(PIN_BTN_MANUAL) == LOW && !btnManualActive) {
                sendWeightToLaravel();
                while(digitalRead(PIN_BTN_MANUAL) == LOW) yield();
            }
        }
    }
}

void handleButtons() {
    unsigned long now = millis();

    // Menu Button
    if (digitalRead(PIN_BTN_MENU) == LOW) {
        if (!btnMenuActive) { btnMenuActive = true; btnMenuStart = now; }
    } else if (btnMenuActive) {
        long duration = now - btnMenuStart;
        btnMenuActive = false;
        if (duration > 5000) syncSettings();
        else {
            currentPage++;
            if (currentPage > 2) currentPage = 0;
            forceLcdClear = true;
        }
    }

    // Tare Button
    if (digitalRead(PIN_BTN_TARE) == LOW) {
        if (!btnTareActive) {
            delay(50);
            tareOffset = rawWeight;
            currentWeight = 0.0;
            btnTareActive = true;
            lcd.clear();
            lcd.setCursor(0, 1); lcd.print("     TARE OK!       ");
            delay(500);
            forceLcdClear = true;
        }
    } else {
        btnTareActive = false;
    }
}

// ============================================================
// === [PART 5] API FUNCTIONS ===
// ============================================================

void connectToWiFi() {
    unsigned long start = millis();
    while (wifiMulti.run() != WL_CONNECTED && millis() - start < 10000) {
        delay(500);
    }
}

void syncSettings() {
    if(WiFi.status() != WL_CONNECTED) return;

    HTTPClient http;
    WiFiClient client;
    String url = String(SERVER_BASE_URL) + "/api/iot/settings?token=" + DEVICE_TOKEN;

    http.begin(client, url);
    int code = http.GET();
    if (code == 200) {
        String payload = http.getString();
        DynamicJsonDocument doc(512);
        deserializeJson(doc, payload);

        if (doc["status"] == "ready") {
            currentKodeProduksi = doc["kode_produksi"].as<String>();
            lcdProduk = doc["nama_produk"].as<String>();
        } else {
            lcdProduk = "Tidak Ada Antrian";
            currentKodeProduksi = "-";
        }
    }
    http.end();
}

void sendWeightToLaravel() {
    lcd.clear();
    lcd.setCursor(0, 1); lcd.print("  SENDING DATA...   ");

    HTTPClient http;
    WiFiClient client;
    String url = String(SERVER_BASE_URL) + "/api/iot/weight";

    StaticJsonDocument<200> doc;
    doc["token"] = DEVICE_TOKEN;
    doc["kode_produksi"] = currentKodeProduksi;
    doc["berat"] = currentWeight;

    String body;
    serializeJson(doc, body);

    http.begin(client, url);
    http.addHeader("Content-Type", "application/json");

    int code = http.POST(body);
    if (code == 200) {
        dataSentForThisObject = true;
        updateHistory(currentWeight, currentKodeProduksi);
        lcd.setCursor(0, 3); lcd.print("SUCCESS!            ");
        delay(1000);
    }
    http.end();
    forceLcdClear = true;
}

// ============================================================
// === [PART 6] UI HELPERS ===
// ============================================================

void updateDisplay() {
    if (millis() - lastLcdUpdate < LCD_UPDATE_INTERVAL) return;

    if (forceLcdClear || currentPage != lastPage) {
        lcd.clear();
        forceLcdClear = false;
        lastPage = currentPage;
    }

    if (currentPage == 0) showMainPage();
    else if (currentPage == 1) showNetworkPage();
    else if (currentPage == 2) showHistoryPage();

    lastLcdUpdate = millis();
}

void showMainPage() {
    lcd.setCursor(0, 0); lcd.print(lcdProduk.substring(0, 20));
    lcd.setCursor(0, 1); lcd.print("LOT: "); lcd.print(currentKodeProduksi);

    lcd.setCursor(0, 2);
    if (dataSentForThisObject) {
        lcd.print(">> TERKIRIM <<      ");
    } else if (objectDetected) {
        lcd.print("LOCKING...          ");
    } else {
        lcd.print("BERAT: "); lcd.print(currentWeight, 3); lcd.print(" kg ");
    }

    lcd.setCursor(0, 3);
    lcd.print(WiFi.status() == WL_CONNECTED ? "WiFi:OK" : "WiFi:!!");
    lcd.setCursor(10, 3);
    lcd.print("V7.3 LARA");
}

void showNetworkPage() {
    lcd.setCursor(0, 0); lcd.print("--- NETWORK ---     ");
    lcd.setCursor(0, 1); lcd.print("SSID: "); lcd.print(WiFi.SSID().substring(0, 14));
    lcd.setCursor(0, 2); lcd.print("IP: "); lcd.print(WiFi.localIP().toString());
    lcd.setCursor(0, 3); lcd.print("RSSI: "); lcd.print(WiFi.RSSI()); lcd.print(" dBm");
}

void showHistoryPage() {
    lcd.setCursor(0, 0); lcd.print("--- LAST LOGS ---   ");
    for(int i=0; i<3; i++) {
        lcd.setCursor(0, i+1);
        lcd.print(lastDataHistory[i]);
    }
}

void updateHistory(float weight, String lot) {
    for(int i=2; i>0; i--) lastDataHistory[i] = lastDataHistory[i-1];
    lastDataHistory[0] = lot.substring(lot.length()-4) + " " + String(weight, 2) + "kg";
}
