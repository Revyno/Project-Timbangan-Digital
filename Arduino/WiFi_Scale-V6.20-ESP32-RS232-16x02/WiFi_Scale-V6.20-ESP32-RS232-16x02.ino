/**
 * ====================================================================
 * PROJECT: WiFi Scale System (Sonic XK-3190 A1+ Custom Pattern)
 * DEVICE:  ESP32 (DevKit V1 / DOIT) + RS232-TTL + LCD 16x02
 * VERSION: V6.20 (ESP32 Port)
 * ====================================================================
 */

#include <WiFi.h>
#include <WiFiMulti.h>
#include <HTTPClient.h>
#include <NetworkClientSecure.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include "time.h"

// ============================================================
// === [PART 1] KONFIGURASI SISTEM ===
// ============================================================

const String GScriptID ="AKfycbxN5sHd_WubwGR2h7Wo-jOP51kkzjuTAz-Oln5hPqGzpa60q_UHoDSK2WlR48KcMENT"; 

// WIFI CREDENTIALS
const char* WIFI_SSID_1 = "LSI";
const char* WIFI_PASS_1 = "Ladang593";
const char* WIFI_SSID_2 = "PT LSI";
const char* WIFI_PASS_2 = "Ladang593";
const char* WIFI_SSID_3 = "Ladang Lima"; 
const char* WIFI_PASS_3 = "Ladang593"; 

// PIN MAPPING UNTUK ESP32
#define I2C_SDA_PIN 21 
#define I2C_SCL_PIN 22 

// ESP32 memiliki 3 Hardware Serial. Kita gunakan Serial2 untuk Timbangan.
// Default Serial2: RX=16, TX=17
#define RX_PIN_SCALE 16 
#define TX_PIN_SCALE 17 

// Tombol
#define BTN_MENU 14  // GPIO 14
#define BTN_SEND 12  // GPIO 12

const int SCALE_BAUDRATE = 9600; 
const float WEIGHT_DIVIDER = 10.0; 
const float MIN_VALID_WEIGHT_KG = 0.05; 

// ============================================================
// === [PART 2] OBJEK & VARIABEL ===
// ============================================================

LiquidCrystal_I2C lcd(0x27, 16, 2); 
WiFiMulti wifiMulti;
// Gunakan Hardware Serial2 (Serial2 sudah terdefinisi di ESP32)
#define scaleSerial Serial2

float currentWeight = 0.0; 
int todayCount = 0;             
float todayTotalWeight = 0.0; 

String lcdProduk = "Menunggu Sync"; 
String lcdAsal = "-";
String lcdPlat = "-";
String lcdSopir = "-";
String lcdJenis = "-";

int lcdPage = 1; 
unsigned long lastLcdUpdate = 0;
String serialBuffer = "";
unsigned long lastDebounceTime = 0;

// Function Prototypes
void showPage_Main();
void handleButtons();
void connectToWiFi();
void syncTime(); 
void getSettingsFromGoogle();
void sendWeightToGoogle();
float parseSonicFixed(String data);
String formatWeightID(float value, int decimals);
void showSpinner(int col, int row); 

// ============================================================
// === [PART 3] SETUP ===
// ============================================================
void setup() {
    Serial.begin(115200); 
    
    // Inisialisasi Hardware Serial 2 untuk Timbangan
    scaleSerial.begin(SCALE_BAUDRATE, SERIAL_8N1, RX_PIN_SCALE, TX_PIN_SCALE);
    
    pinMode(BTN_MENU, INPUT_PULLUP);
    pinMode(BTN_SEND, INPUT_PULLUP); 
    
    Wire.begin(I2C_SDA_PIN, I2C_SCL_PIN);
    lcd.init();
    lcd.backlight();
    lcd.clear();
    
    lcd.setCursor(0, 0); lcd.print("SYSTEM LSI V6.20");
    lcd.setCursor(0, 1); lcd.print("Booting ESP32...");
    
    delay(1000);

    WiFi.mode(WIFI_STA);
    wifiMulti.addAP(WIFI_SSID_1, WIFI_PASS_1);
    wifiMulti.addAP(WIFI_SSID_2, WIFI_PASS_2);
    wifiMulti.addAP(WIFI_SSID_3, WIFI_PASS_3);
    
    connectToWiFi(); 
    syncTime(); 
    getSettingsFromGoogle(); 
    lcd.clear(); 
}

void loop() {
    // Membaca Data dari Hardware Serial2
    while (scaleSerial.available() > 0) {
        char c = scaleSerial.read();
        if (c == '+' || c == '-') {
            serialBuffer = ""; 
            serialBuffer += c; 
        } 
        else if (serialBuffer.length() > 0 && isDigit(c)) {
            serialBuffer += c;
            if (serialBuffer.length() == 7) {
                float val = parseSonicFixed(serialBuffer);
                if (val != -1.0) currentWeight = val;
                serialBuffer = ""; 
            }
        }
    }

    handleButtons();

    if (millis() - lastLcdUpdate >= 250) {
        showPage_Main();
        lastLcdUpdate = millis();
    }
    // ESP32 secara otomatis menangani background tasks, delay(1) opsional
    delay(1); 
}

// ============================================================
// === [PART 4] NETWORK & TIME SYNC ===
// ============================================================

void syncTime() {
    lcd.clear();
    lcd.setCursor(0,0); lcd.print("Syncing Time...");
    configTime(7 * 3600, 0, "id.pool.ntp.org", "asia.pool.ntp.org", "time.google.com");
    
    struct tm timeinfo;
    int timeout = 0;
    while (!getLocalTime(&timeinfo) && timeout < 20) { 
        delay(500);
        timeout++;
        showSpinner(15, 0);
    }
    
    if (timeout < 20) {
        Serial.println("Time synced");
    } else {
        Serial.println("Time Sync Failed");
    }
}

void getSettingsFromGoogle() {
    if(wifiMulti.run() != WL_CONNECTED) { 
        connectToWiFi(); 
        if(WiFi.status() != WL_CONNECTED) return; 
    }

    lcd.clear();
    lcd.setCursor(0,0); lcd.print("SYNCING DATA...");
    
    int code = -1;
    String payload = "";
    
    for (int retry = 1; retry <= 3; retry++) {
        NetworkClientSecure *client = new NetworkClientSecure;
        client->setInsecure(); 
        
        HTTPClient http;
        http.setTimeout(15000);
        
        String url = "https://script.google.com/macros/s/" + GScriptID + "/exec?action=get_setting";

        if (http.begin(*client, url)) {
            http.addHeader("User-Agent", "ESP32-Scale-System");
            http.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS); 
            
            code = http.GET();
            if (code == 200) {
                payload = http.getString();
                http.end();
                delete client;
                break;
            }
            http.end();
        }
        delete client;
        if (retry < 3 && code != 200) {
            lcd.setCursor(0, 1);
            lcd.print(("Retry " + String(retry) + "/3...").substring(0,16));
            delay(2000); 
        }
    }
    
    // Parsing Logic (Sama dengan versi sebelumnya)
    if (code == 200) {
        // ... (Logika pemisahan string '#' tetap sama)
        int h1 = payload.indexOf('#');
        if (h1 > 0) {
            lcdProduk = payload.substring(0, h1);
            int h2 = payload.indexOf('#', h1 + 1);
            if (h2 > 0) {
                todayCount = payload.substring(h1 + 1, h2).toInt();
                int h3 = payload.indexOf('#', h2 + 1);
                if (h3 > 0) {
                    todayTotalWeight = payload.substring(h2 + 1, h3).toFloat();
                    int h4 = payload.indexOf('#', h3 + 1);
                    if (h4 > 0) {
                        int h5 = payload.indexOf('#', h4 + 1);
                        if (h5 > 0) {
                             lcdAsal = payload.substring(h4 + 1, h5);
                             int h6 = payload.indexOf('#', h5 + 1);
                             if (h6 > 0) {
                                 lcdPlat = payload.substring(h5 + 1, h6);
                                 int h7 = payload.indexOf('#', h6 + 1);
                                 if (h7 > 0) {
                                     lcdSopir = payload.substring(h6 + 1, h7);
                                     int h8 = payload.indexOf('#', h7 + 1);
                                     if (h8 > 0) lcdJenis = payload.substring(h7 + 1, h8);
                                     else lcdJenis = payload.substring(h7 + 1);
                                 } else lcdSopir = payload.substring(h6 + 1);
                             }
                        }
                    }
                }
            }
            lcd.clear(); lcd.print("SYNC BERHASIL!"); 
        }
    } else {
        lcd.clear(); lcd.print("SYNC GAGAL!");
    }
    delay(1500);
}

void connectToWiFi() {
    lcd.clear(); lcd.setCursor(0, 0); lcd.print("Mencari WiFi...");
    unsigned long start = millis();
    while (wifiMulti.run() != WL_CONNECTED && (millis() - start < 15000)) {
        showSpinner(15, 0);
        delay(500);
    }
    lcd.clear();
    if (WiFi.status() == WL_CONNECTED) { 
        lcd.print("CONNECTED!"); 
        lcd.setCursor(0,1); lcd.print(WiFi.SSID()); 
    } else { 
        lcd.print("CONN FAILED!"); 
    }
    delay(1500);
}

// ============================================================
// === [PART 5] UI & INTERACTION ===
// ============================================================

void handleButtons() {
    // Tombol Send
    if (digitalRead(BTN_SEND) == LOW) {
        unsigned long pressStart = millis();
        bool longPressActioned = false;
        while (digitalRead(BTN_SEND) == LOW) {
            unsigned long duration = millis() - pressStart;
            if (duration > 500 && !longPressActioned) {
                int bars = map(duration, 500, 2000, 0, 16);
                lcd.setCursor(0, 1); for(int i=0; i<min(bars,16); i++) lcd.print(">"); 
            }
            if (duration > 2000 && !longPressActioned) { connectToWiFi(); syncTime(); longPressActioned = true; }
            delay(50); 
        }
        if (!longPressActioned && (millis() - lastDebounceTime) > 300) {
            if (currentWeight > MIN_VALID_WEIGHT_KG) sendWeightToGoogle();
            else { lcd.clear(); lcd.print("!BERAT KOSONG!"); delay(1000); }
            lastDebounceTime = millis();
        }
    }

    // Tombol Menu
    if (digitalRead(BTN_MENU) == LOW) {
        unsigned long pressStart = millis();
        bool longPressActioned = false;
        while (digitalRead(BTN_MENU) == LOW) {
            unsigned long duration = millis() - pressStart;
            if (duration > 2000 && !longPressActioned) { getSettingsFromGoogle(); longPressActioned = true; }
            delay(50); 
        }
        if (!longPressActioned && (millis() - lastDebounceTime) > 300) {
            lcdPage++;
            if (lcdPage > 6) lcdPage = 1;
            lcd.clear(); 
            lastDebounceTime = millis();
        }
    }
}

void showPage_Main() {
    switch(lcdPage) {
        case 1:
            lcd.setCursor(0,0); lcd.print(("Tot:" + formatWeightID(todayTotalWeight, 1) + " C:" + String(todayCount) + "    ").substring(0,16));
            lcd.setCursor(0,1); lcd.print(("WT: " + formatWeightID(currentWeight, 2) + " Kg       ").substring(0,16));
            break;
        case 2:
            lcd.setCursor(0,0); lcd.print((lcdProduk + "                ").substring(0,16));
            lcd.setCursor(0,1); lcd.print(("Asal: " + lcdAsal + "          ").substring(0,16));
            break;
        case 3:
            lcd.setCursor(0,0); lcd.print(("Drv: " + lcdSopir + "          ").substring(0,16));
            lcd.setCursor(0,1); lcd.print(("Plat: " + lcdPlat + "          ").substring(0,16));
            break;
        case 4:
            lcd.setCursor(0,0); lcd.print(("J: " + lcdJenis + "            ").substring(0,16));
            lcd.setCursor(0,1); lcd.print(((WiFi.status() == WL_CONNECTED ? WiFi.SSID() : "No WiFi") + "                ").substring(0,16));
            break;
        case 5:
            lcd.setCursor(0,0); lcd.print(((WiFi.status() == WL_CONNECTED ? WiFi.localIP().toString() : "0.0.0.0") + "                ").substring(0,16));
            lcd.setCursor(0,1); lcd.print(("Sig: " + String(WiFi.RSSI()) + " dBm      ").substring(0,16));
            break;
        case 6:
            lcd.setCursor(0,0); lcd.print("ID Device (MAC):");
            String mac = WiFi.macAddress();
            mac.replace(":", "");
            lcd.setCursor(0,1); lcd.print((mac + "                ").substring(0,16));
            break;
    }
}

// ============================================================
// === [PART 6] HELPER FUNCTIONS ===
// ============================================================

void sendWeightToGoogle() {
    lcd.clear(); lcd.setCursor(0,0); lcd.print("SENDING...");
    if(wifiMulti.run() != WL_CONNECTED) return;
    
    int code = -1;
    String res = "";
    
    for (int retry = 1; retry <= 2; retry++) {
        NetworkClientSecure *client = new NetworkClientSecure;
        client->setInsecure();

        HTTPClient http;
        http.setTimeout(20000);
        String url = "https://script.google.com/macros/s/" + GScriptID + "/exec?value=" + String(currentWeight, 2);
        
        if(http.begin(*client, url)) {
            http.addHeader("User-Agent", "ESP32-Scale-System");
            http.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS);
            code = http.GET();
            if(code == 200) {
                res = http.getString();
                http.end();
                delete client;
                break;
            }
            http.end();
        }
        delete client;
        if (code != 200) delay(1000);
    }
    
    if(code == 200 && res.indexOf("Sukses") >= 0) {
        lcd.clear(); lcd.print("TERSIMPAN!");
        int idxNo = res.indexOf("No:");
        int idxT = res.indexOf("T:");
        if (idxNo >= 0) {
            int endNo = (idxT > 0) ? idxT : res.length(); 
            todayCount = res.substring(idxNo + 3, endNo).toInt();
        }
        if (idxT >= 0) todayTotalWeight = res.substring(idxT + 2).toFloat();
    } else {
        lcd.clear(); lcd.print("ERR:" + String(code));
    }
    delay(1500); lcd.clear();
}

void showSpinner(int col, int row) {
    static int spinIdx = 0;
    const char spinChars[] = {'|', '/', '-', '\\'}; 
    lcd.setCursor(col, row); lcd.print(spinChars[spinIdx]);
    spinIdx = (spinIdx + 1) % 4;
}

float parseSonicFixed(String data) {
    if (data.length() < 7) return -1.0;
    String numStr = data.substring(1, 7);
    for (int i = 0; i < numStr.length(); i++) { if (!isDigit(numStr.charAt(i))) return -1.0; }
    return numStr.toFloat() / WEIGHT_DIVIDER; 
}

String formatWeightID(float value, int decimals) {
    String s = String(value, decimals);
    s.replace('.', ',');
    return s;
}