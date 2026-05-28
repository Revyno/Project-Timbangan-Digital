/**
 * ====================================================================
 * PROJECT : WiFi Scale System (SURABAYA - FORMULASI)
 * DEVICE  : ESP8266 NodeMCU / Wemos D1 Mini + RS232-TTL + LCD 16x02
 * VERSION : V7.0 — refactored from V6.19
 * FEATURES: WDT, Auto-Reconnect, Offline Queue (LittleFS),
 *           Long-Press Buttons, 6-Page LCD, JSON Sync, HTTP Retry
 * ====================================================================
 *
 * LIBRARY YANG WAJIB DI-INSTALL via Library Manager:
 *   1. ESP8266WiFi         (sudah termasuk di ESP8266 Board Package)
 *   2. ESPAsyncTCP         (oleh me-no-dev)
 *   3. LiquidCrystal I2C  (oleh Frank de Brabander)
 *   4. ArduinoJson         (oleh Benoit Blanchon) — versi 6.x / 7.x
 *   5. EspSoftwareSerial   (oleh Dirk Kaar) — untuk SoftwareSerial
 *
 * ====================================================================
 */

#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>
#include <ESP8266HTTPClient.h>
#include <SoftwareSerial.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <LittleFS.h>
#include <ArduinoJson.h>

// ============================================================
// === [PART 1] KONFIGURASI — SESUAIKAN SEBELUM UPLOAD ===
// ============================================================

#define LOCATION_NAME  "SBY - FORMULASI"
#define DEVICE_TOKEN   "FG-SBY-001"
#define BASE_URL       "http://192.168.1.16:8000"   // ← IP Laptop (dari ipconfig Wi-Fi)

// WiFi Credentials (Multi-AP)
const char* WIFI_SSID_1 = "B9 Kost";
const char* WIFI_PASS_1 = "manakutahu";
const char* WIFI_SSID_2 = "PT LSI";
const char* WIFI_PASS_2 = "Ladang593";
const char* WIFI_SSID_3 = "Ladang Lima";
const char* WIFI_PASS_3 = "Ladang593";

// ============================================================
// === [PART 2] HARDWARE PINS ===
// ============================================================

#define I2C_SDA_PIN   4    // D2 — SDA LCD
#define I2C_SCL_PIN   5    // D1 — SCL LCD
#define RX_PIN_SCALE  13   // D7 — TX Timbangan (RS232 → ESP8266)
#define TX_PIN_SCALE  15   // D8 — RX Timbangan (jarang dipakai)
#define BTN_MENU      14   // D5 — Ganti Halaman / Hold=Sync
#define BTN_SEND      12   // D6 — Kirim Berat   / Hold=Reconnect

// ============================================================
// === [PART 3] KONSTANTA ===
// ============================================================

const int   SCALE_BAUDRATE    = 9600;
const float WEIGHT_DIVIDER    = 10.0f;
const float MIN_VALID_WEIGHT  = 0.05f;   // 50 gram
const int   MAX_QUEUE_SIZE    = 50;
const char* QUEUE_FILE        = "/queue.dat";
const unsigned long SYNC_INTERVAL = 30000UL; // 30 detik

// ============================================================
// === [PART 4] OBJEK GLOBAL ===
// ============================================================

LiquidCrystal_I2C lcd(0x27, 16, 2);
ESP8266WiFiMulti  wifiMulti;
SoftwareSerial    scaleSerial(RX_PIN_SCALE, TX_PIN_SCALE);

// Data dari server
String currentOperator = "Menunggu...";
String currentProduk   = "Belum Pilih";
String currentKode     = "-";
int    todayCount      = 0;
float  todayTotal      = 0.0f;

// State timbangan
float  currentWeight   = 0.0f;
String serialBuffer    = "";
int    lcdPage         = 1;

// Timers
unsigned long lastLcdUpdate   = 0;
unsigned long lastSync        = 0;
unsigned long lastDebounce    = 0;

// Flag
bool isSending = false;

// Offline queue
struct WeighData {
    float         weight;
    unsigned long timestamp;
};
WeighData offlineQueue[MAX_QUEUE_SIZE];
int       queueCount = 0;

// ============================================================
// === [PART 5] FUNCTION PROTOTYPES ===
// ============================================================

void  resetWatchdog();
void  connectToWiFi();
void  syncSettings();
bool  sendData(float w);
void  processQueue();
void  saveQueue();
void  loadQueue();
void  addToQueue(float w);
void  handleButtons();
void  showPage(int page);
void  lcdPrintCenter(String text, int row);
void  lcdPad(int row, String line0, String line1);
void  showSpinner(int col, int row);
float parseSonicFixed(String data);
String formatW(float v, int d);

// ============================================================
// === [PART 6] SETUP ===
// ============================================================

void setup() {
    Serial.begin(115200);
    Serial.println(F("\n\n=== " LOCATION_NAME " Booting ==="));

    // Watchdog: reset jika hang > 8 detik
    ESP.wdtEnable(WDTO_8S);

    // Pins
    pinMode(BTN_MENU, INPUT_PULLUP);
    pinMode(BTN_SEND, INPUT_PULLUP);

    // Serial timbangan
    scaleSerial.begin(SCALE_BAUDRATE);

    // LCD
    Wire.begin(I2C_SDA_PIN, I2C_SCL_PIN);
    lcd.init();
    lcd.backlight();
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("PT LADANG LIMA  ");
    lcd.setCursor(0, 1); lcd.print("SBY-FORMULASI   ");
    delay(800);

    // LittleFS
    if (!LittleFS.begin()) {
        Serial.println(F("[FS] LittleFS GAGAL! Format atau periksa flash."));
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("FS ERROR!");
        delay(2000);
    } else {
        loadQueue();
        Serial.printf("[FS] Queue loaded: %d item(s)\n", queueCount);
    }

    // WiFi — Multi-AP + optimasi daya
    WiFi.mode(WIFI_STA);
    WiFi.setSleepMode(WIFI_NONE_SLEEP);   // Cegah WiFi tidur (lebih stabil)
    WiFi.setOutputPower(20.5f);           // Daya pancar maksimal (20.5 dBm)
    WiFi.setAutoReconnect(true);

    wifiMulti.addAP(WIFI_SSID_1, WIFI_PASS_1);
    wifiMulti.addAP(WIFI_SSID_2, WIFI_PASS_2);
    wifiMulti.addAP(WIFI_SSID_3, WIFI_PASS_3);

    connectToWiFi();
    syncSettings();

    lcd.clear();
}

// ============================================================
// === [PART 7] LOOP UTAMA ===
// ============================================================

void loop() {
    resetWatchdog();
    wifiMulti.run();  // Pastikan WiFi tetap terhubung

    // --- Baca RS232 Timbangan ---
    while (scaleSerial.available() > 0) {
        char c = scaleSerial.read();
        if (c == '+' || c == '-') {
            serialBuffer = "";
            serialBuffer += c;
        } else if (serialBuffer.length() > 0 && isDigit(c)) {
            serialBuffer += c;
            if (serialBuffer.length() == 7) {
                float val = parseSonicFixed(serialBuffer);
                if (val >= 0.0f) currentWeight = val;
                serialBuffer = "";
            }
        }
    }

    // --- Tombol ---
    handleButtons();

    // --- Update LCD ---
    if (millis() - lastLcdUpdate >= 250) {
        lastLcdUpdate = millis();
        showPage(lcdPage);
    }

    // --- Sinkronisasi berkala (30 detik) ---
    if (millis() - lastSync >= SYNC_INTERVAL) {
        lastSync = millis();
        syncSettings();
        processQueue();

        // Ping server untuk update last_online device
        if (WiFi.status() == WL_CONNECTED) {
            WiFiClient cl;
            HTTPClient http;
            String pingUrl = String(BASE_URL) + "/api/iot/ping?token=" + DEVICE_TOKEN;
            if (http.begin(cl, pingUrl)) {
                http.addHeader("User-Agent", "ESP8266-Scale/" LOCATION_NAME);
                http.GET();
                http.end();
            }
        }
    }

    delay(10); // yield background WiFi tasks
}

// ============================================================
// === [PART 8] WIFI & HTTP ===
// ============================================================

void resetWatchdog() {
    ESP.wdtFeed();
}

void connectToWiFi() {
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("Mencari WiFi... ");
    Serial.println(F("[WiFi] Connecting..."));

    unsigned long start = millis();
    while (wifiMulti.run() != WL_CONNECTED && millis() - start < 15000) {
        resetWatchdog();
        showSpinner(15, 0);
        lcd.setCursor(0, 1);
        lcd.print(("tunggu " + String((millis() - start) / 1000) + "s ").substring(0, 16));
        delay(500);
    }

    lcd.clear();
    if (WiFi.status() == WL_CONNECTED) {
        Serial.print(F("[WiFi] Connected: ")); Serial.println(WiFi.localIP());
        lcd.setCursor(0, 0); lcd.print("WiFi CONNECTED! ");
        lcd.setCursor(0, 1); lcd.print((WiFi.SSID() + "                ").substring(0, 16));
    } else {
        Serial.println(F("[WiFi] FAILED — offline mode aktif."));
        lcd.setCursor(0, 0); lcd.print("WiFi GAGAL!     ");
        lcd.setCursor(0, 1); lcd.print("Mode OFFLINE    ");
    }
    delay(1500);
    lcd.clear();
}

void syncSettings() {
    if (WiFi.status() != WL_CONNECTED) return;

    String url = String(BASE_URL) + "/api/iot/settings?token=" + DEVICE_TOKEN;
    WiFiClient client;
    HTTPClient http;

    if (!http.begin(client, url)) return;

    http.addHeader("User-Agent", "ESP8266-Scale/" LOCATION_NAME);
    http.setTimeout(8000);
    int code = http.GET();

    if (code == HTTP_CODE_OK) {
        String payload = http.getString();
        StaticJsonDocument<512> doc;
        DeserializationError err = deserializeJson(doc, payload);
        if (!err) {
            currentOperator = doc["operator"] | "N/A";
            currentProduk   = doc["nama_produk"] | "Idle";
            currentKode     = doc["kode_produksi"] | "-";
            Serial.printf("[Sync] Op=%s Produk=%s KP=%s\n",
                currentOperator.c_str(), currentProduk.c_str(), currentKode.c_str());
        }
    } else {
        Serial.printf("[Sync] HTTP %d\n", code);
    }
    http.end();
}

/**
 * Kirim berat ke server Laravel via HTTP POST
 * Return: true jika server menjawab 200/201
 */
bool sendData(float w) {
    if (WiFi.status() != WL_CONNECTED) return false;

    bool success = false;

    for (int retry = 1; retry <= 2; retry++) {
        WiFiClient client;
        HTTPClient http;

        if (!http.begin(client, String(BASE_URL) + "/api/iot/weight")) break;

        http.addHeader("Content-Type", "application/x-www-form-urlencoded");
        http.addHeader("User-Agent", "ESP8266-Scale/" LOCATION_NAME);
        http.setTimeout(10000);

        String body = "token=" + String(DEVICE_TOKEN)
                    + "&berat=" + String(w, 3)
                    + "&kode_produksi=" + currentKode;

        int code = http.POST(body);
        http.end();

        if (code == HTTP_CODE_OK || code == 201) {
            success = true;
            todayCount++;
            todayTotal += w;
            break;
        }

        Serial.printf("[Send] Retry %d — HTTP %d\n", retry, code);
        if (retry < 2) delay(1000);
    }
    return success;
}

// ============================================================
// === [PART 9] OFFLINE QUEUE ===
// ============================================================

void saveQueue() {
    File f = LittleFS.open(QUEUE_FILE, "w");
    if (!f) return;
    f.write((uint8_t*)offlineQueue, sizeof(offlineQueue));
    f.write((uint8_t*)&queueCount, sizeof(int));
    f.close();
}

void loadQueue() {
    if (!LittleFS.exists(QUEUE_FILE)) { queueCount = 0; return; }
    File f = LittleFS.open(QUEUE_FILE, "r");
    if (!f) return;
    f.read((uint8_t*)offlineQueue, sizeof(offlineQueue));
    f.read((uint8_t*)&queueCount, sizeof(int));
    f.close();
    if (queueCount < 0 || queueCount > MAX_QUEUE_SIZE) queueCount = 0;
}

void addToQueue(float w) {
    if (queueCount < MAX_QUEUE_SIZE) {
        offlineQueue[queueCount++] = {w, millis()};
    } else {
        // Geser: hapus paling lama
        for (int i = 0; i < MAX_QUEUE_SIZE - 1; i++) offlineQueue[i] = offlineQueue[i + 1];
        offlineQueue[MAX_QUEUE_SIZE - 1] = {w, millis()};
    }
    saveQueue();
    Serial.printf("[Queue] Tambah %.2f kg — total antrian: %d\n", w, queueCount);
}

void processQueue() {
    if (queueCount == 0 || WiFi.status() != WL_CONNECTED || isSending) return;
    isSending = true;
    Serial.printf("[Queue] Proses %d item...\n", queueCount);

    while (queueCount > 0 && WiFi.status() == WL_CONNECTED) {
        resetWatchdog();
        if (sendData(offlineQueue[0].weight)) {
            for (int i = 0; i < queueCount - 1; i++) offlineQueue[i] = offlineQueue[i + 1];
            queueCount--;
            saveQueue();
            delay(300);
        } else {
            break;
        }
    }
    isSending = false;
}

// ============================================================
// === [PART 10] TOMBOL (Short / Long Press) ===
// ============================================================

void handleButtons() {
    // --- TOMBOL SEND (D6) ---
    if (digitalRead(BTN_SEND) == LOW) {
        unsigned long pressStart   = millis();
        bool          longActioned = false;
        bool          uiChanged    = false;

        while (digitalRead(BTN_SEND) == LOW) {
            resetWatchdog();
            unsigned long dur = millis() - pressStart;

            if (dur > 500 && !longActioned) {
                if (!uiChanged) {
                    lcd.clear();
                    lcd.setCursor(0, 0); lcd.print("Tahan=Reconnect ");
                    uiChanged = true;
                }
                int bars = constrain(map((long)dur, 500, 2000, 0, 16), 0, 16);
                lcd.setCursor(0, 1);
                for (int i = 0; i < 16; i++) lcd.print(i < bars ? '>' : ' ');
            }
            if (dur > 2000 && !longActioned) {
                longActioned = true;
                connectToWiFi();
                syncSettings();
            }
            delay(50);
        }

        if (!longActioned && (millis() - lastDebounce) > 300) {
            lastDebounce = millis();
            if (currentWeight >= MIN_VALID_WEIGHT) {
                lcd.clear(); lcdPrintCenter("SENDING...", 0);
                if (sendData(currentWeight)) {
                    lcdPrintCenter("SUKSES!", 1);
                } else {
                    lcdPrintCenter("OFFLINE SAVED", 1);
                    addToQueue(currentWeight);
                }
                delay(1500);
                lcd.clear();
            } else {
                lcd.clear();
                lcdPrintCenter("!BERAT KOSONG!", 0);
                delay(1000);
                lcd.clear();
            }
        }
    }

    // --- TOMBOL MENU (D5) ---
    if (digitalRead(BTN_MENU) == LOW) {
        unsigned long pressStart   = millis();
        bool          longActioned = false;
        bool          uiChanged    = false;

        while (digitalRead(BTN_MENU) == LOW) {
            resetWatchdog();
            unsigned long dur = millis() - pressStart;

            if (dur > 500 && !longActioned) {
                if (!uiChanged) {
                    lcd.clear();
                    lcd.setCursor(0, 0); lcd.print("Tahan=Sync Data ");
                    uiChanged = true;
                }
                int bars = constrain(map((long)dur, 500, 2000, 0, 16), 0, 16);
                lcd.setCursor(0, 1);
                for (int i = 0; i < 16; i++) lcd.print(i < bars ? '>' : ' ');
            }
            if (dur > 2000 && !longActioned) {
                longActioned = true;
                syncSettings();
                lcd.clear(); lcdPrintCenter("SYNC DONE!", 0);
                delay(1000); lcd.clear();
            }
            delay(50);
        }

        if (!longActioned && (millis() - lastDebounce) > 300) {
            lastDebounce = millis();
            lcdPage++;
            if (lcdPage > 6) lcdPage = 1;
            lcd.clear();
        }
    }
}

// ============================================================
// === [PART 11] TAMPILAN LCD — 6 HALAMAN ===
// ============================================================

void showPage(int page) {
    bool online = (WiFi.status() == WL_CONNECTED);

    switch (page) {
        case 1:  // Berat + Status
            lcd.setCursor(0, 0);
            lcd.print((String(LOCATION_NAME) + "       ").substring(0, 15));
            lcd.setCursor(15, 0); lcd.print(online ? '*' : '!');
            lcd.setCursor(0, 1);
            lcd.print(("W:" + formatW(currentWeight, 2) + "kg C:" + String(todayCount) + "   ").substring(0, 16));
            break;

        case 2:  // Operator & Produk
            lcd.setCursor(0, 0);
            lcd.print(("Op:" + currentOperator + "             ").substring(0, 16));
            lcd.setCursor(0, 1);
            lcd.print(("P: " + currentProduk + "             ").substring(0, 16));
            break;

        case 3:  // Kode Produksi & Queue
            lcd.setCursor(0, 0);
            lcd.print(("KP:" + currentKode + "              ").substring(0, 16));
            lcd.setCursor(0, 1);
            lcd.print(("Queue: " + String(queueCount) + " item(s)   ").substring(0, 16));
            break;

        case 4:  // Total & Hitungan
            lcd.setCursor(0, 0);
            lcd.print(("Tot: " + formatW(todayTotal, 1) + " kg     ").substring(0, 16));
            lcd.setCursor(0, 1);
            lcd.print(("Count: " + String(todayCount) + " kali   ").substring(0, 16));
            break;

        case 5:  // IP & RSSI
            lcd.setCursor(0, 0);
            lcd.print((online ? WiFi.localIP().toString() : "0.0.0.0") + "        ");
            lcd.setCursor(0, 1);
            lcd.print(("RSSI:" + String(WiFi.RSSI()) + " dBm      ").substring(0, 16));
            break;

        case 6:  // SSID & MAC
            lcd.setCursor(0, 0);
            lcd.print(((online ? WiFi.SSID() : "No WiFi") + "                ").substring(0, 16));
            {
                String mac = WiFi.macAddress();
                mac.replace(":", "");
                lcd.setCursor(0, 1);
                lcd.print((mac + "                ").substring(0, 16));
            }
            break;
    }
}

// ============================================================
// === [PART 12] HELPER FUNCTIONS ===
// ============================================================

void lcdPrintCenter(String text, int row) {
    int len = text.length();
    int pos = (16 - len) / 2;
    if (pos < 0) pos = 0;
    lcd.setCursor(pos, row);
    lcd.print(text);
}

void showSpinner(int col, int row) {
    static int spinIdx = 0;
    const char spinChars[] = {'|', '/', '-', '\\'};
    lcd.setCursor(col, row);
    lcd.print(spinChars[spinIdx]);
    spinIdx = (spinIdx + 1) % 4;
}

/**
 * Parse format timbangan Sonic XK-3190 A1+
 * Data: [+/-][6 digit angka] → value / 10
 * Return: -1.0 jika tidak valid
 */
float parseSonicFixed(String data) {
    if (data.length() < 7) return -1.0f;
    String numStr = data.substring(1, 7);
    for (int i = 0; i < (int)numStr.length(); i++) {
        if (!isDigit(numStr.charAt(i))) return -1.0f;
    }
    return numStr.toFloat() / WEIGHT_DIVIDER;
}

/**
 * Format float menggunakan koma (Indonesian)
 */
String formatW(float v, int d) {
    String s = String(v, d);
    s.replace('.', ',');
    return s;
}
