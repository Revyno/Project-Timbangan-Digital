/**
 * ====================================================================
 * PROJECT: WiFi Scale System V6.6 (Finished Goods Pasuruan)
 * DEVICE:  ROBODYN D1 R2 (ESP8266)
 * AUTHOR:  [ProTechD Ladang Sehat Indonesia]
 * VERSION: V6.6 (FAST SEND & OLED UI)
 * ====================================================================
 */

#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecure.h>
#include <Wire.h>
#include <SoftwareSerial.h>
#include <LiquidCrystal_I2C.h>

// ============================================================
// === [PART 1] KONFIGURASI SISTEM ===
// ============================================================

const String GScriptID = "AKfycbyYfSoKA16zIIIypohtkEcvQ_NAJkDpod8cFRAUiUsSQ32ajWLotgtbiodv5rvvexU";

// --- WIFI CREDENTIALS ---
// const char* WIFI_SSID_1 = "LSI";
// const char* WIFI_PASS_1 = "Ladang593";
const char* WIFI_SSID_1 = "Intranet";
const char* WIFI_PASS_1 = "aaaabbbb";
const char* WIFI_SSID_2 = "LSI";
const char* WIFI_PASS_2 = "Ladang593";
const char* WIFI_SSID_3 = "abc";
const char* WIFI_PASS_3 = "aaaabbbb";

// --- PIN DEFINITIONS (WEMOS D1 R2) ---
#define I2C_SDA D2          // GPIO 4
#define I2C_SCL D1          // GPIO 5
#define RX_PIN_SCALE D7     // GPIO 14 (Ke TX Timbangan)
#define TX_PIN_SCALE D8     // Tidak dipakai

// Trigger & Buttons
#define PIN_PROXIMITY D4    // GPIO 12
#define PIN_BTN_MANUAL D5    // GPIO 0
#define PIN_BTN_MENU   D3    // GPIO 13
#define PIN_BTN_TARE   D6    // GPIO Tambahan untuk tombol TARE

// Settings
const int SCALE_BAUDRATE = 9600;
const int LCD_UPDATE_INTERVAL = 200;
const float MIN_VALID_WEIGHT = 0.5; 

// Timing Logic
const unsigned long TRIGGER_DELAY = 800; 
const unsigned long HTTP_TIMEOUT = 10000; 

// ============================================================
// === [PART 2] OBJEK & VARIABEL ===
// ============================================================

LiquidCrystal_I2C lcd(0x27, 20, 4); // Alamat I2C umumnya 0x27 atau 0x3F
ESP8266WiFiMulti wifiMulti;
SoftwareSerial scaleSerial(RX_PIN_SCALE, -1);

// Variabel Logika
float rawWeight = 0.0;
float tareOffset = 0.0;
float currentWeight = 0.0;
bool objectDetected = false;
bool dataSentForThisObject = false;
unsigned long proximityStartTime = 0;
unsigned long resetWeightTimer = 0;

// Data Penimbangan
String lcdProduk = "Belum Sync";
String lcdShift = "-";
String lcdPetugas = "-";
String currentKodeProduksi = "-";
String currentTglExpired = "-";
unsigned int lastLoggedCounter = 0;
String lastDataHistory[3] = {"-", "-", "-"};

// Navigasi UI
int currentPage = 0; 
unsigned long lastLcdUpdate = 0;
bool forceLcdClear = true; // Tambahan: Bendera untuk memaksa hapus layar
int lastPage = -1;         // Tambahan: Menyimpan status halaman terakhir
int lastMenuHold = -1;     // Tambahan: Menyimpan status tombol terakhir
int lastManualHold = -1;   // Tambahan: Menyimpan status tombol terakhir

// Status Tombol
unsigned long btnMenuStart = 0;
bool btnMenuActive = false;
int btnMenuHoldState = 0; 

unsigned long btnManualStart = 0;
bool btnManualActive = false;
int btnManualHoldState = 0; 

bool btnTareActive = false; // Status tombol Tare

// Prototypes
void connectToWiFi();
void getSettingsFromGoogle();
void sendWeightToGoogle();
void deleteLastData();
void drawProgressBar(int percent);
String formatWeight(float weight);
void updateLastDataHistory(float weight, unsigned int counter);

// UI Pages
void showPage1_Main();
void showPage2_Info();
void showPage3_Network();
void showPage4_History();

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
    lcd.setCursor(0, 0);
    lcd.print("SYSTEM V6.6 LCD");
    lcd.setCursor(0, 1);
    lcd.print("FG1 PASURUAN (8266)");
    lcd.setCursor(0, 2);
    lcd.print("Initializing...");
    
    WiFi.mode(WIFI_STA);
    wifiMulti.addAP(WIFI_SSID_1, WIFI_PASS_1);
    wifiMulti.addAP(WIFI_SSID_2, WIFI_PASS_2);
    wifiMulti.addAP(WIFI_SSID_3, WIFI_PASS_3);

    connectToWiFi();
    getSettingsFromGoogle();

    lcd.clear();
    lcd.setCursor(0, 1);
    lcd.print("       READY!       ");
    delay(1000);
}

// ============================================================
// === [PART 4] MAIN LOOP ===
// ============================================================
void loop() {
    // 1. READ SCALE (SoftwareSerial)
    while (scaleSerial.available() > 0) {
        char c = scaleSerial.read();
        static String buffer = "";
        if (c == '\n') {
            buffer.trim();
            String cleanStr = "";
            bool decimalFound = false;
            for (int i = 0; i < (int)buffer.length(); i++) {
                char x = buffer.charAt(i);
                if (isDigit(x) || x == '-') cleanStr += x;
                else if (x == '.' && !decimalFound) { cleanStr += x; decimalFound = true; }
            }
            if (cleanStr.length() > 0 && cleanStr != "." && cleanStr != "-") {
                rawWeight = cleanStr.toFloat();
                currentWeight = rawWeight - tareOffset;
            }
            buffer = "";
        } else {
            if (buffer.length() < 20) buffer += c;
        }
    }

    // 2. RESET LOGIC
    if (currentWeight < (MIN_VALID_WEIGHT / 2)) {
        if (resetWeightTimer == 0) resetWeightTimer = millis();
        if (millis() - resetWeightTimer > 1000) {
            dataSentForThisObject = false;
            resetWeightTimer = 0;
            objectDetected = false;
            proximityStartTime = 0;
        }
    } else {
        resetWeightTimer = 0;
    }

    // 3. TRIGGER LOGIC
    bool readyToSend = (!dataSentForThisObject && currentWeight >= MIN_VALID_WEIGHT && lcdProduk != "Belum Sync");

    if (readyToSend) {
        if (digitalRead(PIN_PROXIMITY) == LOW) {
            if (!objectDetected) {
                proximityStartTime = millis();
                objectDetected = true;
            } else {
                if (millis() - proximityStartTime >= TRIGGER_DELAY) sendWeightToGoogle();
            }
        } else {
            objectDetected = false;
            proximityStartTime = 0;
        }

        if (digitalRead(PIN_BTN_MANUAL) == LOW) {
             delay(50);
             if (digitalRead(PIN_BTN_MANUAL) == LOW && !btnManualActive) {
                 sendWeightToGoogle();
                 while(digitalRead(PIN_BTN_MANUAL) == LOW) yield();
             }
        }
    }

    // 4. BUTTONS
    unsigned long now = millis();
    if (digitalRead(PIN_BTN_MANUAL) == LOW) {
        if (!btnManualActive) { btnManualActive = true; btnManualStart = now; }
        long duration = now - btnManualStart;
        if (duration > 10000) btnManualHoldState = 2;
        else if (duration > 5000) btnManualHoldState = 1;
    } else if (btnManualActive) {
        long duration = now - btnManualStart;
        btnManualActive = false;
        if (duration > 10000) deleteLastData();
        else if (duration > 5000) dataSentForThisObject = false;
        btnManualHoldState = 0;
    }

    if (digitalRead(PIN_BTN_MENU) == LOW) {
        if (!btnMenuActive) { btnMenuActive = true; btnMenuStart = now; }
        long duration = now - btnMenuStart;
        if (duration > 10000) btnMenuHoldState = 2;
        else if (duration > 5000) btnMenuHoldState = 1;
    } else if (btnMenuActive) {
        long duration = now - btnMenuStart;
        btnMenuActive = false;
        if (duration > 10000) connectToWiFi();
        else if (duration > 5000) getSettingsFromGoogle();
        else {
            currentPage++;
            if (currentPage > 3) currentPage = 0; 
        }
        btnMenuHoldState = 0;
    }

    // --- TARE BUTTON LOGIC ---
    if (digitalRead(PIN_BTN_TARE) == LOW) {
        if (!btnTareActive) {
            delay(50); // Debounce sederhana
            if (digitalRead(PIN_BTN_TARE) == LOW) {
                btnTareActive = true;
                tareOffset = rawWeight;     // Simpan berat saat ini sebagai offset
                currentWeight = 0.0;        // Langsung nol-kan nilai di layar
                
                // Beri indikasi di layar bahwa Tare berhasil
                lcd.clear();
                lcd.setCursor(0, 1);
                lcd.print("     TARE OK!       ");
                delay(500);                 // Tahan sebentar agar terbaca
                forceLcdClear = true;       // Paksa gambar ulang layar
            }
        }
    } else {
        btnTareActive = false;
    }

    // 5. UPDATE DISPLAY
    if (millis() - lastLcdUpdate > LCD_UPDATE_INTERVAL) {
        // Cek apakah ada perubahan status layar
        if (forceLcdClear || currentPage != lastPage || btnMenuHoldState != lastMenuHold || btnManualHoldState != lastManualHold) {
            lcd.clear(); // Hanya clear saat berpindah menu/halaman
            forceLcdClear = false;
            lastPage = currentPage;
            lastMenuHold = btnMenuHoldState;
            lastManualHold = btnManualHoldState;
        }
        
        if (btnMenuHoldState == 1) {
            lcd.setCursor(0, 1); lcd.print("    RELEASE TO:     ");
            lcd.setCursor(0, 2); lcd.print("     SYNC DATA      ");
        } else if (btnMenuHoldState == 2) {
            lcd.setCursor(0, 1); lcd.print("    RELEASE TO:     ");
            lcd.setCursor(0, 2); lcd.print("     RESET WIFI     ");
        } else if (btnManualHoldState == 1) {
            lcd.setCursor(0, 1); lcd.print("    RELEASE TO:     ");
            lcd.setCursor(0, 2); lcd.print("     RST STATUS     ");
        } else if (btnManualHoldState == 2) {
            lcd.setCursor(0, 1); lcd.print("    RELEASE TO:     ");
            lcd.setCursor(0, 2); lcd.print("    DELETE DATA     ");
        } else {
            if (currentPage == 0) showPage1_Main();
            else if (currentPage == 1) showPage2_Info();
            else if (currentPage == 2) showPage3_Network();
            else if (currentPage == 3) showPage4_History();
        }
        
        lastLcdUpdate = millis();
    }
}

// ============================================================
// === [PART 5] NETWORK & GOOGLE FUNCTIONS ===
// ============================================================

void connectToWiFi() {
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("WIFI SEARCHING...");
    unsigned long start = millis();
    while (wifiMulti.run() != WL_CONNECTED && millis() - start < 20000) {
        drawProgressBar(map(millis()-start, 0, 20000, 0, 100));
        delay(500);
    }
    forceLcdClear = true; // Paksa refresh setelah action selesai
}

void getSettingsFromGoogle() {
    if(WiFi.status() != WL_CONNECTED) return;
    lcd.clear();
    lcd.setCursor(0, 1); lcd.print("  SYNCING DATA...   ");
    
    WiFiClientSecure client;
    client.setInsecure();
    HTTPClient http;
    http.setReuse(true);
    String url = "https://script.google.com/macros/s/" + GScriptID + "/exec?action=get_setting";
    
    if (http.begin(client, url)) {
        http.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS);
        int code = http.GET();
        if (code == 200) {
            String payload = http.getString();
            int h[6]; h[0] = payload.indexOf('#');
            for(int i=1; i<6; i++) h[i] = payload.indexOf('#', h[i-1]+1);
            if (h[0]>0) {
                lcdProduk = payload.substring(0, h[0]);
                lcdShift = payload.substring(h[0]+1, h[1]);
                lcdPetugas = payload.substring(h[1]+1, h[2]);
                lastLoggedCounter = payload.substring(h[2]+1, h[3]).toInt();
                currentKodeProduksi = payload.substring(h[3]+1, h[4]);
                String rawDate = (h[5] != -1) ? payload.substring(h[4]+1, h[5]) : payload.substring(h[4]+1);
                currentTglExpired = rawDate.substring(0, 10);
            }
        }
        http.end();
    }
    forceLcdClear = true;
}

void sendWeightToGoogle() {
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("SENDING DATA:");
    lcd.setCursor(0, 1); lcd.print(formatWeight(currentWeight));

    WiFiClientSecure client;
    client.setInsecure();
    HTTPClient http;
    
    http.setReuse(true);
    String url = "https://script.google.com/macros/s/" + GScriptID + "/exec?value=" + String(currentWeight, 2);
    
    if(http.begin(client, url)) {
        http.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS);
        int code = http.GET();
        if(code == 200) {
            String res = http.getString();
            if(res.indexOf("Sukses") != -1) {
                lastLoggedCounter++;
                updateLastDataHistory(currentWeight, lastLoggedCounter);
                dataSentForThisObject = true;
                lcd.setCursor(0, 3); lcd.print("SUCCESS!            ");
                delay(1000); // Tahan sebentar agar pesan sukses terbaca
            }
        }
        http.end();
    }
    forceLcdClear = true;
}

void deleteLastData() {
    lcd.clear();
    lcd.setCursor(0, 1); lcd.print("  DELETING LAST...  ");
    WiFiClientSecure client;
    client.setInsecure();
    HTTPClient http;
    String url = "https://script.google.com/macros/s/" + GScriptID + "/exec?action=delete_last";
    if(http.begin(client, url)) {
        http.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS);
        if(http.GET() == 200) {
            if(lastLoggedCounter > 0) lastLoggedCounter--;
            lcd.setCursor(0, 2); lcd.print("  DELETE SUCCESS!   ");
            delay(1000);
        }
        http.end();
    }
    forceLcdClear = true;
}

// ============================================================
// === [PART 6] UI HELPERS ===
// ============================================================

void showPage1_Main() {
    lcd.setCursor(0, 0);
    lcd.print(lcdProduk.substring(0, 20));
    
    lcd.setCursor(0, 1);
    lcd.print("KP:"); lcd.print(currentKodeProduksi.substring(0, 8));
    lcd.print(" SF:"); lcd.print(lcdShift);

    lcd.setCursor(0, 2);
    if (dataSentForThisObject) {
        lcd.print(">> TERKIRIM <<      ");
    } else if (objectDetected && currentWeight >= MIN_VALID_WEIGHT) {
        unsigned long elapsed = millis() - proximityStartTime;
        int pct = (elapsed * 100) / TRIGGER_DELAY;
        lcd.print("LOCK: [");
        int bars = map(constrain(pct, 0, 100), 0, 100, 0, 10);
        for(int i=0; i<10; i++) {
            if(i < bars) lcd.print("=");
            else lcd.print(" ");
        }
        lcd.print("]  ");
    } else {
        String wLine = "BERAT: " + formatWeight(currentWeight);
        while(wLine.length() < 20) wLine += " "; // Padded spasi sampai ujung layar
        lcd.print(wLine);
    }

    lcd.setCursor(0, 3);
    lcd.print("Cnt:"); lcd.print(lastLoggedCounter);
    lcd.setCursor(14, 3);
    lcd.print(WiFi.status() == WL_CONNECTED ? "WF:OK " : "WF:!!");
}

void showPage2_Info() {
    lcd.setCursor(0, 0);
    lcd.print("--- DETAIL INFO --- ");
    lcd.setCursor(0, 1);
    lcd.print("P: "); lcd.print(lcdPetugas.substring(0, 17));
    lcd.setCursor(0, 2);
    lcd.print("Exp: "); lcd.print(currentTglExpired);
    lcd.setCursor(0, 3);
    lcd.print("Ver: V6.6 ESP8266   ");
}

void showPage3_Network() {
    lcd.setCursor(0, 0);
    lcd.print("--- NETWORK ---     ");
    lcd.setCursor(0, 1);
    lcd.print("SSID: "); lcd.print(WiFi.SSID().substring(0, 14));
    lcd.setCursor(0, 2);
    lcd.print("IP: "); lcd.print(WiFi.localIP().toString());
    lcd.setCursor(0, 3);
    lcd.print("RSSI: "); lcd.print(WiFi.RSSI()); lcd.print(" dBm     ");
}

void showPage4_History() {
    lcd.setCursor(0, 0);
    lcd.print("--- HISTORY LOG --- ");
    for(int i=0; i<3; i++) {
        lcd.setCursor(0, i+1);
        lcd.print(lastDataHistory[i]);
        // Tambah spasi agar rapi jika teks pendek
        for(int s=lastDataHistory[i].length(); s<20; s++) lcd.print(" ");
    }
}

String formatWeight(float weight) {
    return String(weight, 2) + "kg"; 
}

void updateLastDataHistory(float weight, unsigned int counter) {
    for(int i=2; i>0; i--) lastDataHistory[i] = lastDataHistory[i-1];
    lastDataHistory[0] = "#" + String(counter) + " " + formatWeight(weight);
}

void drawProgressBar(int percent) {
    lcd.setCursor(0, 1);
    lcd.print("PROG: [");
    int bars = map(constrain(percent, 0, 100), 0, 100, 0, 10);
    for(int i=0; i<10; i++) {
        if(i < bars) lcd.print("=");
        else lcd.print(" ");
    }
    lcd.print("]  ");
}