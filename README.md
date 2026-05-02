# Timbangan Digital IoT - Ladang Lima

Sistem Monitoring Timbangan Digital Real-Time menggunakan Laravel, Inertia.js, Vue 3, dan IoT Integration.

## 🚀 Tech Stack

### Backend
- **Laravel 12**: Core framework.
- **Laravel Reverb**: WebSocket server untuk komunikasi real-time.
- **Inertia.js**: Jembatan antara Laravel dan Vue (SPA).
- **Laravel Breeze**: Sistem autentikasi dasar.

### Frontend
- **Vue 3 (Composition API)**: Frontend framework.
- **Tailwind CSS**: Styling utility-first.
- **shadcn-vue**: UI component library (Radix Vue based).
- **Lucide Vue Next**: Set ikon modern.
- **Laravel Echo**: Client-side WebSocket listener.

---

## 📂 Struktur Folder Utama

### Backend (Laravel)
- `app/Http/Controllers`: Logika bisnis untuk render halaman Inertia.
- `app/Http/Controllers/Api`: Endpoint REST API untuk perangkat IoT (Arduino/ESP8266).
- `app/Events`: Event broadcasting untuk update berat real-time.
- `routes/web.php`: Definisi route aplikasi web.
- `routes/api.php`: Definisi route API untuk perangkat IoT.

### Frontend (Vue 3)
- `resources/js/Pages`: Komponen halaman utama (SPA).
  - `Dashboard/Admin.vue`: Panel monitoring global untuk admin.
  - `Dashboard/Operator.vue`: Panel operasional untuk operator produksi.
  - `Dashboard/DataView.vue`: Tabel data historis dengan filter & export.
  - `Incoming/`: Modul penerimaan barang (Singkong & RMPM).
- `resources/js/Layouts`: Template layout aplikasi.
  - `AuthenticatedLayout.vue`: Layout utama setelah login (Sidebar & WebSocket listener).
  - `GuestLayout.vue`: Layout untuk halaman login/auth.
- `resources/js/components/ui`: Koleksi komponen dasar shadcn-vue.
- `resources/js/lib/utils.js`: Utility fungsi (seperti helper `cn`).

---

## 🛠️ Instalasi & Pengembangan

### Prasyarat
- PHP >= 8.2
- Node.js & NPM
- Composer
- Laragon / XAMPP (MySQL)

### Langkah-langkah
1. **Clone Repository**
   ```bash
   git clone <repository-url>
   cd Project-Timbangan-Digital/Timbangan
   ```

2. **Instal Dependensi**
   ```bash
   composer install
   npm install
   ```

3. **Konfigurasi Environment**
   Salin `.env.example` menjadi `.env` dan sesuaikan database serta konfigurasi Reverb.
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Migrasi Database**
   ```bash
   php artisan migrate
   ```

5. **Menjalankan Server (Mode Development)**
   Gunakan script khusus yang sudah disiapkan untuk menjalankan server, vite, dan reverb secara bersamaan:
   ```bash
   composer run dev
   ```
   *Script ini akan menjalankan:*
   - `php artisan serve` (Web Server)
   - `npm run dev` (Vite Hot Reload)
   - `php artisan reverb:start` (WebSocket Server)

---

## 📡 Integrasi IoT (Arduino/ESP8266)

Setiap modul memiliki prefix URL tersendiri untuk integrasi perangkat IoT:
- **FG Pasuruan**: `/api/iot`
- **FG Surabaya**: `/api/iot/fg-surabaya`
- **CS Noodle Sby**: `/api/iot/cs-noodle-sby`
- **Incoming Singkong**: `/api/iot/incoming-singkong`
- **Incoming RMPM**: `/api/iot/incoming-rmpm`

**Endpoint Utama**:
- `GET /settings`: Mendapatkan konteks produk & operator aktif.
- `POST /weight`: Mengirimkan data berat hasil timbangan.
- `POST /ping`: Heartbeat perangkat.

**Contoh Payload Weight**:
```json
{
  "token": "device_secret_token",
  "weight": 10.5,
  "unit": "kg"
}
```
Sistem akan memproses data berdasarkan sesi operator yang sedang aktif dan melakukan broadcast via Reverb ke dashboard Vue secara real-time.

---

## 📄 Lisensi
Sistem ini dikembangkan untuk penggunaan internal **Ladang Lima**.
