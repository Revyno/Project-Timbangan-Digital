/**
 * ====================================================================
 * PROJECT: WiFi Scale System (SURABAYA - CS NOODLE)
 * DEVICE:  ESP8266 + RS232-TTL + LCD 16x02
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

#define LOCATION_NAME "SBY - CS NOODLE"
#define DEVICE_TOKEN  "CS-NOODLE-001"
#define BASE_URL      "http://192.168.1.100:8000" 

#define I2C_SDA_PIN 4  // D2
#define I2C_SCL_PIN 5  // D1
#define RX_PIN_SCALE 14 // D5 (GPIO14)
#define TX_PIN_SCALE 12 // D6 (GPIO12)
#define BTN_MENU 0     // D3 (GPIO0)
#define BTN_SEND 2     // D4 (GPIO2)

const char* WIFI_SSID_1 = "LSI";
const char* WIFI_PASS_1 = "Ladang593";
const char* WIFI_SSID_2 = "PT LSI";
const char* WIFI_PASS_2 = "Ladang593";

const int SCALE_BAUDRATE = 9600;
const float MIN_VALID_WEIGHT = 0.05;
const int MAX_QUEUE_SIZE = 50;
const char* QUEUE_FILE = "/queue.dat";

LiquidCrystal_I2C lcd(0x27, 16, 2);
ESP8266WiFiMulti wifiMulti;
SoftwareSerial scaleSerial(RX_PIN_SCALE, TX_PIN_SCALE);

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

void resetWatchdog() { ESP.wdtFeed(); }
void lcdPrintCenter(String text, int row) {
    int len = text.length();
    int pos = (16 - len) / 2;
    if (pos < 0) pos = 0;
    lcd.setCursor(pos, row);
    lcd.print(text);
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
    int httpCode = http.POST("token=" + String(DEVICE_TOKEN) + "&berat=" + String(w, 3));
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

void setup() {
    Serial.begin(115200);
    ESP.wdtEnable(WDTO_8S);
    pinMode(BTN_MENU, INPUT_PULLUP);
    pinMode(BTN_SEND, INPUT_PULLUP);
    scaleSerial.begin(SCALE_BAUDRATE);
    Wire.begin(I2C_SDA_PIN, I2C_SCL_PIN);
    lcd.init();
    lcd.backlight();
    LittleFS.begin();
    loadQueue();
    WiFi.mode(WIFI_STA);
    WiFi.setAutoReconnect(true);
    wifiMulti.addAP(WIFI_SSID_1, WIFI_PASS_1);
    wifiMulti.addAP(WIFI_SSID_2, WIFI_PASS_2);
}

void loop() {
    resetWatchdog();
    wifiMulti.run();
    
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
    
    if (digitalRead(BTN_SEND) == LOW) {
        delay(50);
        if (digitalRead(BTN_SEND) == LOW) {
            if (currentWeight >= MIN_VALID_WEIGHT) {
                lcd.clear(); lcdPrintCenter("SENDING...", 0);
                if (sendData(currentWeight)) lcdPrintCenter("SUCCESS!", 1);
                else { lcdPrintCenter("OFFLINE SAVED", 1); addToQueue(currentWeight); }
                delay(1500); lcd.clear();
            }
            while(digitalRead(BTN_SEND) == LOW) resetWatchdog();
        }
    }
    
    if (digitalRead(BTN_MENU) == LOW) {
        delay(50);
        if (digitalRead(BTN_MENU) == LOW) {
            lcdPage++; if (lcdPage > 3) lcdPage = 1;
            lcd.clear();
            while(digitalRead(BTN_MENU) == LOW) resetWatchdog();
        }
    }
    
    if (millis() - lastLcdUpdate > 300) {
        lastLcdUpdate = millis();
        if (lcdPage == 1) {
            lcd.setCursor(0,0); lcd.print(LOCATION_NAME);
            lcd.setCursor(0,1); lcd.print("W: " + String(currentWeight, 2) + " kg   ");
        } else if (lcdPage == 2) {
            lcd.setCursor(0,0); lcd.print("STATUS NETWORK");
            lcd.setCursor(0,1); lcd.print(WiFi.status() == WL_CONNECTED ? "WiFi: ONLINE" : "WiFi: OFFLINE");
        } else {
            lcd.setCursor(0,0); lcd.print("OFFLINE QUEUE");
            lcd.setCursor(0,1); lcd.print("Count: " + String(queueCount) + " items");
        }
        lcd.setCursor(15, 0); lcd.print(WiFi.status() == WL_CONNECTED ? "*" : "!");
    }
    
    if (millis() - lastPing > 30000) {
        lastPing = millis();
        processQueue();
        if (WiFi.status() == WL_CONNECTED) {
            WiFiClient client; HTTPClient http;
            http.begin(client, String(BASE_URL) + "/api/iot/ping?token=" + String(DEVICE_TOKEN));
            http.GET(); http.end();
        }
    }
}
