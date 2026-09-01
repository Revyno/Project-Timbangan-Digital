# Project Timbangan Digital (Sistem Penimbangan Terintegrasi dengan IOT)

Repositori ini berisi keseluruhan sistem penimbangan digital cerdas untuk Ladang Lima yang terbagi menjadi dua komponen utama: Perangkat Keras (IoT) dan Perangkat Lunak (Dashboard Web). 

Struktur repositori ini dibagi ke dalam dua direktori/folder utama:

## 1. Folder `Arduino` (Perangkat Keras IoT)
Folder ini berisi kode sumber (source code) C++ yang ditanamkan (di-flash) ke mikrokontroler (seperti ESP32). 
Perangkat keras (IoT) ini bertugas membaca data fisik dari sensor beban (Load Cell via modul HX711), memprosesnya, dan kemudian mengirimkan data pembacaan berat secara *real-time* ke server Timbangan Digital melalui jaringan Wi-Fi (via WebSocket atau HTTP API).

**Petunjuk Penggunaan:**
1. Buka *source code* (file berektensi `.ino`) di dalam folder ini menggunakan perangkat lunak **Arduino IDE**.
2. Pastikan Anda telah memasang (install) *libraries* yang dibutuhkan pada Arduino IDE (seperti library `WiFi`, `WebSocketsClient`, `ArduinoJson`, dan `HX711`).
3. Sesuaikan parameter konfigurasi pada kode, seperti SSID Wi-Fi, *password* Wi-Fi, serta URL / IP Address dari Server Dashboard `Timbangan`.
4. Hubungkan mikrokontroler ke komputer dengan kabel USB, pilih *Board* dan *Port* yang sesuai, lalu klik **Upload** untuk memasukkan program.

---

## 2. Folder `Timbangan` (Perangkat Lunak Dashboard Web)
Folder ini merupakan sistem **Web Dashboard** yang dibangun menggunakan *framework* web modern (Laravel, Vue 3, Inertia.js, Tailwind CSS). Dashboard ini berfungsi untuk mendengarkan (*listen*) data dari perangkat Arduino, mencatat hasil penimbangan (berhasil/invalid) ke dalam database MySQL, serta menampilkan grafik dan aktivitas penimbangan secara *real-time* kepada Operator dan Admin di berbagai lokasi (Pasuruan/Surabaya).

**Petunjuk Penggunaan & Instalasi (Development Lokal):**

1. Buka terminal (Command Prompt/PowerShell/Git Bash) dan arahkan (*change directory*) ke dalam folder `Timbangan`:
   ```bash
   cd Timbangan
   ```
2. Instal semua pustaka dependensi backend (PHP) menggunakan Composer:
   ```bash
   composer install
   ```
3. Instal semua pustaka dependensi frontend (JavaScript/Node.js) menggunakan NPM:
   ```bash
   npm install
   ```
4. Salin file template konfigurasi dan hasilkan Application Key baru:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *(Pastikan Anda mengubah konfigurasi database `DB_DATABASE`, `DB_USERNAME`, dll. di dalam file `.env` yang baru dibuat agar terhubung ke database lokal Anda).*
5. Eksekusi migrasi untuk membangun struktur tabel di database Anda:
   ```bash
   php artisan migrate
   ```
6. Jalankan proses-proses server berikut di terminal yang terpisah (*split terminal*):
   - **Terminal 1** - Menjalankan server aplikasi web Laravel:
     ```bash
     php artisan serve
     ```
   - **Terminal 2** - Menjalankan WebSocket/Reverb server untuk fitur *real-time*:
     ```bash
     php artisan reverb:start
     ```
   - **Terminal 3** - Menjalankan proses *compiling* antarmuka Vite/Vue:
     ```bash
     npm run dev
     ```
7. Aplikasi sudah dapat diakses menggunakan web browser melalui URL yang diberikan oleh artisan serve (umumnya `http://127.0.0.1:8000`).

---

## 3. Token API (Ringkasan)

Perangkat IoT dan sinkronisasi antar-server memakai token. Dokumentasi lengkap
(cara rotasi, contoh curl, aturan penolakan) ada di
[`Timbangan/README.md` → 🔑 Token API](Timbangan/README.md#-token-api).

### a. Device Token — perangkat timbangan → server

Dikirim sebagai field `token` (body form untuk `POST`, query string untuk `GET`),
dicocokkan ke kolom `devices.device_token`. Token salah/tidak terdaftar → `401 Unauthorized`.

| Modul | Prefix endpoint (v1) | `device_token` bawaan seeder | Sketch Arduino |
|---|---|---|---|
| FG Pasuruan | `/api/v1/fg-pasuruan` | `FG-PASURUAN-001` | `Arduino/Pasuruan/Finished_Goods/` |
| Formulasi Pasuruan | `/api/v1/formulasi` | `FORM-PASURUAN-001` | `Arduino/Pasuruan/Formulasi/` |
| FG PSN | `/api/v1/fg-psn` | `FG-PSN-001` | — |
| FG Surabaya | `/api/v1/fg-surabaya` | `FG-SBY-001` | `Arduino/Surabaya/Formulasi/` |
| Incoming Singkong | `/api/v1/incoming-singkong` | `INC-SINGKONG-001` | `Arduino/Pasuruan/Incoming_Singkong/` |
| Incoming RMPM | `/api/v1/incoming-rmpm` | `INC-RMPM-001` | `Arduino/Pasuruan/Incoming_RMPM/` |
| CS Noodle Sby | `/api/v1/cs-noodle-sby` | *belum di-seed* — `CS-NOODLE-001` (sketch) / `CS-NOODLE-SBY-001` (Postman) | `Arduino/Surabaya/CS_Noodle/` |
| CS FG Sby | `/api/v1/cs-fg-sby` | *belum di-seed* — `CS-FG-SBY-001` | `Arduino/Surabaya/CS_FG_Sby/` |

Endpoint per modul: `GET {prefix}/settings`, `POST {prefix}/weight`,
`GET|POST {prefix}/ping`. Route lama `/api/iot/...` tetap aktif untuk perangkat
yang sudah terpasang.

### b. Sync Token — server lokal → server online

Header `X-Sync-Token` pada `POST /api/v1/sync/weighings`, nilainya dari env
`ONLINE_SYNC_TOKEN` dan **harus sama persis** di server `local` maupun `online`.

### c. Endpoint tanpa token

`GET /api/v1/status` (health check) dan `POST /api/v1/driver/identify`
(cukup `qr_code` di body).

> 🔒 Token bawaan di atas hanya untuk **development** dan sudah tertulis di
> repositori ini. Di produksi ganti semua device token dengan nilai acak
> (`Str::random(64)`), isi `ONLINE_SYNC_TOKEN` dengan nilai acak, dan akses API
> hanya lewat HTTPS.

---
*Dokumentasi ini ditujukan untuk mempermudah developer dan pengelola (Admin/IT) dalam memahami arsitektur dan mengatur jalannya sistem Penimbangan Terintegrasi.*
