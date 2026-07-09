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

## 🖥️ HMI Kiosk & Store-and-Forward (Local → Online)

Tampilan operator gaya **kiosk full-screen** (acuan untuk konversi ke HMI fisik),
dengan 2 template: **Bahan Baku** (`/hmi-display/bahan-baku`) dan **Formulasi**
(`/hmi-display/formulasi`). Keduanya: grid pilih bahan/produk, kotak berat besar, tombol
**PRINT / Tare / Zero / Unit**, dan daftar "Timbangan ke-".

### Alur data (anti "server meledak")

```
Timbangan → berat LIVE (ephemeral, tidak disimpan) → angka bergerak di HMI
HMI → PRINT → server LOKAL simpan 1 baris (hmi_weighings, pending)
     → Job ForwardWeighingsBatch (BATCH, WithoutOverlapping, tahan online mati)
     → server ONLINE upsert by uuid (idempoten) → broadcast Reverb ke dashboard
```

Kunci optimasinya: **hanya PRINT** yang menyentuh DB & jaringan; berat live tidak
pernah disimpan/di-forward. Banyak PRINT digabung jadi sedikit HTTP call, dan
broadcast dibuat **ter-queue** (`WeightReceived` kini `ShouldBroadcast`, bukan
`ShouldBroadcastNow`).

### Peran server (`APP_ROLE` — lihat `config/hmi.php`)

| `APP_ROLE` | Peran | Env yang relevan |
|---|---|---|
| `online` (default) | Cloud/VPS. Terima sync → broadcast. Cocok juga **all-in-one** (PRINT langsung disimpan + broadcast, tanpa forward). | `ONLINE_SYNC_TOKEN` (harus sama dgn server lokal) |
| `local` | Edge di pabrik. PRINT → DB lokal → forward BATCH ke online. | `ONLINE_SYNC_URL`, `ONLINE_SYNC_TOKEN`, `SYNC_BATCH_SIZE` |

> ⚠️ **Wajib ada queue worker berjalan.** Karena broadcast sekarang ter-queue,
> tanpa worker dashboard tidak update. Service docker `queue` sudah menjalankan
> `php artisan queue:work`. Di dev lokal, jalankan sendiri:
> `php artisan queue:work`. Server `local` juga butuh service `scheduler`
> (`schedule:work`) sebagai jaring pengaman forward tiap menit.

Endpoint sync antar-server: `POST /api/v1/sync/weighings` (header `X-Sync-Token`,
hanya aktif saat `APP_ROLE=online`).

---

## 🚢 Deploy Produksi (VPS Ubuntu + Docker + Git)

Deployment produksi memakai **Docker Compose** dengan 6 service: `app` (PHP-FPM),
`nginx` (web server), `mysql`, `reverb` (WebSocket), `queue` (worker), dan
`scheduler`. Semua asset frontend di-build di dalam image, jadi VPS **tidak** perlu
PHP, Node, atau Composer terpasang — cukup Docker.

### 1. Siapkan VPS (sekali saja)

```bash
# Login ke VPS
ssh user@IP_VPS

# Update sistem
sudo apt update && sudo apt upgrade -y

# Install Docker + plugin Compose (script resmi Docker)
curl -fsSL https://get.docker.com | sudo sh

# Agar bisa menjalankan docker tanpa sudo (login ulang setelah ini)
sudo usermod -aG docker $USER
newgrp docker

# Verifikasi
docker --version
docker compose version
```

### 2. Ambil kode & konfigurasi environment

```bash
# Clone repo (butuh git terpasang: sudo apt install -y git)
git clone <repository-url>
cd Project-Timbangan-Digital/Timbangan

# Salin template environment produksi lalu edit
cp .env.production.example .env
nano .env
```

Yang **wajib** diisi/diganti di `.env`:

| Variabel | Isi dengan |
|---|---|
| `APP_URL` | `https://domain-anda.com` (atau `http://IP_VPS` bila belum ada domain) |
| `DB_PASSWORD`, `DB_ROOT_PASSWORD` | password kuat & acak |
| `REVERB_APP_KEY`, `REVERB_APP_SECRET`, `REVERB_APP_ID` | nilai acak (jangan pakai default) |
| `VITE_REVERB_HOST` | domain (Skenario A) **atau** IP VPS (Skenario B) — lihat komentar di file |
| `VITE_REVERB_PORT` / `VITE_REVERB_SCHEME` | `443`/`https` (domain+SSL) atau `80`/`http` (IP saja) |

> ⚠️ **Penting:** `VITE_REVERB_*` di-*bake* ke dalam bundle JavaScript **saat build image**.
> Kalau nilainya berubah, image **harus di-build ulang** (`docker compose build`), tidak
> cukup restart.

### 3. Build & jalankan

```bash
# 1. Build image (frontend + backend)
docker compose build

# 2. Generate APP_KEY, lalu tempel hasilnya ke baris APP_KEY= di .env
docker compose run --rm --entrypoint php app artisan key:generate --show
nano .env        # APP_KEY=base64:.....

# 3. Jalankan seluruh stack (migrasi & cache berjalan otomatis)
docker compose up -d
```

> Karena `.env` disuntikkan sebagai environment variable (`env_file`), bukan file di
> dalam container, gunakan `key:generate --show` lalu salin manual ke `.env` —
> bukan `key:generate` biasa (yang mencoba menulis file).

Container `app` otomatis menjalankan `migrate --force` + **seeding** + cache
konfigurasi saat start (lihat `docker/entrypoint.sh`).

Seeding dikontrol oleh env `DB_SEED` (default `auto`):

| `DB_SEED` | Perilaku saat container `app` start |
|---|---|
| `auto` (default) | Seed **hanya bila database masih kosong** (fresh install). Aman — restart/redeploy tidak menghapus data. |
| `always` | Paksa `db:seed` tiap start. ⚠️ `DatabaseSeeder` melakukan `truncate`, jadi ini **menghapus & mengisi ulang** data. |
| `never` | Tidak pernah seed otomatis. |

Seed manual kapan saja (mis. bila `DB_SEED=never`):

```bash
docker compose exec app php artisan db:seed --force
```

Cek status & log:

```bash
docker compose ps
docker compose logs -f app
docker compose logs -f reverb
```

Buka `http://IP_VPS` (atau domain Anda) di browser.

### 4. Update / redeploy (setiap ada perubahan kode)

```bash
cd Project-Timbangan-Digital/Timbangan
git pull
docker compose up -d --build        # rebuild image + migrasi otomatis
docker compose exec app php artisan optimize   # (opsional) refresh cache
```

> Karena OPcache aktif dengan `validate_timestamps=0`, kode baru baru berlaku setelah
> image di-build ulang / container `app` di-restart (sudah otomatis oleh perintah di atas).

---

## 🗄️ Database (MySQL)

**MySQL sudah termasuk di dalam stack Docker** (service `mysql`, image `mysql:8.0`) —
**tidak perlu meng-install MySQL secara manual di VPS**. Saat `docker compose up -d`
dijalankan pertama kali, container MySQL otomatis:

1. Membuat database sesuai `DB_DATABASE`.
2. Membuat user sesuai `DB_USERNAME` / `DB_PASSWORD`.
3. Menyimpan data secara permanen di Docker volume `mysql_data`
   (data **tetap aman** walau container di-restart / rebuild).

Container `app` menunggu MySQL siap (`healthcheck`) lalu **menjalankan migrasi
otomatis** (`php artisan migrate --force`). Jadi tidak ada langkah `migrate` manual.

Kredensial diambil dari `.env` (lihat `.env.production.example`):

| Variabel `.env` | Dipakai untuk |
|---|---|
| `DB_HOST=mysql` | Nama service Docker (jangan diubah untuk setup ini) |
| `DB_DATABASE` | Nama database yang dibuat otomatis |
| `DB_USERNAME` / `DB_PASSWORD` | User aplikasi (dipakai Laravel) |
| `DB_ROOT_PASSWORD` | Password root MySQL (untuk admin/healthcheck) |

> ⚠️ `DB_USERNAME` **tidak boleh** `root` (MySQL tidak mengizinkan membuat ulang user
> root). Pakai nama lain, mis. `timbangan`.

### Operasi database yang sering dipakai

```bash
# Masuk ke shell MySQL di dalam container
docker compose exec mysql mysql -u root -p"$DB_ROOT_PASSWORD" timbangan

# Backup / dump database ke file di host
docker compose exec mysql mysqldump -u root -p"$DB_ROOT_PASSWORD" timbangan > backup_$(date +%F).sql

# Restore dari file backup
cat backup_2026-01-01.sql | docker compose exec -T mysql mysql -u root -p"$DB_ROOT_PASSWORD" timbangan

# Jalankan seeder (isi data awal, opsional)
docker compose exec app php artisan db:seed --force

# Cek status migrasi
docker compose exec app php artisan migrate:status
```

> Port MySQL (3306) **tidak** diekspos ke internet demi keamanan (lihat komentar di
> `docker-compose.yml`). Untuk akses dari luar, aktifkan `ports: "127.0.0.1:3306:3306"`
> lalu sambung lewat SSH tunnel — jangan buka ke publik.

---

## 🔐 Konfigurasi Firewall (UFW) di Ubuntu Server

VPS **harus** dilindungi firewall. Hanya buka port yang diperlukan: SSH (22), HTTP (80),
dan HTTPS (443). Port database (3306) & Reverb (8080) **tidak** diekspos ke publik —
mereka hanya lewat jaringan internal Docker.

```bash
# Kebijakan dasar: tolak semua masuk, izinkan semua keluar
sudo ufw default deny incoming
sudo ufw default allow outgoing

# WAJIB izinkan SSH LEBIH DULU agar tidak terkunci dari VPS
sudo ufw allow OpenSSH          # setara: sudo ufw allow 22/tcp

# Web
sudo ufw allow 80/tcp           # HTTP
sudo ufw allow 443/tcp          # HTTPS (bila pakai SSL)

# Aktifkan firewall
sudo ufw enable

# Cek hasil
sudo ufw status verbose
```

Hasil yang diharapkan (`sudo ufw status`):

```
To                         Action      From
--                         ------      ----
22/tcp (OpenSSH)           ALLOW       Anywhere
80/tcp                     ALLOW       Anywhere
443/tcp                    ALLOW       Anywhere
```

> ⚠️ **Catatan Docker + UFW:** Docker mem-publish port dengan menulis aturan iptables
> langsung, sehingga **melewati** UFW. Selama Anda hanya mem-publish port `80`/`443`
> (seperti di `docker-compose.yml`), ini aman. **Jangan** menambahkan `ports:` untuk
> `mysql` atau `reverb` ke `0.0.0.0`. Jika suatu saat perlu akses DB dari luar,
> bind ke localhost saja: `- "127.0.0.1:3306:3306"`, lalu akses via SSH tunnel.

### (Opsional) HTTPS/SSL

Untuk domain dengan sertifikat gratis Let's Encrypt, cara termudah adalah memasang
**Nginx atau Caddy sebagai reverse-proxy di host** (di depan container), lalu proxy ke
`http://127.0.0.1:80`. Contoh dengan Caddy (`/etc/caddy/Caddyfile`):

```
timbangan.example.com {
    reverse_proxy 127.0.0.1:80
}
```

Caddy mengurus sertifikat & perpanjangan otomatis. WebSocket (`/app`) ikut ter-proxy
tanpa konfigurasi tambahan. Setelah SSL aktif, pastikan di `.env`:
`VITE_REVERB_SCHEME=https`, `VITE_REVERB_PORT=443`, lalu `docker compose up -d --build`.

---

## 🧪 Testing (PHPUnit)

Test suite mencakup: endpoint API IoT (FG Pasuruan, Formulasi, FG PSN), identifikasi
driver, health-check, autentikasi & pencatatan login, kontrol akses dashboard
per-peran, serta unit test (`ShiftService`, model `User`, model `Penimbangan`).
Semua test memakai database **SQLite in-memory** (lihat `phpunit.xml`) sehingga
cepat dan tidak menyentuh database asli.

### Menjalankan test secara lokal

```bash
php artisan test
# atau
./vendor/bin/phpunit --testdox
```

> Butuh ekstensi PHP `pdo_sqlite` aktif. Bila belum aktif di CLI, jalankan:
> `php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit`

### Menjalankan test via Docker

Image `test` (stage `test` di `Dockerfile`) sudah menyertakan dev-dependencies
(PHPUnit) + driver SQLite. Service `test` berada di profile `test` sehingga
**tidak** ikut `docker compose up`:

```bash
docker compose --profile test build test
docker compose --profile test run --rm test
```

Perintah ini menjalankan `php artisan test` di dalam container dan keluar dengan
kode non-nol bila ada test yang gagal — cocok dijadikan gerbang CI sebelum deploy.

---

## 📊 Monitoring (Prometheus + Grafana)

Stack monitoring **opsional** untuk memantau kesehatan server & database secara
real-time: CPU, RAM, disk, jaringan host (VPS), metrik per-container, serta
metrik MySQL. Semua service berada di **profile `monitoring`** sehingga **tidak**
ikut `docker compose up` biasa.

| Komponen | Fungsi | Image |
|---|---|---|
| **Prometheus** | Mengumpulkan & menyimpan metrik (retensi 15 hari) | `prom/prometheus` |
| **Grafana** | Dashboard visual + alert | `grafana/grafana` |
| **node-exporter** | CPU / RAM / disk / network **host** | `prom/node-exporter` |
| **cAdvisor** | Metrik **per-container** (app, mysql, reverb, queue, …) | `cadvisor` |
| **mysqld-exporter** | Metrik **database MySQL** | `prom/mysqld-exporter` |

### 1. Siapkan variabel `.env`

Tambahkan (sudah ada di `.env.production.example`):

```env
GRAFANA_ADMIN_USER=admin
GRAFANA_ADMIN_PASSWORD=ganti_password_grafana
DB_MONITOR_USER=exporter
DB_MONITOR_PASSWORD=exporter_password
```

### 2. Buat user monitoring MySQL

- **DB baru (volume kosong):** otomatis dibuat oleh
  `docker/mysql/init/01-monitoring-user.sql` saat container MySQL pertama kali start.
- **DB yang sudah berjalan:** buat manual (samakan password dengan `DB_MONITOR_PASSWORD`):

  ```bash
  docker compose exec mysql mysql -uroot -p
  ```
  ```sql
  CREATE USER IF NOT EXISTS 'exporter'@'%' IDENTIFIED BY 'exporter_password' WITH MAX_USER_CONNECTIONS 3;
  GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'exporter'@'%';
  FLUSH PRIVILEGES;
  ```

### 3. Nyalakan stack monitoring

```bash
docker compose --profile monitoring up -d
```

- **Grafana:** `http://<IP-VPS>:3000` — login pakai `GRAFANA_ADMIN_*`.
  Datasource Prometheus & dashboard **“Timbangan Digital — Overview”** sudah
  ter-provision otomatis.
- **Prometheus:** hanya di-bind ke `127.0.0.1:9090` (akses via SSH tunnel:
  `ssh -L 9090:localhost:9090 user@vps`). Cek target di **Status → Targets**.

### 4. Dashboard tambahan (opsional)

Import dari Grafana.com (menu **+ → Import → ID**):

| ID | Dashboard |
|---|---|
| `1860` | Node Exporter Full (host lengkap) |
| `14057` | MySQL / mysqld-exporter |
| `14282` | cAdvisor (container) |

> **Firewall:** buka port `3000` (Grafana) hanya untuk IP tepercaya, mis.
> `sudo ufw allow from <IP-kamu> to any port 3000`. Jangan expose Prometheus ke publik.

### Matikan monitoring

```bash
docker compose --profile monitoring down          # hentikan (data metrik tetap)
docker compose --profile monitoring down -v       # + hapus data metrik/dashboard
```

---

## 🧯 Troubleshooting Singkat

| Gejala | Kemungkinan penyebab / solusi |
|---|---|
| Dashboard tidak real-time, indikator "Polling" | Container `reverb` mati (`docker compose logs reverb`) atau `VITE_REVERB_*` salah → build ulang |
| WebSocket gagal connect di browser (console error) | `VITE_REVERB_HOST/PORT/SCHEME` tidak cocok dengan cara akses (domain/IP, http/https) — samakan lalu `--build` |
| `502 Bad Gateway` | Container `app` belum siap / crash saat migrasi → cek `docker compose logs app` |
| Asset (CSS/JS) tidak muncul | Image belum di-build ulang setelah perubahan → `docker compose up -d --build` |
| Perubahan `.env` tidak berpengaruh | Untuk `VITE_*` harus build ulang; untuk lainnya cukup `docker compose up -d` |

---

## 📄 Lisensi
Sistem ini dikembangkan untuk penggunaan internal **Ladang Lima**.
