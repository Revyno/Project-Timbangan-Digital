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
#include <WebSocketsClient.h>

// ============================================================
// === [PART 1] KONFIGURASI SISTEM ===
// ============================================================

// --- LARAVEL API CONFIG ---
const char* SERVER_BASE_URL = "http://192.168.10.204:8000";
const String DEVICE_TOKEN = "FG-PASURUAN-001";

// --- REVERB WEBSOCKET CONFIG ---
const char* REVERB_HOST = "192.168.10.204";a
const int REVERB_PORT = 8080;
const char* REVERB_APP_KEY = "timbanganreverbkey";
WebSocketsClient webSocket;

// --- WIFI CREDENTIALS ---
const char* WIFI_SSID_1 = "Intranet";
const char* WIFI_PASS_1 = "aaaabbbb";


// --- PIN DEFINITIONS (WEMOS D1 R2) ---
#define I2C_SDA D2
#define I2C_SCL D1
#define RX_PIN_SCALE D7
#define TX_PIN_SCALE D8
#define PIN_BTN_MENU D3
#define PIN_PROXIMITY D4
#define PIN_BTN_MANUAL D5  // Tombol START / Kirim berat manual
#define PIN_BTN_TARE D6    // Tombol TARE

// Settings
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

// Variabel Logika Berat
float rawWeight = 0.0;
float tareOffset = 0.0;
float currentWeight = 0.0;
bool objectDetected = false;
bool dataSentForThisObject = false;
unsigned long proximityStartTime = 0;
unsigned long resetWeightTimer = 0;

// Data Penimbangan 
String lcdProduk = "Belum Sync";
String currentKodeProduksi = "-";
String lcdShift = "-";
String lcdPetugas = "-";
// String lcdExpired = "-";
String lcdBerat= "-";
String currentStatus = "idle";
bool apiConnected = false;
unsigned int lastLoggedCounter = 0;
String lastDataHistory[3] = {"-", "-", "-"};

// Reconnect tracking
unsigned long lastWifiReconnectAttempt = 0;
unsigned long lastWsHealthCheck = 0;
int wifiReconnectCount = 0;
const int MAX_WIFI_RETRIES_BEFORE_RESTART = 2; // Restart ESP setelah 2x gagal

// Navigasi UI
int currentPage = 0;
unsigned long lastLcdUpdate = 0;
bool forceLcdClear = true;
int lastPage = -1;

// Status Tombol
unsigned long btnMenuStart = 0;
bool btnMenuActive = false;
int btnMenuHoldState = 0;
int lastMenuHold = 0;

unsigned long btnManualStart = 0;
bool btnManualActive = false;
int btnManualHoldState = 0;
int lastManualHold = 0;

bool btnTareActive = false;

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
    randomSeed(analogRead(0));

    Wire.begin(I2C_SDA, I2C_SCL);
    lcd.init();
    lcd.backlight();
    lcd.clear();
    lcd.setCursor(0, 0); lcd.print("  TIMBANGAN V7.3  ");
    lcd.setCursor(0, 1); lcd.print("PT LADANG LIMA INDO  ");

    // 1. KONEKSI WIFI
    WiFi.mode(WIFI_STA);
    WiFi.setSleepMode(WIFI_NONE_SLEEP); // Mencegah WiFi sleep/putus-nyambung
    WiFi.setAutoReconnect(true);
    WiFi.persistent(true);

    wifiMulti.addAP(WIFI_SSID_1, WIFI_PASS_1);

    // 2. KONEKSI WIFI (dengan retry)
    connectToWiFi();
    
    // 3. AMBIL SETTING AWAL (HTTP) - retry sampai berhasil atau timeout
    if (WiFi.status() == WL_CONNECTED) {
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("Sync API...");
        int syncRetry = 0;
        while (!apiConnected && syncRetry < 5) {
            syncSettings();
            if (!apiConnected) {
                syncRetry++;
                lcd.setCursor(0, 1); 
                lcd.print("Retry "); lcd.print(syncRetry); lcd.print("/5...      ");
                delay(2000);
            }
        }
    }

    // 4. KONEK KE LARAVEL REVERB (WEBSOCKET REALTIME)
    connectWebSocket();

    lcd.clear();
    lcd.setCursor(0, 0); lcd.print(" STATUS SYNC ");
    if(!apiConnected) {
      lcd.setCursor(0, 1); lcd.print("   API ERROR   ");
      lcd.setCursor(0, 2); lcd.print("  Will retry...  ");
    } else if(currentStatus == "ready"){
      lcd.setCursor(0, 1); lcd.print("       READY!       ");
    } else {
      lcd.setCursor(0, 1); lcd.print("      NOT READY     ");
    }
    
    delay(500);
}

// ============================================================
// === [PART 4] MAIN LOOP ===
// ============================================================

void loop() {
    static unsigned long lastSyncRetry = 0;
    static bool wasWifiConnected = true;

    // 0. JAGA KONEKSI WIFI
    if (WiFi.status() != WL_CONNECTED) {
        if (wasWifiConnected) {
            wasWifiConnected = false;
            apiConnected = false;
            lcdProduk = "WIFI TERPUTUS  ";
            currentKodeProduksi = "-";
            lcdPetugas = "-";
            forceLcdClear = true;
            Serial.println("[WIFI] Disconnected!");
        }
        
        // Coba reconnect setiap 5 detik
        if (millis() - lastWifiReconnectAttempt > 5000) {
            lastWifiReconnectAttempt = millis();
            wifiReconnectCount++;
            Serial.print("[WIFI] Reconnect attempt #"); Serial.println(wifiReconnectCount);
            
            // Update LCD dengan status reconnect
            lcd.setCursor(0, 3);
            lcd.print("Reconn WiFi #"); lcd.print(wifiReconnectCount); lcd.print("  ");
            
            wifiMulti.run();

            // Jika sudah terlalu banyak gagal, restart ESP
            if (wifiReconnectCount >= MAX_WIFI_RETRIES_BEFORE_RESTART) {
                lcd.clear();
                lcd.setCursor(0, 0); lcd.print("WiFi gagal terus!");
                lcd.setCursor(0, 1); lcd.print("Restart ESP...");
                delay(2000);
                ESP.restart();
            }
        }
    } else {
        // Jika WiFi baru saja tersambung kembali
        if (!wasWifiConnected) {
            wasWifiConnected = true;
            wifiReconnectCount = 0; // Reset counter
            Serial.println("[WIFI] Reconnected! IP: " + WiFi.localIP().toString());
            
            lcd.clear();
            lcd.setCursor(0, 0); lcd.print("WiFi Reconnected!");
            lcd.setCursor(0, 1); lcd.print("IP: "); lcd.print(WiFi.localIP().toString());
            delay(1000);
            
            // Sync ulang settings dari API
            syncSettings();
            
            // Reconnect WebSocket
            connectWebSocket();
            
            forceLcdClear = true;
        }

        bool wasApiDisconnected = !apiConnected;

        // Jika API error (server mati/restart), coba sync ulang setiap 10 detik
        if (!apiConnected && (millis() - lastSyncRetry > 10000)) {
            Serial.println("[API] Retrying sync...");
            syncSettings();
            lastSyncRetry = millis();
            
            // JIKA API BARU SAJA KEMBALI KONEK, PAKSA RELOG WEBSOCKET
            if (apiConnected && wasApiDisconnected) {
                Serial.println("[API] Reconnected! Restarting WebSocket...");
                connectWebSocket();
            }
        }

        // Health check WebSocket setiap 30 detik
        if (millis() - lastWsHealthCheck > 10000) {
            lastWsHealthCheck = millis();
            if (!webSocket.isConnected()) {
                Serial.println("[WS] Not connected, reconnecting...");
                connectWebSocket();
            }
        }

        webSocket.loop(); // Dengarkan Reverb secara realtime
    }

    // 1. READ SCALE (Non-blocking char by char)
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
    bool readyToSend = (!dataSentForThisObject && currentWeight >= MIN_VALID_WEIGHT && currentKodeProduksi != "-");

    if (readyToSend) {
        // Proximity sensor auto-trigger
        if (digitalRead(PIN_PROXIMITY) == LOW) {
            if (!objectDetected) {
                proximityStartTime = millis();
                objectDetected = true;
            } else if (millis() - proximityStartTime >= TRIGGER_DELAY) {
                sendWeightToLaravel();
            }
        } else {
            objectDetected = false;
            proximityStartTime = 0;
        }

        // Manual button (D5) quick press = send weight
        if (digitalRead(PIN_BTN_MANUAL) == LOW) {
            delay(50); // Debounce
            if (digitalRead(PIN_BTN_MANUAL) == LOW && !btnManualActive) {
                sendWeightToLaravel();
                while(digitalRead(PIN_BTN_MANUAL) == LOW) yield(); // Wait release
            }
        }
    }
}

void handleButtons() {
    unsigned long now = millis();

    // --- TOMBOL MANUAL / START (D5) ---
    // Short press (<2s): (handled in handleTriggers if ready, otherwise nothing)
    // Hold 5s: Reset status terkirim
    // Hold 10s: (reserved)
    if (digitalRead(PIN_BTN_MANUAL) == LOW) {
        if (!btnManualActive) { btnManualActive = true; btnManualStart = now; }
        long duration = now - btnManualStart;
        if (duration > 5000) btnManualHoldState = 1;
    } else if (btnManualActive) {
        long duration = now - btnManualStart;
        btnManualActive = false;
        if (duration > 5000) {
            // Long press: Reset status terkirim
            dataSentForThisObject = false;
            lcd.clear();
            lcd.setCursor(0, 1); lcd.print(" STATUS RESET OK ");
            delay(500);
            forceLcdClear = true;
        }
        btnManualHoldState = 0;
    }

    // --- TOMBOL MENU (D3) ---
    // Short press: Ganti halaman LCD
    // Hold 5s: Sync data dari server
    // Hold 10s: Reset WiFi connection
    if (digitalRead(PIN_BTN_MENU) == LOW) {
        if (!btnMenuActive) { btnMenuActive = true; btnMenuStart = now; }
        long duration = now - btnMenuStart;
        if (duration > 10000) btnMenuHoldState = 2;
        else if (duration > 5000) btnMenuHoldState = 1;
    } else if (btnMenuActive) {
        long duration = now - btnMenuStart;
        btnMenuActive = false;
        if (duration > 10000) {
            // Very long press: Reset WiFi
            connectToWiFi();
        } else if (duration > 5000) {
            // Long press: Sync settings
            lcd.clear();
            lcd.setCursor(0, 1); lcd.print("   SYNCING...   ");
            syncSettings();
            delay(500);
            forceLcdClear = true;
        } else {
            // Short press: Next page
            currentPage++;
            if (currentPage > 2) currentPage = 0;
        }
        btnMenuHoldState = 0;
    }

    // --- TOMBOL TARE (D6) ---
    // Short press: Tare (nol-kan berat)
    if (digitalRead(PIN_BTN_TARE) == LOW) {
        if (!btnTareActive) {
            delay(50); // Debounce
            if (digitalRead(PIN_BTN_TARE) == LOW) {
                btnTareActive = true;
                tareOffset = rawWeight;
                currentWeight = 0.0;
                lcd.clear();
                lcd.setCursor(0, 1); lcd.print("     TARE OK!       ");
                delay(500);
                forceLcdClear = true;
            }
        }
    } else {
        btnTareActive = false;
    }
}

// ============================================================
// === [PART 5] API FUNCTIONS ===
// ============================================================

void connectToWiFi() {
    int attempt = 0;
    const int MAX_ATTEMPTS = 3;
    
    while (attempt < MAX_ATTEMPTS && WiFi.status() != WL_CONNECTED) {
        attempt++;
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("WiFi Connect...");
        lcd.setCursor(0, 1); lcd.print("Attempt "); lcd.print(attempt); lcd.print("/"); lcd.print(MAX_ATTEMPTS);
        
        unsigned long start = millis();
        int dots = 0;
        
        while (wifiMulti.run() != WL_CONNECTED && millis() - start < 15000) {
            delay(500);
            lcd.setCursor(dots % 20, 3);
            lcd.print(".");
            dots++;
            // Serial.print(".");
        }
        Serial.println();

        if (WiFi.status() == WL_CONNECTED) {
            break; // Berhasil, keluar dari loop retry
        }
        
        // Gagal, tunggu sebentar sebelum retry
        if (attempt < MAX_ATTEMPTS) {
            lcd.clear();
            lcd.setCursor(0, 0); lcd.print("WiFi GAGAL!");
            lcd.setCursor(0, 1); lcd.print("Retry in 3s...");
            delay(3000);
            
            // Reset WiFi sebelum retry
            WiFi.disconnect();
            delay(500);
        }
    }

    lcd.clear();
    if (WiFi.status() == WL_CONNECTED) {
        Serial.println("[WIFI] Connected: " + WiFi.SSID() + " IP: " + WiFi.localIP().toString());
        lcd.setCursor(0, 0); lcd.print("WiFi Connected!");
        lcd.setCursor(0, 1); lcd.print("SSID: "); lcd.print(WiFi.SSID());
        lcd.setCursor(0, 2); lcd.print("IP: "); lcd.print(WiFi.localIP().toString());
        lcd.setCursor(0, 3); lcd.print("RSSI: "); lcd.print(WiFi.RSSI()); lcd.print(" dBm");
        delay(500);
    } else {
        Serial.println("[WIFI] All attempts failed!");
        lcd.setCursor(0, 0); lcd.print("WiFi SEMUA GAGAL!");
        lcd.setCursor(0, 1); lcd.print("SSID: "); lcd.print(WIFI_SSID_1);
        lcd.setCursor(0, 2); lcd.print("Akan retry otomatis");
        lcd.setCursor(0, 3); lcd.print("di loop utama...");
        delay(2000);
    }
}

void connectWebSocket() {
    Serial.println("[WS] Connecting to Reverb...");
    String wsUrl = String("/app/") + REVERB_APP_KEY + "?protocol=7&client=arduino&version=1.0.0";
    webSocket.disconnect();
    delay(100);
    webSocket.begin(REVERB_HOST, REVERB_PORT, wsUrl.c_str());
    webSocket.onEvent(webSocketEvent);
    webSocket.setReconnectInterval(5000); // Auto-reconnect setiap 5 detik jika terputus
}

void webSocketEvent(WStype_t type, uint8_t * payload, size_t length) {
    switch(type) {
        case WStype_DISCONNECTED:
            apiConnected = false;
            break;
        case WStype_CONNECTED: {
            apiConnected = true;
            // Subscribe ke channel 'iot-channel'
            String msg = "{\"event\":\"pusher:subscribe\",\"data\":{\"channel\":\"iot-channel\"}}";
            webSocket.sendTXT(msg);
            break;
        }
        case WStype_TEXT: {
            String text = (char*)payload;
            DynamicJsonDocument doc(1024);
            deserializeJson(doc, text);
            
            String eventName = doc["event"].as<String>();
            
            // Reverb Ping-Pong Keep Alive
            if(eventName == "pusher:ping") {
                webSocket.sendTXT("{\"event\":\"pusher:pong\"}");
            }
            
            // Tangkap Event dari Laravel Reverb!
            if(eventName == "App\\Events\\SettingsUpdated") {
                String dataStr = doc["data"].as<String>();
                DynamicJsonDocument dataDoc(512);
                deserializeJson(dataDoc, dataStr);
                
                currentStatus = dataDoc["status"].as<String>();
                if (currentStatus == "ready") {
                    currentKodeProduksi = dataDoc["kode_produksi"].as<String>();
                    lcdProduk = dataDoc["nama_produk"].as<String>();
                    lcdPetugas = dataDoc["operator"].as<String>();
                    lcdBerat = dataDoc["berat"].as<String>();
                } else {
                    lcdProduk = "Tidak Ada Antrian";
                    currentKodeProduksi = "-";
                    lcdPetugas = dataDoc["operator"].as<String>();
                    lcdBerat = dataDoc["berat"].as<String>();
                }
                forceLcdClear = true; // Refresh layar
            }
            break;
        }
    }
}

void syncSettings() {
    if(WiFi.status() != WL_CONNECTED) {
        apiConnected = false;
        Serial.println("[API] syncSettings: No WiFi");
        return;
    }

    HTTPClient http;
    WiFiClient client;
    
    // Timeout agar tidak hang jika server lambat
    client.setTimeout(5000); 
    http.setTimeout(5000);
    http.setReuse(false);
    
    String url = String(SERVER_BASE_URL) + "/api/iot/settings?token=" + DEVICE_TOKEN;
    Serial.println("[API] GET " + url);

    http.begin(client, url);
    int code = http.GET();
    Serial.print("[API] Response: "); Serial.println(code);
    
    if (code == 200) {
        apiConnected = true;
        String payload = http.getString();
        DynamicJsonDocument doc(512);
        deserializeJson(doc, payload);

        currentStatus = doc["status"].as<String>();

        if (currentStatus == "ready") {
            currentKodeProduksi = doc["kode_produksi"].as<String>();
            lcdProduk = doc["nama_produk"].as<String>();
            lcdPetugas = doc["operator"].as<String>();
            lcdBerat= doc["berat"].as<String>();
        } else {
            lcdProduk = "Tidak Ada Antrian";
            currentKodeProduksi = "-";
            lcdPetugas = doc["operator"].as<String>();
            lcdBerat= doc["berat"].as<String>();
        }
    } else {
        apiConnected = false;
        if (code < 0) {
            // HTTP client error (connection refused, timeout, dll)
            lcdProduk = "Server Offline  ";
            Serial.println("[API] Connection failed: " + http.errorToString(code));
        } else {
            lcdProduk = "API ERROR " + String(code);
        }
        currentKodeProduksi = "-";
        lcdPetugas = "-";
    }
    http.end();
}

void sendWeightToLaravel() {
    if (WiFi.status() != WL_CONNECTED) {
        lcd.clear();
        lcd.setCursor(0, 1); lcd.print("  NO WIFI!  ");
        lcd.setCursor(0, 2); lcd.print("Data tidak terkirim");
        delay(1000);
        forceLcdClear = true;
        return;
    }

    const int MAX_SEND_RETRIES = 3;
    bool sendSuccess = false;
    
    for (int attempt = 1; attempt <= MAX_SEND_RETRIES; attempt++) {
        lcd.clear();
        lcd.setCursor(0, 0); lcd.print("KIRIM DATA...");
        if (attempt > 1) {
            lcd.setCursor(0, 1); lcd.print("Retry "); lcd.print(attempt); lcd.print("/"); lcd.print(MAX_SEND_RETRIES);
        } else {
            lcd.setCursor(0, 1); lcd.print("Berat: "); lcd.print(currentWeight, 3); lcd.print(" kg");
        }

        HTTPClient http;
        WiFiClient client;

        // Timeout yang wajar agar tidak hang
        client.setTimeout(5000); 
        http.setTimeout(5000);
        http.setReuse(false);

        String url = String(SERVER_BASE_URL) + "/api/iot/weight";

        StaticJsonDocument<200> doc;
        doc["token"] = DEVICE_TOKEN;
        doc["kode_produksi"] = currentKodeProduksi;
        doc["berat"] = currentWeight;

        String body;
        serializeJson(doc, body);
        Serial.println("[API] POST " + url + " body: " + body + " (attempt " + String(attempt) + ")");

        http.begin(client, url);
        http.addHeader("Content-Type", "application/json");

        int code = http.POST(body);
        Serial.print("[API] Send response: "); Serial.println(code);
        
        if (code == 200 || code == 201) {
            apiConnected = true;
            dataSentForThisObject = true;
            sendSuccess = true;
            updateHistory(currentWeight, currentKodeProduksi);
            lcd.setCursor(0, 3); lcd.print("SUCCESS!            ");
            delay(1000);
            http.end();
            break;
        } else {
            apiConnected = false;
            http.end();
            
            if (attempt < MAX_SEND_RETRIES) {
                lcd.setCursor(0, 3); 
                lcd.print("GAGAL! Retry..."); 
                Serial.println("[API] Send failed, retrying in 2s...");
                delay(2000);
            } else {
                lcd.setCursor(0, 2); lcd.print("GAGAL SEMUA RETRY!");
                lcd.setCursor(0, 3); 
                if (code < 0) {
                    lcd.print("Server offline     ");
                } else {
                    lcd.print("Error: "); lcd.print(code); lcd.print("        ");
                }
                delay(2000);
            }
        }
    }
    
    if (!sendSuccess) {
        Serial.println("[API] All send retries failed!");
    }
    
    forceLcdClear = true;
}

// ============================================================
// === [PART 6] UI HELPERS ===
// ============================================================

void updateDisplay() {
    if (millis() - lastLcdUpdate < LCD_UPDATE_INTERVAL) return;

    // Cek apakah ada perubahan status layar
    if (forceLcdClear || currentPage != lastPage || btnMenuHoldState != lastMenuHold || btnManualHoldState != lastManualHold) {
        lcd.clear();
        forceLcdClear = false;
        lastPage = currentPage;
        lastMenuHold = btnMenuHoldState;
        lastManualHold = btnManualHoldState;
    }

    // Show hold state feedback (from V6.6)
    if (btnMenuHoldState == 1) {
        lcd.setCursor(0, 1); lcd.print("    RELEASE TO:     ");
        lcd.setCursor(0, 2); lcd.print("     SYNC DATA      ");
    } else if (btnMenuHoldState == 2) {
        lcd.setCursor(0, 1); lcd.print("    RELEASE TO:     ");
        lcd.setCursor(0, 2); lcd.print("     RESET WIFI     ");
    } else if (btnManualHoldState == 1) {
        lcd.setCursor(0, 1); lcd.print("    RELEASE TO:     ");
        lcd.setCursor(0, 2); lcd.print("     RST STATUS     ");
    } else {
        // Normal page display
        if (currentPage == 0) showMainPage();
        else if (currentPage == 1) showNetworkPage();
        else if (currentPage == 2) showHistoryPage();
    }

    lastLcdUpdate = millis();
}

void showMainPage() {
    // Line 0: Nama Produk
    lcd.setCursor(0, 0); 
    lcd.print("NP: ");
    String dispProduk = lcdProduk;
    while(dispProduk.length() < 16) dispProduk += " ";
    lcd.print(dispProduk.substring(0, 16));
    
    // Line 1: LOT (Kode Produksi)
    lcd.setCursor(0, 1); 
    lcd.print("KP: "); 
    String dispLot = currentKodeProduksi;
    while(dispLot.length() < 16) dispLot += " ";
    lcd.print(dispLot.substring(0, 16));

    // Line 2: Nama Petugas & API Status
    lcd.setCursor(0, 2);
    lcd.print("OP: ");
    String dispOp = lcdPetugas;
    while(dispOp.length() < 11) dispOp += " ";
    lcd.print(dispOp.substring(0, 11));
    
    // lcd.setCursor(12, 2);
    // if (apiConnected) {
    //     lcd.print("OK");
    // } else {
    //     lcd.print("NOT OK");
    // }

    // Line 3: Expired & Weight
    lcd.setCursor(0, 3);
    if (dataSentForThisObject) {
        lcd.print("SENT: ");
        lcd.print(currentWeight, 2);
        lcd.print(" kg -> [OK]");
    } else {
        lcd.print("BRT: ");
        lcd.print(currentWeight, 2);
        lcd.print(" kg          ");
    }
}

void showNetworkPage() {
    lcd.setCursor(0, 0); lcd.print("--- NETWORK ---     ");
    lcd.setCursor(0, 1); lcd.print("SSID: "); lcd.print(WiFi.SSID().substring(0, 14));
    lcd.setCursor(0, 2); lcd.print("IP: "); lcd.print(WiFi.localIP().toString());
    lcd.setCursor(0, 3); 
    String rssiStr = String(WiFi.RSSI());
    String statusStr = "RS:" + rssiStr + " API:" + (apiConnected ? "OK" : "NO");
    while(statusStr.length() < 20) statusStr += " "; 
    lcd.print(statusStr);
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
