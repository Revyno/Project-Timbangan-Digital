/**
 * ====================================================================
 * PROJECT: Universal WiFi Scale System (Robust Edition)
 * DEVICE:  ESP8266 + RS232-TTL + LCD 16x02
 * FEATURES: WDT, Auto-Reconnect, Offline Queue, Retry Logic
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

// ============================================================
// === [CONFIG] LOKASI & TOKEN (SESUAIKAN TIAP FOLDER) ===
// ============================================================
#define LOCATION_NAME "PASURUAN - FORM"
#define DEVICE_TOKEN  "FG-PASURUAN-001"
#define BASE_URL      "http://192.168.1.100:8000" // Alamat Server Laravel

// ============================================================
// === [HARDWARE PINS] ===
// ============================================================
#define I2C_SDA_PIN 4  // D2
#define I2C_SCL_PIN 5  // D1
#define RX_PIN_SCALE 14 // D5 (GPIO14) -> TX Timbangan
#define TX_PIN_SCALE 12 // D6 (GPIO12) -> RX Timbangan
#define BTN_MENU 0     // D3 (GPIO0)
#define BTN_SEND 2     // D4 (GPIO2)

// ============================================================
// === [CONSTANTS] ===
// ============================================================
const char* WIFI_SSID_1 = "LSI";
const char* WIFI_PASS_1 = "Ladang593";
const char* WIFI_SSID_2 = "PT LSI";
const char* WIFI_PASS_2 = "Ladang593";

const int SCALE_BAUDRATE = 9600;
const float MIN_VALID_WEIGHT = 0.05; // 50 gram
const int MAX_QUEUE_SIZE = 50;       // Simpan max 50 data saat offline
const char* QUEUE_FILE = "/queue.dat";

// ============================================================
// === [GLOBALS] ===
// ============================================================
LiquidCrystal_I2C lcd(0x27, 16, 2);
ESP8266WiFiMulti wifiMulti;
SoftwareSerial scaleSerial(RX_PIN_SCALE, TX_PIN_SCALE);

float currentWeight = 0.0;
String serialBuffer = "";
int lcdPage = 1;
unsigned long lastLcdUpdate = 0;
unsigned long lastPing = 0;
bool isSending = false;

// Queue Structure
struct WeighData {
    float weight;
    unsigned long timestamp;
};
WeighData offlineQueue[MAX_QUEUE_SIZE];
int queueCount = 0;

// ============================================================
// === [SYSTEM HELPERS] ===
// ============================================================

void resetWatchdog() {
    ESP.wdtFeed();
}

void lcdPrintCenter(String text, int row) {
    int len = text.length();
    int pos = (16 - len) / 2;
    if (pos < 0) pos = 0;
    lcd.setCursor(pos, row);
    lcd.print(text);
}

// ============================================================
// === [NETWORK & STORAGE] ===
// ============================================================

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
        saveQueue();
        Serial.println("Data added to offline queue.");
    } else {
        Serial.println("Queue Full! Overwriting oldest.");
        for(int i=0; i<MAX_QUEUE_SIZE-1; i++) offlineQueue[i] = offlineQueue[i+1];
        offlineQueue[MAX_QUEUE_SIZE-1].weight = w;
        offlineQueue[MAX_QUEUE_SIZE-1].timestamp = millis();
        saveQueue();
    }
}

bool sendData(float w) {
    if (WiFi.status() != WL_CONNECTED) return false;

    WiFiClient client;
    HTTPClient http;
    
    String url = String(BASE_URL) + "/api/iot/weight";
    http.begin(client, url);
    http.addHeader("Content-Type", "application/x-www-form-urlencoded");
    
    String payload = "token=" + String(DEVICE_TOKEN) + "&berat=" + String(w, 3);
    int httpCode = http.POST(payload);
    
    bool success = (httpCode == HTTP_CODE_OK || httpCode == 201);
    if (!success) {
        Serial.printf("[HTTP] POST failed, error: %s\n", http.errorToString(httpCode).c_str());
    }
    
    http.end();
    return success;
}

void processQueue() {
    if (queueCount == 0 || WiFi.status() != WL_CONNECTED || isSending) return;
    
    isSending = true;
    Serial.printf("Processing Queue: %d items\n", queueCount);
    
    while (queueCount > 0 && WiFi.status() == WL_CONNECTED) {
        resetWatchdog();
        if (sendData(offlineQueue[0].weight)) {
            // Shift queue
            for (int i = 0; i < queueCount - 1; i++) {
                offlineQueue[i] = offlineQueue[i + 1];
            }
            queueCount--;
            saveQueue();
            delay(500); // Small gap between sends
        } else {
            break; // Stop if send fails
        }
    }
    isSending = false;
}

void handleWiFi() {
    if (wifiMulti.run() != WL_CONNECTED) {
        static unsigned long lastAttempt = 0;
        if (millis() - lastAttempt > 10000) {
            Serial.println("WiFi Disconnected. Retrying...");
            lastAttempt = millis();
        }
    }
}

// ============================================================
// === [CORE LOGIC] ===
// ============================================================

void setup() {
    Serial.begin(115200);
    
    // Watchdog Setup
    ESP.wdtEnable(WDTO_8S);
    
    // Hardware Setup
    pinMode(BTN_MENU, INPUT_PULLUP);
    pinMode(BTN_SEND, INPUT_PULLUP);
    scaleSerial.begin(SCALE_BAUDRATE);
    
    // LCD Setup
    Wire.begin(I2C_SDA_PIN, I2C_SCL_PIN);
    lcd.init();
    lcd.backlight();
    lcd.clear();
    lcdPrintCenter("LSI SCALE V7.0", 0);
    lcdPrintCenter("Initializing...", 1);
    
    // Filesystem
    if (!LittleFS.begin()) {
        Serial.println("LittleFS Mount Failed");
    } else {
        loadQueue();
    }
    
    // WiFi Setup
    WiFi.mode(WIFI_STA);
    WiFi.setAutoReconnect(true);
    wifiMulti.addAP(WIFI_SSID_1, WIFI_PASS_1);
    wifiMulti.addAP(WIFI_SSID_2, WIFI_PASS_2);
    
    delay(1000);
}

void loop() {
    resetWatchdog();
    handleWiFi();
    
    // 1. Read Scale Serial
    while (scaleSerial.available() > 0) {
        char c = scaleSerial.read();
        if (c == '+' || c == '-') {
            serialBuffer = "";
            serialBuffer += c;
        } else if (serialBuffer.length() > 0 && isDigit(c)) {
            serialBuffer += c;
            if (serialBuffer.length() == 7) {
                float val = serialBuffer.substring(1).toFloat() / 10.0;
                currentWeight = val;
                serialBuffer = "";
            }
        }
    }
    
    // 2. Handle Buttons
    if (digitalRead(BTN_SEND) == LOW) {
        delay(50); // Debounce
        if (digitalRead(BTN_SEND) == LOW) {
            if (currentWeight >= MIN_VALID_WEIGHT) {
                lcd.clear();
                lcdPrintCenter("SENDING...", 0);
                if (sendData(currentWeight)) {
                    lcdPrintCenter("SUCCESS!", 1);
                } else {
                    lcdPrintCenter("OFFLINE SAVED", 1);
                    addToQueue(currentWeight);
                }
                delay(1500);
                lcd.clear();
            } else {
                lcd.clear();
                lcdPrintCenter("UNDER WEIGHT", 0);
                delay(1000);
                lcd.clear();
            }
            while(digitalRead(BTN_SEND) == LOW) resetWatchdog(); // Wait for release
        }
    }
    
    if (digitalRead(BTN_MENU) == LOW) {
        delay(50);
        if (digitalRead(BTN_MENU) == LOW) {
            lcdPage++;
            if (lcdPage > 3) lcdPage = 1;
            lcd.clear();
            while(digitalRead(BTN_MENU) == LOW) resetWatchdog();
        }
    }
    
    // 3. Update LCD
    if (millis() - lastLcdUpdate > 300) {
        lastLcdUpdate = millis();
        switch(lcdPage) {
            case 1:
                lcd.setCursor(0, 0); lcd.print(LOCATION_NAME);
                lcd.setCursor(0, 1); lcd.print("W: " + String(currentWeight, 2) + " kg   ");
                break;
            case 2:
                lcd.setCursor(0, 0); lcd.print("STATUS NETWORK");
                lcd.setCursor(0, 1); 
                if (WiFi.status() == WL_CONNECTED) lcd.print("WiFi: " + WiFi.SSID().substring(0, 10));
                else lcd.print("WiFi: OFFLINE   ");
                break;
            case 3:
                lcd.setCursor(0, 0); lcd.print("OFFLINE QUEUE");
                lcd.setCursor(0, 1); lcd.print("Count: " + String(queueCount) + " items   ");
                break;
        }
        
        // Show status icons
        lcd.setCursor(15, 0);
        if (WiFi.status() == WL_CONNECTED) lcd.print("*"); else lcd.print("!");
    }
    
    // 4. Periodical tasks
    if (millis() - lastPing > 30000) { // Every 30s
        lastPing = millis();
        processQueue();
        
        // Send Ping to server
        if (WiFi.status() == WL_CONNECTED) {
            WiFiClient client;
            HTTPClient http;
            http.begin(client, String(BASE_URL) + "/api/iot/ping?token=" + String(DEVICE_TOKEN));
            http.GET();
            http.end();
        }
    }
}
