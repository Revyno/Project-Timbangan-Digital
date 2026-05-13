/**
 * ====================================================================
 * PROJECT: WiFi Scale System (Sonic XK-3190 A1+ Custom Pattern)
 * DEVICE:  ESP8266 (NodeMCU/Wemos) + RS232-TTL + LCD 16x02
 * VERSION: V6.19
 * ====================================================================
 */

#include <ESP8266WiFi.h>
#include <ESP8266WiFiMulti.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecureBearSSL.h>
#include <SoftwareSerial.h>
#include <Wire.h>
#include <LiquidCrystal_I2C.h>
#include <time.h>

// ============================================================
// === [PART 1] KONFIGURASI SISTEM ===
// ============================================================

// GANTI DENGAN SCRIPT ID
const String GScriptID ="AKfycbxN5sHd_WubwGR2h7Wo-jOP51kkzjuTAz-Oln5hPqGzpa60q_UHoDSK2WlR48KcMENT"; 

// WIFI CREDENTIALS
const char* WIFI_SSID_1 = "Intarnet";
const char* WIFI_PASS_1 = "aaaabbbb";
const char* WIFI_SSID_2 = "PT LSI";
const char* WIFI_PASS_2 = "Ladang593";
const char* WIFI_SSID_3 = "Ladang Lima"; 
const char* WIFI_PASS_3 = "Ladang593"; 

// PIN MAPPING UNTUK ESP8266 (NodeMCU / Wemos D1 Mini)
#define I2C_SDA_PIN 4  // D2
#define I2C_SCL_PIN 5  // D1

// Menggunakan SoftwareSerial
#define RX_PIN_SCALE 13 // D7 - TX Timbangan masuk ke RX Pin ini
#define TX_PIN_SCALE 15 // D8 - RX Timbangan (biasanya tidak dipakai untuk mengirim ke timbangan)

// Tombol
#define BTN_MENU 14     // D5 (GPIO 14)
#define BTN_SEND 12     // D6 (GPIO 12)

const int SCALE_BAUDRATE = 9600; 
const float WEIGHT_DIVIDER = 10.0; 
const float MIN_VALID_WEIGHT_KG = 0.05; 

// ============================================================
// === [PART 2] OBJEK & VARIABEL ===
// ============================================================

// Inisialisasi LCD 16x2
LiquidCrystal_I2C lcd(0x27, 16, 2); 
ESP8266WiFiMulti wifiMulti;
SoftwareSerial scaleSerial(RX_PIN_SCALE, TX_PIN_SCALE); 

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
    Serial.begin(115200); // Untuk Debug ke Laptop
    
    // Inisialisasi SoftwareSerial untuk Timbangan
    scaleSerial.begin(SCALE_BAUDRATE);
    
    // Konfigurasi Pin Tombol
    pinMode(BTN_MENU, INPUT_PULLUP);
    pinMode(BTN_SEND, INPUT_PULLUP); 
    
    // Inisialisasi LCD
    Wire.begin(I2C_SDA_PIN, I2C_SCL_PIN);
    lcd.init();
    lcd.backlight();
    lcd.clear();
    
    lcd.setCursor(0, 0); lcd.print("SYSTEM LSI V6.20");
    lcd.setCursor(0, 1); lcd.print("Booting ESP8266.");
    
    delay(1000);

    WiFi.mode(WIFI_STA); 
    
    // --- OPTIMASI & MAKSIMALISASI WIFI ---
    WiFi.setSleepMode(WIFI_NONE_SLEEP); // Mencegah WiFi tidur agar koneksi lebih responsif & stabil
    WiFi.setOutputPower(20.5);          // Memaksimalkan daya pancar sinyal WiFi (maks 20.5 dBm)
    WiFi.setAutoReconnect(true);        // Pastikan fitur otomatis terhubung kembali aktif
    
    wifiMulti.addAP(WIFI_SSID_1, WIFI_PASS_1);
    wifiMulti.addAP(WIFI_SSID_2, WIFI_PASS_2);
    wifiMulti.addAP(WIFI_SSID_3, WIFI_PASS_3);
    
    connectToWiFi(); 
    syncTime(); 
    getSettingsFromGoogle(); 
    lcd.clear(); 
}

void loop() {
    // Membaca Data dari SoftwareSerial (Timbangan)
    while (scaleSerial.available() > 0) {
        char c = scaleSerial.read();
        // Serial.print(c); // Uncomment untuk debug raw data timbangan di Serial Monitor
        
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
    delay(10); // Yield untuk background task WiFi
}

// ============================================================
// === [PART 4] NETWORK & TIME SYNC ===
// ============================================================

void syncTime() {
    lcd.clear();
    lcd.setCursor(0,0); lcd.print("Syncing Time...");
    
    // Konfigurasi NTP (GMT+7 untuk WIB) menggunakan server Indonesia & Asia
    configTime(7 * 3600, 0, "id.pool.ntp.org", "asia.pool.ntp.org", "time.google.com");
    
    time_t now = time(nullptr);
    int timeout = 0;
    while (now < 8 * 3600 * 2 && timeout < 40) { 
        delay(500);
        now = time(nullptr);
        timeout++;
        showSpinner(15, 0); // Di kolom ke-15 untuk LCD 16x2
    }
    
    if (now > 1000) {
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
        // Setup WiFiClientSecure untuk ESP8266 (BearSSL)
        std::unique_ptr<BearSSL::WiFiClientSecure> client(new BearSSL::WiFiClientSecure);
        client->setInsecure(); // Abaikan validasi sertifikat
        
        HTTPClient http;
        http.setTimeout(15000);
        
        String url = "https://script.google.com/macros/s/" + GScriptID + "/exec?action=get_setting";
        
        Serial.print("Fetching: "); Serial.println(url);

        if (http.begin(*client, url)) {
            // --- OPTIMASI HEADER ---
            http.addHeader("User-Agent", "ESP8266-Scale-System");
            http.addHeader("Connection", "keep-alive"); // Mencegah putus koneksi saat Redirect
            
            http.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS); 
            
            code = http.GET();
            
            if (code == 200) {
                payload = http.getString();
                http.end();
                break;
            } else {
                Serial.printf("HTTP Fail: %d\n", code);
            }
            http.end();
        }
        
        if (retry < 3 && code != 200) {
            lcd.setCursor(0, 1);
            lcd.print(("Retry " + String(retry) + "/3...   ").substring(0,16));
            delay(2000); 
        }
    }
    
    // Parsing Data 
    if (code == 200) {
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
        } else {
            if (payload.length() > 0) {
                lcdProduk = payload;
                lcd.clear(); lcd.print("SYNC PARTIAL");
            } else {
                lcd.clear(); lcd.print("DATA KOSONG");
            }
        }
    } else {
        lcd.clear();
        lcd.setCursor(0,0); lcd.print("SYNC GAGAL!");
        lcd.setCursor(0,1); lcd.print("Err: " + String(code));
    }
    delay(1500);
}

void connectToWiFi() {
    lcd.clear(); lcd.setCursor(0, 0); lcd.print("Mencari WiFi...");
    unsigned long start = millis();
    while (wifiMulti.run() != WL_CONNECTED && (millis() - start < 15000)) {
        showSpinner(15, 0); // Di kolom ke-15 untuk LCD 16x2
        lcd.setCursor(0, 1); lcd.print("Tunggu WiFi..");
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
    if (digitalRead(BTN_SEND) == LOW) {
        unsigned long pressStart = millis();
        bool longPressActioned = false;
        bool uiChanged = false;
        while (digitalRead(BTN_SEND) == LOW) {
            unsigned long duration = millis() - pressStart;
            if (duration > 500 && !longPressActioned) {
                if (!uiChanged) { lcd.clear(); lcd.setCursor(0,0); lcd.print("Tahan Reconnect"); uiChanged = true; }
                int bars = map(duration, 500, 2000, 0, 16); // Disesuaikan ke 16 karakter
                if (bars > 16) bars = 16;
                lcd.setCursor(0, 1); for(int i=0; i<bars; i++) lcd.print(">"); 
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

    if (digitalRead(BTN_MENU) == LOW) {
        unsigned long pressStart = millis();
        bool longPressActioned = false;
        bool uiChanged = false;
        while (digitalRead(BTN_MENU) == LOW) {
            unsigned long duration = millis() - pressStart;
            if (duration > 500 && !longPressActioned) {
                if (!uiChanged) { lcd.clear(); lcd.setCursor(0,0); lcd.print("Tahan utk Sync"); uiChanged = true; }
                int bars = map(duration, 500, 2000, 0, 16); // Disesuaikan ke 16 karakter
                if (bars > 16) bars = 16;
                lcd.setCursor(0, 1); for(int i=0; i<bars; i++) lcd.print(">"); 
            }
            if (duration > 2000 && !longPressActioned) { getSettingsFromGoogle(); longPressActioned = true; }
            delay(50); 
        }
        if (!longPressActioned && (millis() - lastDebounceTime) > 300) {
            lcdPage++;
            if (lcdPage > 6) lcdPage = 1; // Kembali ke 6 halaman karena 1 halaman muat 2 baris
            lcd.clear(); 
            lastDebounceTime = millis();
        }
    }
}

void showPage_Main() {
    // Tiap baris dipotong atau diisi spasi kosong hingga 16 karakter menggunakan substring(0,16)
    switch(lcdPage) {
        case 1: { // Data Timbangan & Hitungan
            lcd.setCursor(0,0); lcd.print(("Tot:" + formatWeightID(todayTotalWeight, 1) + " C:" + String(todayCount) + "    ").substring(0,16));
            lcd.setCursor(0,1); lcd.print(("WT: " + formatWeightID(currentWeight, 2) + " Kg       ").substring(0,16));
            break;
        }
        case 2: { // Info Produk & Asal
            lcd.setCursor(0,0); lcd.print((lcdProduk + "                ").substring(0,16));
            lcd.setCursor(0,1); lcd.print(("Asal: " + lcdAsal + "          ").substring(0,16));
            break;
        }
        case 3: { // Info Kendaraan
            lcd.setCursor(0,0); lcd.print(("Drv: " + lcdSopir + "          ").substring(0,16));
            lcd.setCursor(0,1); lcd.print(("Plat: " + lcdPlat + "          ").substring(0,16));
            break;
        }
        case 4: { // Info Jenis & Network
            lcd.setCursor(0,0); lcd.print(("J: " + lcdJenis + "            ").substring(0,16));
            lcd.setCursor(0,1); lcd.print(((WiFi.status() == WL_CONNECTED ? WiFi.SSID() : "No WiFi") + "                ").substring(0,16));
            break;
        }
        case 5: { // Info Sistem
            lcd.setCursor(0,0); lcd.print(((WiFi.status() == WL_CONNECTED ? WiFi.localIP().toString() : "0.0.0.0") + "                ").substring(0,16));
            lcd.setCursor(0,1); lcd.print(("Sig: " + String(WiFi.RSSI()) + " dBm      ").substring(0,16));
            break;
        }
        case 6: { // Info MAC
            lcd.setCursor(0,0); lcd.print("ID Device (MAC):");
            String mac = WiFi.macAddress();
            mac.replace(":", "");
            lcd.setCursor(0,1); lcd.print((mac + "                ").substring(0,16));
            break;
        }
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
        // Setup WiFiClientSecure untuk ESP8266 (BearSSL)
        std::unique_ptr<BearSSL::WiFiClientSecure> client(new BearSSL::WiFiClientSecure);
        client->setInsecure();

        HTTPClient http;
        http.setTimeout(20000); // 20 Detik timeout
        String url = "https://script.google.com/macros/s/" + GScriptID + "/exec?value=" + String(currentWeight, 2);
        
        if(http.begin(*client, url)) {
            // --- OPTIMASI HEADER ---
            http.addHeader("User-Agent", "ESP8266-Scale-System");
            http.addHeader("Connection", "keep-alive"); // Mencegah putus koneksi saat Redirect

            http.setFollowRedirects(HTTPC_STRICT_FOLLOW_REDIRECTS);
            
            code = http.GET();
            if(code == 200) {
                res = http.getString();
                http.end();
                break;
            }
            http.end();
        }
        if (code != 200) delay(1000);
    }
    
    if(code == 200) {
        if(res.indexOf("Sukses") >= 0) {
            lcd.clear(); lcd.print("TERSIMPAN!");
            int idxNo = res.indexOf("No:");
            int idxT = res.indexOf("T:");
            if (idxNo >= 0) {
                int endNo = (idxT > 0) ? idxT : res.length(); 
                todayCount = res.substring(idxNo + 3, endNo).toInt();
            }
            if (idxT >= 0) todayTotalWeight = res.substring(idxT + 2).toFloat();
        } else { lcd.clear(); lcd.print("FAILED DB"); }
    } else { lcd.clear(); lcd.print("ERR:" + String(code)); }
    
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