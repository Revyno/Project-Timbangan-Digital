/**
 * ====================================================================
 * PROJECT: WiFi Scale System (SURABAYA - FORMULASI)
 * DEVICE:  ESP8266 + RS232-TTL + LCD 16x02
 * FEATURES: WDT, Auto-Reconnect, Offline Queue, JSON Sync Operator
 * ====================================================================
 */

#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecure.h>
#include <SoftwareSerial.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <LittleFS.h>
#include <ArduinoJson.h> // WAJIB INSTALL DI LIBRARY MANAGER

// ============================================================
// === [CONFIG] LOKASI & TOKEN ===
// ============================================================
#define LOCATION_NAME "SBY - FORMULASI"
#define DEVICE_TOKEN  "FG-SBY-001"
#define BASE_URL      "http://192.168.1.100:8000" // Sesuaikan IP PC Laragon Anda

// ============================================================
// === [HARDWARE PINS] ===
// ============================================================
#define I2C_SDA_PIN 4   // D2
#define I2C_SCL_PIN 5   // D1
#define RX_PIN_SCALE 13 // D7 -> TX Timbangan
#define TX_PIN_SCALE 15 // D8 -> RX Timbangan
#define BTN_MENU 0      // D3 (GPIO0)
#define BTN_SEND 2      // D4 (GPIO2)

// ============================================================
// === [CONSTANTS & GLOBALS] ===
// ============================================================
const char* WIFI_SSID_1 = "Intranet";
const char* WIFI_PASS_1 = "aaaabbbb";
const char* WIFI_SSID_2 = "PT LSI";
const char* WIFI_PASS_2 = "Ladang593";

const int SCALE_BAUDRATE = 9600;
const float MIN_VALID_WEIGHT = 0.05;
const int MAX_QUEUE_SIZE = 50;
const char* QUEUE_FILE = "/queue.dat";

LiquidCrystal_I2C lcd(0x27, 16, 2);
ESP8266WiFiMulti wifiMulti;
SoftwareSerial scaleSerial(RX_PIN_SCALE, TX_PIN_SCALE);

// Variabel Data dari Dashboard
String currentOperator = "Menunggu...";
String currentProduk = "Belum Pilih";
String currentKode = "-";

float currentWeight = 0.0;
String serialBuffer = "";
int lcdPage = 1;
unsigned long lastLcdUpdate = 0;
unsigned long lastPing = 0;
bool isSending = false;

struct WeighData {
    float weight;
    unsigned long timestamp;
};
WeighData offlineQueue[MAX_QUEUE_SIZE];
int queueCount = 0;

// ============================================================
// === [CORE FUNCTIONS] ===
// ============================================================

void resetWatchdog() { ESP.wdtFeed(); }

void lcdPrintCenter(String text, int row) {
    int len = text.length();
    int pos = (16 - len) / 2;
    if (pos < 0) pos = 0;
    lcd.setCursor(pos, row);
    lcd.print(text);
}

void syncSettings() {
    if (WiFi.status() != WL_CONNECTED) return;
    
    WiFiClient client;
    HTTPClient http;
    String url = String(BASE_URL) + "/api/iot/settings?token=" + String(DEVICE_TOKEN);
    
    if (http.begin(client, url)) {
        int httpCode = http.GET();
        if (httpCode == 200) {
            String payload = http.getString();
            StaticJsonDocument<512> doc;
            DeserializationError error = deserializeJson(doc, payload);
            
            if (!error) {
                currentOperator = doc["operator"] | "N/A";
                currentProduk = doc["nama_produk"] | "Idle";
                currentKode = doc["kode_produksi"] | "-";
            }
        }
        http.end();
    }
}

void saveQueue() {
    File file = LittleFS.open(QUEUE_FILE, "w");
    if (file) {
        file.write((uint8_t*)offlineQueue, sizeof(offlineQueue));
        file.write((uint8_t*)&queueCount, sizeof(int));
        file.close();
    }
}

void loadQueue() {
    if (!LittleFS.exists(QUEUE_FILE)) return;
    File file = LittleFS.open(QUEUE_FILE, "r");
    if (file) {
        file.read((uint8_t*)offlineQueue, sizeof(offlineQueue));
        file.read((uint8_t*)&queueCount, sizeof(int));
        file.close();
    }
}

void addToQueue(float w) {
    if (queueCount < MAX_QUEUE_SIZE) {
        offlineQueue[queueCount].weight = w;
        offlineQueue[queueCount].timestamp = millis();
        queueCount++;
    } else {
        for(int i=0; i<MAX_QUEUE_SIZE-1; i++) offlineQueue[i] = offlineQueue[i+1];
        offlineQueue[MAX_QUEUE_SIZE-1].weight = w;
        offlineQueue[MAX_QUEUE_SIZE-1].timestamp = millis();
    }
    saveQueue();
}

bool sendData(float w) {
    if (WiFi.status() != WL_CONNECTED) return false;
    WiFiClient client;
    HTTPClient http;
    http.begin(client, String(BASE_URL) + "/api/iot/weight");
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    String postData = "token=" + String(DEVICE_TOKEN) + "&berat=" + String(w, 3) + "&kode_produksi=" + currentKode;
    int httpCode = http.POST(postData);
    http.end();
    return (httpCode == 200 || httpCode == 201);
}

void processQueue() {
    if (queueCount == 0 || WiFi.status() != WL_CONNECTED || isSending) return;
    isSending = true;
    while (queueCount > 0 && WiFi.status() == WL_CONNECTED) {
        resetWatchdog();
        if (sendData(offlineQueue[0].weight)) {
            for (int i = 0; i < queueCount - 1; i++) offlineQueue[i] = offlineQueue[i + 1];
            queueCount--;
            saveQueue();
            delay(200);
        } else break;
    }
    isSending = false;
}

// ============================================================
// === [SETUP & LOOP] ===
// ============================================================

void setup() {
    Serial.begin(115200);
    ESP.wdtEnable(WDTO_8S);
    
    pinMode(BTN_MENU, INPUT_PULLUP);
    pinMode(BTN_SEND, INPUT_PULLUP);
    
    scaleSerial.begin(SCALE_BAUDRATE);
    Wire.begin(I2C_SDA_PIN, I2C_SCL_PIN);
    
    lcd.init();
    lcd.backlight();
    lcdPrintCenter("LSI SYSTEM SBY", 0);
    lcdPrintCenter("Booting...", 1);
    
    LittleFS.begin();
    loadQueue();
    
    WiFi.mode(WIFI_STA);
    WiFi.setAutoReconnect(true);
    wifiMulti.addAP(WIFI_SSID_1, WIFI_PASS_1);
    wifiMulti.addAP(WIFI_SSID_2, WIFI_PASS_2);
    
    delay(1500);
    syncSettings(); // Ambil data awal
}

void loop() {
    resetWatchdog();
    wifiMulti.run();
    
    // Membaca timbangan
    while (scaleSerial.available() > 0) {
        char c = scaleSerial.read();
        if (c == '+' || c == '-') { serialBuffer = ""; serialBuffer += c; }
        else if (serialBuffer.length() > 0 && isDigit(c)) {
            serialBuffer += c;
            if (serialBuffer.length() == 7) {
                currentWeight = serialBuffer.substring(1).toFloat() / 10.0;
                serialBuffer = "";
            }
        }
    }
    
    // Tombol Kirim
    if (digitalRead(BTN_SEND) == LOW) {
        delay(50);
        if (digitalRead(BTN_SEND) == LOW) {
            if (currentWeight >= MIN_VALID_WEIGHT) {
                lcd.clear(); lcdPrintCenter("SENDING...", 0);
                if (sendData(currentWeight)) {
                    lcdPrintCenter("SUCCESS!", 1);
                } else {
                    lcdPrintCenter("OFFLINE SAVED", 1);
                    addToQueue(currentWeight);
                }
                delay(1500); lcd.clear();
            }
            while(digitalRead(BTN_SEND) == LOW) resetWatchdog();
        }
    }
    
    // Tombol Menu / Ganti Halaman
    if (digitalRead(BTN_MENU) == LOW) {
        delay(50);
        if (digitalRead(BTN_MENU) == LOW) {
            lcdPage++; 
            if (lcdPage > 4) lcdPage = 1; // Sekarang ada 4 halaman
            lcd.clear();
            while(digitalRead(BTN_MENU) == LOW) resetWatchdog();
        }
    }
    
    // Update Tampilan LCD
    if (millis() - lastLcdUpdate > 300) {
        lastLcdUpdate = millis();
        switch(lcdPage) {
            case 1: // Halaman Utama: Berat
                lcd.setCursor(0,0); lcd.print(LOCATION_NAME);
                lcd.setCursor(0,1); lcd.print("W: " + String(currentWeight, 2) + " kg   ");
                break;
            case 2: // Halaman Operator & Produk
                lcd.setCursor(0,0); lcd.print("Op: " + currentOperator.substring(0,12));
                lcd.setCursor(0,1); lcd.print("P: " + currentProduk.substring(0,13));
                break;
            case 3: // Halaman Kode & Queue
                lcd.setCursor(0,0); lcd.print("KP: " + currentKode.substring(0,12));
                lcd.setCursor(0,1); lcd.print("Queue: " + String(queueCount) + " items");
                break;
            case 4: // Halaman Network
                lcd.setCursor(0,0); lcd.print("NET: " + (WiFi.status() == WL_CONNECTED ? String("ONLINE") : String("OFFLINE")));
                lcd.setCursor(0,1); lcd.print("IP: " + WiFi.localIP().toString());
                break;
        }
        lcd.setCursor(15, 0); lcd.print(WiFi.status() == WL_CONNECTED ? "*" : "!");
    }
    
    // Sync & Ping berkala (setiap 30 detik)
    if (millis() - lastPing > 30000) {
        lastPing = millis();
        syncSettings(); // Ambil info operator terbaru dari dashboard
        processQueue(); // Kirim data antrean jika ada
        
        if (WiFi.status() == WL_CONNECTED) {
            WiFiClient client; HTTPClient http;
            http.begin(client, String(BASE_URL) + "/api/iot/ping?token=" + String(DEVICE_TOKEN));
            http.GET(); http.end();
        }
    }
}
