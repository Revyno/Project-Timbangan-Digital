# PRD — Sistem Timbangan Digital (Ladang Lima)

> Dokumen gabungan: **ringkasan produk & alur kerja**, **integrasi ke aplikasi HRGA** (API local, Swagger, design system), dan **panduan testing Postman**. Modul login/register driver TIDAK dibahas. Status per commit `df549cb6` (branch `grafana`).

---

# BAGIAN A — RINGKASAN PRODUK & ALUR KERJA

## A.1 Ringkasan Produk

Sistem penimbangan industri multi-pabrik (Pasuruan + Surabaya) untuk mencatat berat produk/bahan secara **realtime** dari timbangan fisik (via Arduino/ESP8266) ke aplikasi web, dengan dashboard admin dan kiosk operator.

**Stack:**
- Backend: Laravel 10, Inertia.js, MySQL (produksi) / SQLite (dev)
- Frontend: Vue 3 + Tailwind + komponen shadcn-vue
- Realtime: Laravel Reverb (WebSocket) + Laravel Echo
- Hardware: Arduino/ESP8266 (Wemos D1) baca timbangan RS232 → kirim REST API
- Monitoring: Grafana + Prometheus (Docker)
- Dokumentasi API: Swagger (l5-swagger)

**Arsitektur data — Store & Forward (edge → cloud):**
- **Role `local`** = server edge di pabrik. Terima PRINT dari HMI → simpan DB lokal → forward batch ke server online.
- **Role `online`** = server cloud/VPS. Terima sync dari edge → upsert → broadcast ke dashboard.
- **All-in-one** = cukup role `online` (print langsung simpan + broadcast, tanpa forward).
- Konfigurasi di `config/hmi.php` (`APP_ROLE`, `SITE_CODE`, `ONLINE_SYNC_URL`, `ONLINE_SYNC_TOKEN`).

## A.2 Peran Pengguna (Roles)

Otorisasi **custom** lewat kolom di tabel `users` (bukan spatie/permission — walau paket terpasang):
- `role` — `admin` | `operator`
- `tipe` — modul yang dipegang operator (fg, formulasi_pasuruan, fg_psn, fg_surabaya, cs_noodle_sby, cs_fg_sby, incoming_singkong, incoming_rmpm)
- `shift`, `shift_start`, `shift_end`, `shift_type` (normal/long), `session_locked`

| Role | Akses |
|------|-------|
| **Admin** | Dashboard read-only + chart semua modul, master data (supplier/driver), export CSV/Excel, lihat login-logs. Bypass semua pembatasan shift. |
| **Operator** | Satu modul kiosk saja (sesuai `tipe`), hanya data sendiri, terkunci window shift. |
| **Driver** (publik) | Registrasi mandiri di `/register-driver`, dapat QR code otomatis. Tanpa login. |

**Middleware:**
- `role` (`RoleMiddleware`) — admin bypass, selain itu cek role.
- `shift` (`CheckShiftAccess` via `ShiftService`) — logout jika di luar jam shift.
- `sync.token` (`VerifySyncToken`) — validasi `X-Sync-Token`, hanya aktif saat role `online`.

**Manajemen sesi operator = cache** (bukan baris DB): `session_operator_{id}`, `session_fg_psn_{id}`, `session_singkong_{id}`, dst. Ada `last_session_*` untuk prefill 7 hari.

## A.3 Modul Penimbangan

Setiap modul punya pola sama: **kiosk operator** (start sesi → next → stop shift) + **dashboard admin** (read-only + export). Data masuk dari device via API.

| Modul | Fungsi | Tabel utama |
|-------|--------|-------------|
| **FG Pasuruan** | Finished Goods Pasuruan | `penimbangans` |
| **Formulasi** | Penimbangan formulasi (HMI) | `hmi_weighings` |
| **FG PSN** | Finished Goods PSN | `penimbangans` |
| **FG Surabaya** | Finished Goods Surabaya | `penimbangans` |
| **CS Noodle Sby** | Cold Storage Noodle Surabaya | `penimbangans` |
| **CS FG Sby** | Cold Storage FG Surabaya | `penimbangans` |
| **Incoming Singkong** | Penerimaan singkong (supplier, sopir, plat, jenis) | `incoming_singkongs` |
| **Incoming RMPM** | Penerimaan Raw Material / Packaging Material | `incoming_rmpms` |

**HMI Display** (kiosk layar penuh): 2 menu — `bahan-baku` & `formulasi`. Endpoint `live` (berat live, tanpa DB) + `print` (simpan + sync). Data disimpan ke `hmi_weighings` (tabel terpisah, pakai `uuid` untuk idempotensi).

## A.4 Alur Kerja Utama

### A.4.1 Alur Operator (kiosk penimbangan)
1. Operator login (dicek shift & `session_locked`; ditolak jika shift terkunci mid-shift).
2. Login tercatat di `login_logs` (IP, user-agent, waktu).
3. Buka dashboard → diarahkan ke kiosk modul sesuai `tipe`.
4. **Start sesi** → pilih produk/target, simpan sesi ke cache.
5. Device (Arduino) kirim berat → `POST /api/v1/{modul}/weight` → sistem tempel ke sesi operator aktif → broadcast realtime → tampil di kiosk.
6. **Next session** untuk timbang berikutnya, atau **Stop shift** untuk akhiri.

### A.4.2 Alur HMI (bahan-baku / formulasi)
1. Operator buka layar HMI full-screen.
2. Device kirim berat live → `POST /hmi-display/{menu}/live` → broadcast channel `scale.{device}` (ephemeral, tidak disimpan).
3. Operator klik **Print** → `POST /hmi-display/{menu}/print` → buat `HmiWeighing`:
   - Jika role `online`: langsung tandai `synced` + broadcast.
   - Jika role `local`: dispatch `ForwardWeighingsBatch` (kirim ke server online).

### A.4.3 Alur Sync (edge → cloud)
1. Server edge (`local`) kumpulkan `hmi_weighings` status `pending`.
2. Job `ForwardWeighingsBatch` (jalan tiap menit, `withoutOverlapping`) POST batch ke `/api/v1/sync/weighings` dengan header `X-Sync-Token`.
3. Server online (`SyncController`) upsert by `uuid` (idempoten), broadcast baris baru ke dashboard.
4. Edge tandai `synced`. Toleran downtime (job tidak throw, `$tries=1`).

### A.4.4 Alur Driver
1. Driver buka `/register-driver` (publik) → isi data → dapat QR `DRV-xxxxxxxx`.
2. Saat masuk, QR di-scan (`QrScanner.vue` kamera) → `POST /api/v1/driver/identify` → data driver muncul otomatis di form incoming.

### A.4.5 Alur Device (Arduino/ESP8266)
1. Device poll `GET /api/v1/{modul}/settings` (produk aktif, target).
2. Baca timbangan RS232 → `POST /api/v1/{modul}/weight` (auth `device_token`).
3. `GET/POST /ping` untuk heartbeat (`last_online`).
4. Endpoint legacy `/api/iot/...` tetap ada untuk device lama (jangan dihapus).

## A.5 Halaman Frontend (Vue)

| Halaman | Peran | Fungsi |
|---------|-------|--------|
| `Auth/Login` | publik | Login (root `/`) |
| `Auth/DriverRegister` | publik | Registrasi driver + tampil QR |
| `Dashboard/Admin` | admin | Statistik global, chart minggu/bulan, filter per modul |
| `Dashboard/DataView` | admin | Tabel read-only ter-filter + export |
| `Dashboard/Operator` | operator | Kiosk penimbangan realtime (Echo) |
| `Dashboard/OperatorOverview` | operator | Statistik pribadi + chart |
| `Incoming/Singkong` | operator | Kiosk penerimaan singkong |
| `Incoming/Rmpm` | operator | Kiosk penerimaan RMPM |
| `Hmi/BahanBaku` | operator | HMI kiosk bahan baku |
| `Hmi/Formulasi` | operator | HMI kiosk formulasi |
| `Admin/Master/Suppliers` | admin | Master supplier |
| `Admin/Master/Drivers` | admin | Master driver (auto-QR) |
| `Admin/Master/LoginLogs` | admin | Riwayat login |

**Layout:** `GuestLayout`, `AuthenticatedLayout` (nav + Echo), `KioskLayout` (HMI full-screen).
**Infra JS:** `echo.js` (Reverb), `composables/useLiveWeight.js` (listen `scale.{device}` + simulator demo tanpa hardware), `useRealtimeReload.js` (Inertia partial reload saat `WeightReceived`).

## A.6 Model & Tabel Data

| Tabel | Kolom kunci |
|-------|-------------|
| `users` | role, tipe, shift, shift_start/end, shift_type, session_locked |
| `penimbangans` | tanggal, produk_id, user_id, kode_produksi (unik), tanggal_expired, berat(10,3), selisih, device_id, status |
| `hmi_weighings` | uuid (unik), menu, site, device_id, user_id, operator_name, karu_name, shift, produk, nama_item, kode_batch, target, berat, unit, selisih, timbangan_ke, sync_status, synced_at, online_id |
| `incoming_singkongs` | no_surat, nama_supplier, asal, nama_sopir, nomor_plat, jenis_singkong, kode_produksi, berat, status |
| `incoming_rmpms` | tanggal_kedatangan, petugas_penerima, nama_barang, jenis_barang, asal, supplier, no_surat, sopir, plat, total_qty, kode_batch, expired_date, berat, status |
| `devices` | device_code (unik), device_name, device_token (unik), current_product_id, last_online, is_active |
| `produks` | nama_produk, target_berat |
| `drivers` | name, supplier_id, nomor_plat, qr_code (unik), asal |
| `suppliers` | name |
| `rmpm_items` | type, nama_barang (master dropdown) |
| `login_logs` | user_id, ip_address, user_agent, login_at |
| `weigh_logs` | driver_id, supplier_id, berat, device_id |

## A.7 Integrasi & Realtime

- **WebSocket (Reverb :8080):** channel `scale.{device}` (berat live), `iot-weights[.modul]` (hasil final), `iot-channel` (status legacy). Channel bersifat **publik** (model trust LAN).
- **Events:** `ScaleReading` (live, ephemeral), `WeightReceived` (hasil final, queued), `SettingsUpdated` (status legacy).
- **API IoT:** v1 (`/api/v1/{modul}`) + legacy (`/api/iot`), auth via `device_token`.
- **Swagger:** anotasi OpenAPI di semua controller IoT.
- **QR:** `simple-qrcode` + `milon/barcode`, scan kamera `QrScanner.vue`.
- **PWA:** `config/pwa.php` — "Ladang Lima Timbangan", standalone, theme #1d4ed8.
- **Docker:** nginx, PHP-FPM, MySQL, Reverb, queue worker, scheduler, Grafana, Prometheus, exporters.

## A.8 Export & Laporan

- Export **CSV** (`fputcsv` stream) dan **.xls** (HTML bergaya, dengan header supplier/kendaraan untuk incoming).
- Tersedia per modul di area admin (`/admin/{modul}/export`) dan `/export/{modul}`.

## A.9 Status / Gap (untuk perencanaan harga)

**Sudah berjalan:** semua modul penimbangan, kiosk operator, dashboard admin + chart, HMI display, sync edge→cloud, API IoT (v1 + legacy), realtime Reverb, registrasi driver + QR, login logs, export CSV/xls, monitoring Grafana/Prometheus, Docker deploy.

**Terpasang tapi BELUM diimplementasi (kandidat roadmap):**
- **Modbus TCP** (`aldas/modbus-tcp-client`) — belum dipakai di kode.
- **Web Push** (`minishlink/web-push`) — belum ada VAPID/subscription.
- **PDF** (dompdf, fpdf/fpdi) — belum dipakai (export masih CSV/xls).
- **Excel native** (`maatwebsite/excel`) — export masih HTML manual.
- **spatie/laravel-permission** — terpasang tapi otorisasi masih custom kolom `role`.

**Catatan teknis:**
- Controller Blade legacy (Penimbangan/Produk/Device CRUD) ada tanpa route aktif → device/produk dikelola via seeder/DB (belum ada UI admin).
- Channel broadcast publik (tanpa channel auth) — asumsi jaringan LAN terpercaya.
- Ada backup manual (`.zip`, `.git_backup`) di root project.

## A.10 Data Seed (referensi)

- 1 admin (`admin@test.com`), operator per modul (4 FG bershift, incoming singkong, incoming RMPM, PSN, formulasi, Surabaya, CS Noodle, CS FG). Password default `password`.
- 32 produk (target berat FG), 6 device, 19 supplier × 2 driver, master item RMPM.

---

# BAGIAN B — INTEGRASI KE APLIKASI HRGA

## B.1 Ringkasan Integrasi

**Tujuan:** Menyediakan data penimbangan, data supplier/driver, dan data penerimaan bahan dari Sistem Timbangan Digital kepada aplikasi HRGA secara real-time maupun batch.

**Domain:** `timbangan.ladanglima.com` (produksi) / `localhost:8000` (development)

## B.2 Alur Kerja Integrasi

### B.2.1 Realtime Flow — WebSocket (Push dari Timbangan ke HRGA)

```
┌──────────────┐     REST POST      ┌──────────────────┐    Broadcast     ┌──────────┐
│  Arduino/ESP  │ ────────────────►  │  Timbangan API   │ ──────────────►  │  HRGA   │
│  (Device)     │  /api/v1/...      │  (Laravel)       │  Reverb WS      │  Client  │
└──────────────┘                    └──────────────────┘                  └──────────┘
```

**Channel WebSocket:**
| Channel | Event | Payload | Keterangan |
|---------|-------|---------|------------|
| `scale.{device_code}` | `ScaleReading` | `{ device, berat, timestamp }` | Berat live (ephemeral, tidak disimpan) |
| `iot-weights` | `WeightReceived` | `{ id, modul, produk, berat, user, timestamp }` | Hasil final (disimpan ke DB) |
| `iot-weights.fg-pasuruan` | `WeightReceived` | — | Filter per modul |
| `iot-weights.fg-psn` | `WeightReceived` | — | Filter per modul |
| `iot-weights.incoming-singkong` | `WeightReceived` | — | Filter per modul |
| `iot-weights.incoming-rmpm` | `WeightReceived` | — | Filter per modul |

**Cara HRGA subscribe:**
```javascript
// Laravel Echo (JS)
Echo.channel('iot-weights')
    .listen('WeightReceived', (e) => {
        // e.modul, e.berat, eproduk, e.user_name, e.timestamp
        updateDashboard(e);
    });
```

### B.2.2 Polling/REST Flow — HRGA Ambil Data dari Timbangan

```
┌──────────┐    GET /api/v1/...    ┌──────────────────┐
│  HRGA   │ ────────────────────►  │  Timbangan API   │
│  Server  │  (token auth)         │  (Laravel)       │
└──────────┘                       └──────────────────┘
```

**Skenario penggunaan:**
1. HRGA pull data penimbangan terakhir → `GET /api/v1/{modul}/settings`
2. HRGA pull daftar supplier → `GET /api/v1/master/suppliers`
3. HRGA pull daftar driver → `GET /api/v1/master/drivers`
4. HRGA identifikasi driver via QR → `POST /api/v1/driver/identify`
5. HRGA check health server → `GET /api/v1/status`

### B.2.3 Push dari HRGA ke Timbangan (Opsional)

Jika HRGA perlu trigger aksi di Timbangan:
```
POST /api/v1/{modul}/weight   ← HRGA kirim data berat (simulasi/override)
```
> Catatan: Endpoint ini biasanya untuk device Arduino. HRGA boleh pakai dengan device token yang valid.

## B.3 Endpoint API Local (Lengkap)

### B.3.1 Health Check

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `GET` | `/api/v1/status` | — | Cek status server + waktu |

**Response 200:**
```json
{
    "status": "online",
    "version": "v1",
    "app": "Ladang Lima Timbangan",
    "server_time": "2026-08-03T10:00:00+07:00"
}
```

### B.3.2 Per Modul Penimbangan (FG Pasuruan, FG PSN, FG Surabaya, CS Noodle, CS FG)

> Pola endpoint sama untuk semua modul FG. Ganti `{modul}` dengan: `fg-pasuruan`, `fg-psn`, `fg-surabaya`, `cs-noodle-sby`, `cs-fg-sby`.

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `GET` | `/api/v1/{modul}/settings` | `device_token` | Ambil pengaturan aktif (produk, target, operator) |
| `POST` | `/api/v1/{modul}/weight` | `device_token` | Kirim data berat |
| `GET/POST` | `/api/v1/{modul}/ping` | `device_token` | Heartbeat device |

#### GET `/api/v1/fg-pasuruan/settings`

**Query:** `?token=DEV-TOKEN-FG-001`

**Response 200:**
```json
{
    "status": "ready",
    "kode_produksi": "KP-2024-001",
    "nama_produk": "Mie Goreng 85g",
    "operator": "Budi Santoso",
    "expired": "2025-12-31",
    "total_penimbangan_sesi": 5,
    "total_berat_sesi": 7500.5
}
```

#### POST `/api/v1/fg-pasuruan/weight`

**Body (form-urlencoded):**
```
token=DEV-TOKEN-FG-001
berat=25.5
kode_produksi=KP-2024-001  (opsional jika sudah ada sesi aktif)
produk_id=1                  (opsional)
```

**Response 200:**
```json
{
    "status": "success",
    "message": "Data berat 25.5 kg tersimpan",
    "record_id": 42
}
```

**Response 400:** `{ "status": "error", "message": "Berat tidak valid" }`
**Response 401:** `{ "status": "error", "message": "Token tidak valid" }`
**Response 403:** `{ "status": "error", "message": "Tidak ada operator aktif" }`

### B.3.3 Formulasi Pasuruan

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `GET` | `/api/v1/formulasi/settings` | `device_token` | Settings formulasi |
| `POST` | `/api/v1/formulasi/weight` | `device_token` | Kirim berat formulasi |
| `GET/POST` | `/api/v1/formulasi/ping` | `device_token` | Heartbeat |

**Weight Body:**
```
token=DEV-TOKEN-FORMULASI-001
weight=15.25
produk_id=5
```

### B.3.4 Incoming Singkong

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `GET` | `/api/v1/incoming-singkong/settings` | `device_token` | Settings |
| `POST` | `/api/v1/incoming-singkong/weight` | `device_token` | Kirim berat penerimaan |
| `GET/POST` | `/api/v1/incoming-singkong/ping` | `device_token` | Heartbeat |

**Weight Body:**
```
token=DEV-TOKEN-SINGKONG-001
weight=1500.0
```

### B.3.5 Incoming RMPM

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `GET` | `/api/v1/incoming-rmpm/settings` | `device_token` | Settings |
| `POST` | `/api/v1/incoming-rmpm/weight` | `device_token` | Kirim berat RMPM |
| `GET/POST` | `/api/v1/incoming-rmpm/ping` | `device_token` | Heartbeat |

### B.3.6 Driver Identification

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/api/v1/driver/identify` | — | Identifikasi driver via QR code |

**Body:**
```
qr_code=DRV-xxxxxxxx
```

**Response 200:**
```json
{
    "status": "success",
    "driver": {
        "id": 1,
        "name": "Ahmad Driver",
        "nomor_plat": "B 1234 CD",
        "supplier": "Supplier ABC",
        "asal": "Surabaya"
    }
}
```

### B.3.7 Sync (Edge → Cloud)

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| `POST` | `/api/v1/sync/weighings` | `X-Sync-Token` header | Batch sync dari server edge ke cloud |

**Header:** `X-Sync-Token: {token_dari_ONLINE_SYNC_TOKEN}`

**Body (JSON):**
```json
{
    "site": "pasuruan",
    "weighings": [
        {
            "uuid": "550e8400-e29b-41d4-a716-446655440000",
            "menu": "bahan-baku",
            "device_id": 1,
            "user_id": 5,
            "operator_name": "Operator 1",
            "karu_name": "Karu 1",
            "shift": "A",
            "produk": "Garam",
            "nama_item": "Garam Meja",
            "kode_batch": "BATCH-001",
            "target": 50,
            "berat": 50.25,
            "unit": "kg",
            "selisih": 0.25,
            "timbangan_ke": 1
        }
    ]
}
```

**Response 200:**
```json
{
    "status": "success",
    "synced": 1,
    "message": "1 records synced"
}
```

### B.3.8 Legacy Endpoints (Backward Compatible)

> Jangan dihapus — device Arduino lama masih pakai.

| Method | Endpoint | Versi |
|--------|----------|-------|
| `POST` | `/api/iot/weight` | v0 (legacy) |
| `GET` | `/api/iot/settings` | v0 (legacy) |
| `GET/POST` | `/api/iot/ping` | v0 (legacy) |
| `POST` | `/api/iot/formulasi/weight` | v0 (legacy) |
| `GET` | `/api/iot/formulasi/settings` | v0 (legacy) |
| `POST` | `/api/iot/fg-psn/weight` | v0 (legacy) |
| `GET` | `/api/iot/fg-psn/settings` | v0 (legacy) |
| `POST` | `/api/iot/fg-surabaya/weight` | v0 (legacy) |
| `GET` | `/api/iot/fg-surabaya/settings` | v0 (legacy) |
| `POST` | `/api/iot/cs-noodle-sby/weight` | v0 (legacy) |
| `GET` | `/api/iot/cs-noodle-sby/settings` | v0 (legacy) |
| `POST` | `/api/iot/cs-fg-sby/weight` | v0 (legacy) |
| `GET` | `/api/iot/cs-fg-sby/settings` | v0 (legacy) |
| `POST` | `/api/iot/incoming-singkong/weight` | v0 (legacy) |
| `GET` | `/api/iot/incoming-singkong/settings` | v0 (legacy) |
| `POST` | `/api/iot/incoming-rmpm/weight` | v0 (legacy) |
| `GET` | `/api/iot/incoming-rmpm/settings` | v0 (legacy) |
| `POST` | `/api/driver/identify` | v0 (legacy) |
| `GET` | `/api/status` | v0 (legacy) |

## B.4 Swagger / OpenAPI

### B.4.1 Akses UI

| URL | Keterangan |
|-----|------------|
| `http://localhost:8000/api/documentation` | Swagger UI (development) |
| `https://timbangan.ladanglima.com/api/documentation` | Swagger UI (produksi) |

### B.4.2 Struktur OpenAPI (l5-swagger)

**Info:**
```yaml
openapi: 3.0.0
info:
  title: Ladang Lima Timbangan API
  description: |
    API untuk integrasi Sistem Timbangan Digital.
    Menyediakan endpoint untuk:
    - Device Arduino/ESP8266 (kirim berat, settings, ping)
    - HRGA (driver identification, master data, status)
    - Sync edge-to-cloud (batch weighings)
  version: 1.0.0
  contact:
    name: Ladang Lima IT
```

**Server:**
```yaml
servers:
  - url: http://localhost:8000
    description: Development
  - url: https://timbangan.ladanglima.com
    description: Production
```

**Tags:**
```yaml
tags:
  - name: Device — FG Pasuruan
    description: Endpoint untuk device Arduino modul FG Pasuruan
  - name: Device — FG PSN
    description: Endpoint untuk device Arduino modul FG PSN
  - name: Device — FG Surabaya
    description: Endpoint untuk device Arduino modul FG Surabaya
  - name: Device — CS Noodle Sby
    description: Endpoint untuk device Arduino modul CS Noodle Surabaya
  - name: Device — CS FG Sby
    description: Endpoint untuk device Arduino modul CS FG Surabaya
  - name: Device — Formulasi
    description: Endpoint untuk device Arduino modul Formulasi
  - name: Device — Incoming Singkong
    description: Endpoint untuk device Arduino modul Incoming Singkong
  - name: Device — Incoming RMPM
    description: Endpoint untuk device Arduino modul Incoming RMPM
  - name: Driver
    description: Identifikasi driver via QR code
  - name: Sync
    description: Sinkronisasi data edge → cloud
```

### B.4.3 Schemas (Shared)

```yaml
components:
  schemas:
    WeightRequest:
      type: object
      required: [token, weight]
      properties:
        token:
          type: string
          example: "DEV-TOKEN-12345"
          description: Token unik perangkat timbangan
        weight:
          type: number
          format: float
          example: 25.5
          description: Berat timbangan dalam kilogram

    SettingsResponse:
      type: object
      properties:
        status:
          type: string
          enum: [ready, idle]
          example: ready
        kode_produksi:
          type: string
          example: KP-2024-001
        nama_produk:
          type: string
          example: Mie Goreng 85g
        operator:
          type: string
          example: Budi Santoso
        expired:
          type: string
          example: "2025-12-31"
        total_penimbangan_sesi:
          type: integer
          example: 5
        total_berat_sesi:
          type: number
          format: float
          example: 7500.5

    WeightSuccessResponse:
      type: object
      properties:
        status:
          type: string
          example: success
        message:
          type: string
          example: "Data berat 25.5 kg tersimpan"
        record_id:
          type: integer
          example: 42

    PingRequest:
      type: object
      properties:
        token:
          type: string
          example: "DEV-TOKEN-12345"

    PingResponse:
      type: object
      properties:
        status:
          type: string
          example: ok
        server_time:
          type: string
          example: "2024-01-15 08:30:00"
        total_penimbangan_sesi:
          type: integer
          example: 5
        total_berat_sesi:
          type: number
          format: float
          example: 7500.5
        berat_sebelumnya:
          type: number
          format: float
          example: 1500.0

    ErrorResponse:
      type: object
      properties:
        status:
          type: string
          example: error
        message:
          type: string
          example: "Unauthorized"

    DriverIdentifyRequest:
      type: object
      required: [qr_code]
      properties:
        qr_code:
          type: string
          example: "DRV-xxxxxxxx"
          description: QR Code driver dari registrasi

    DriverIdentifyResponse:
      type: object
      properties:
        status:
          type: string
          example: success
        driver:
          type: object
          properties:
            id:
              type: integer
            name:
              type: string
            nomor_plat:
              type: string
            supplier:
              type: string
            asal:
              type: string

    SyncWeighingsRequest:
      type: object
      required: [site, weighings]
      properties:
        site:
          type: string
          example: pasuruan
        weighings:
          type: array
          items:
            type: object
            properties:
              uuid:
                type: string
                format: uuid
              menu:
                type: string
                enum: [bahan-baku, formulasi]
              device_id:
                type: integer
              user_id:
                type: integer
              operator_name:
                type: string
              karu_name:
                type: string
              shift:
                type: string
              produk:
                type: string
              nama_item:
                type: string
              kode_batch:
                type: string
              target:
                type: number
              berat:
                type: number
              unit:
                type: string
              selisih:
                type: number
              timbangan_ke:
                type: integer

    SyncResponse:
      type: object
      properties:
        status:
          type: string
          example: success
        synced:
          type: integer
          example: 1
        message:
          type: string
          example: "1 records synced"
```

### B.4.4 Regenerate Swagger

```bash
cd Timbangan
php artisan l5-swagger:generate
```

> Swagger annotations sudah terpasang di semua controller API (`app/Http/Controllers/Api/`). Generate ulang jika ada perubahan endpoint.

## B.5 Design System

### B.5.1 Color Tokens

| Token | Light Mode | Dark Mode | Kegunaan |
|-------|-----------|-----------|----------|
| `--primary` | `#3b82f6` (blue-500) | `#60a5fa` (blue-400) | Tombol utama, link, active state |
| `--primary-foreground` | `#f8fafc` (slate-50) | `#0f172a` (slate-900) | Teks di atas primary |
| `--secondary` | `#f1f5f9` (slate-100) | `#1e293b` (slate-800) | Tombol sekunder, badge |
| `--muted` | `#f1f5f9` (slate-100) | `#1e293b` (slate-800) | Background input, disabled |
| `--muted-foreground` | `#64748b` (slate-500) | `#94a3b8` (slate-400) | Label, placeholder |
| `--destructive` | `#ef4444` (red-500) | `#7f1d1d` (red-900) | Error, hapus |
| `--success` | `#10b981` (emerald-500) | — | Status sukses |
| `--warning` | `#f59e0b` (amber-500) | — | Status peringatan |
| `--border` | `#e2e8f0` (slate-200) | `#1e293b` (slate-800) | Border card, input |
| `--background` | `#ffffff` (white) | `#0f172a` (slate-900) | Background halaman |
| `--foreground` | `#0f172a` (slate-900) | `#f8fafc` (slate-50) | Teks utama |

**Dashboard-specific (tanpa CSS variable):**
```js
dashboard: {
    primary: '#2563eb',    // blue-600
    secondary: '#3b82f6',  // blue-500
    success: '#10b981',    // emerald-500
    warning: '#f59e0b',    // amber-500
    danger: '#ef4444',     // red-500
    surface: '#ffffff',
    background: '#f8fafc', // slate-50
    text: '#0f172a',       // slate-900
    muted: '#64748b',      // slate-500
}
```

### B.5.2 Typography

| Scale | Size | Line Height | Font | Kegunaan |
|-------|------|-------------|------|----------|
| `dashboard-xs` | 12px | 16px | IBM Plex Sans | Caption, label kecil |
| `dashboard-sm` | 14px | 20px | IBM Plex Sans | Body kecil, table cell |
| `dashboard-base` | 16px | 24px | IBM Plex Sans | Body text |
| `dashboard-lg` | 20px | 28px | IBM Plex Sans | Sub-heading |
| `dashboard-xl` | 24px | 32px | IBM Plex Sans | Section heading |
| `dashboard-2xl` | 32px | 40px | IBM Plex Sans | Page title |

**Font Stack:**
- Primary: `IBM Plex Sans`, fallback `Inter`, system sans-serif
- Mono: `IBM Plex Mono`, fallback `ui-monospace`

### B.5.3 Spacing

8pt baseline grid. Kelipatan 8: `4px`, `8px`, `12px`, `16px`, `24px`, `32px`, `40px`, `48px`, `64px`.

### B.5.4 Border Radius

| Token | Value | Kegunaan |
|-------|-------|----------|
| `--radius` | `0.5rem` (8px) | Default radius |
| `rounded-lg` | `var(--radius)` | Card, modal |
| `rounded-md` | `calc(var(--radius) - 2px)` | Button, input |
| `rounded-sm` | `calc(var(--radius) - 4px)` | Badge, tag kecil |

### B.5.5 Component Inventory (shadcn-vue)

| Component | Path | Kegunaan |
|-----------|------|----------|
| `Button` | `components/ui/button` | Semua tombol |
| `Card` | `components/ui/card` | Panel data, stat card |
| `Badge` | `components/ui/badge` | Status tag (ready/idle/error) |
| `Input` | `components/ui/input` | Form input |
| `Label` | `components/ui/label` | Form label |
| `Table` | `components/ui/table` | Data tabular |
| `Alert` | `components/ui/alert` | Notifikasi error/sukses |
| `Pagination` | `components/ui/pagination` | Navigasi tabel |
| `Chart` | `components/ui/chart` | Grafik (via recharts) |

### B.5.6 Layout

| Layout | File | Kegunaan |
|--------|------|----------|
| `GuestLayout` | `Layouts/GuestLayout.vue` | Halaman publik (driver register) |
| `AuthenticatedLayout` | `Layouts/AuthenticatedLayout.vue` | Admin + Operator (nav + sidebar + Echo) |
| `KioskLayout` | `Layouts/KioskLayout.vue` | HMI full-screen kiosk |

### B.5.7 Glass Panel Effect

```css
/* Shadow + backdrop blur untuk card di dark mode */
box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
backdrop-filter: blur(8px);
```

### B.5.8 Responsive Breakpoints

| Breakpoint | Width | Kegunaan |
|-----------|-------|----------|
| `sm` | 640px | Mobile landscape |
| `md` | 768px | Tablet |
| `lg` | 1024px | Desktop |
| `xl` | 1280px | Large desktop |
| `2xl` | 1400px | Container max-width |

## B.6 Data Model (Referensi untuk HRGA)

### B.6.1 Tabel Utama

| Tabel | Kolom Kunci | Relasi |
|-------|-------------|--------|
| `users` | id, name, role (admin/operator), tipe, shift | — |
| `penimbangans` | id, tanggal, produk_id, user_id, kode_produksi (unik), berat(10,3), device_id, status | → produks, → users, → devices |
| `hmi_weighings` | id, uuid (unik), menu, site, device_id, user_id, produk, berat, sync_status | → devices, → users |
| `incoming_singkongs` | id, no_surat, nama_supplier, nama_sopir, nomor_plat, berat, status | — |
| `incoming_rmpms` | id, tanggal_kedatangan, nama_barang, supplier, sopir, berat, status | — |
| `devices` | id, device_code (unik), device_token (unik), current_product_id, last_online | → produks |
| `produks` | id, nama_produk, target_berat | — |
| `drivers` | id, name, supplier_id, nomor_plat, qr_code (unik), asal | → suppliers |
| `suppliers` | id, name | — |
| `rmpm_items` | id, type, nama_barang | — |
| `login_logs` | id, user_id, ip_address, user_agent, login_at | → users |
| `weigh_logs` | id, driver_id, supplier_id, berat, device_id | → drivers, → suppliers |

### B.6.2 Key Relationships

```
suppliers ──1:N──► drivers
suppliers ──1:N──► incoming_singkongs (via nama_supplier)
drivers   ──1:N──► weigh_logs
produks   ──1:N──► penimbangans
devices   ──1:N──► penimbangans
devices   ──1:N──► hmi_weighings
users     ──1:N──► penimbangans
users     ──1:N──► hmi_weighings
```

## B.7 Konfigurasi untuk HRGA

### B.7.1 Environment Variables

```env
# Server role (local/online)
APP_ROLE=online

# Site code (untuk identifikasi pabrik asal)
SITE_CODE=pasuruan

# WebSocket
REVERB_APP_KEY=your-reverb-key
REVERB_HOST=localhost
REVERB_PORT=8080

# Sync (jika HRGA perlu push data)
ONLINE_SYNC_URL=https://timbangan.ladanglima.com
ONLINE_SYNC_TOKEN=your-sync-token
```

### B.7.2 Config File: `config/hmi.php`

| Key | Default | Deskripsi |
|-----|---------|-----------|
| `role` | `online` | Peran server (`local` = edge, `online` = cloud) |
| `site` | `default` | Kode pabrik |
| `online.base_url` | — | URL server online (untuk sync) |
| `online.token` | — | Token autentikasi sync |
| `sync.batch_size` | `50` | Maksimal record per batch sync |
| `sync.max_batches_per_run` | `10` | Maks batch per eksekusi job |
| `sync.http_timeout` | `15` | Timeout HTTP (detik) |
| `live.throttle_ms` | `200` | Throttle broadcast berat live |

## B.8 Middleware & Autentikasi

| Middleware | Keterangan | Digunakan di |
|-----------|------------|--------------|
| `auth` | Laravel Breeze auth | Semua route web + export |
| `role:admin` | Cek `users.role === admin` | `/admin/*` |
| `role:operator` | Cek `users.role === operator` | `/operator/*`, `/hmi-display/*` |
| `shift` | `CheckShiftAccess` via `ShiftService` | Operator routes (auto logout di luar jam shift) |
| `sync.token` | `VerifySyncToken` — cek header `X-Sync-Token` | `/api/v1/sync/*` |
| `device_token` | Validasi `device_token` di query/body | Semua `/api/v1/{modul}/*` |

## B.9 Monitoring

| Service | Port | Keterangan |
|---------|------|------------|
| Grafana | `:3000` | Dashboard monitoring (akun: `admin`/`admin`) |
| Prometheus | `:9090` | Metrics scraper |
| Reverb WS | `:8080` | WebSocket server |

**Metrics yang di-scrape:**
- CPU, RAM, Disk usage (node-exporter)
- Container stats (cadvisor)
- MySQL connections, queries (mysqld-exporter)

## B.10 Catatan Teknis

1. **Channel broadcast bersifat publik** — tidak ada channel auth. Asumsi: jaringan LAN terpercaya.
2. **Device token** unik per device, disimpan di tabel `devices`. HRGA tidak perlu device token kecuali ingin simulate device.
3. **Legacy endpoints** (`/api/iot/*`) tetap harus ada untuk backward compatibility device lama.
4. **Swagger generate:** `php artisan l5-swagger:generate` — file output di `storage/api-docs/`.
5. **Docker compose** tersedia di root project `Timbangan/docker-compose.yml` untuk full stack deployment.

---

# BAGIAN C — TESTING API DENGAN POSTMAN

## C.1 Cara Import

1. Buka Postman → **File → Import**
2. Pilih file `Timbangan-Postman-Collection.json` (root project)
3. Collection **"Timbangan Digital - Local API"** muncul dengan 12 folder

## C.2 Setup Sebelum Testing

### C.2.1 Cek & Sesuaikan Collection Variables

Klik collection → tab **Variables**, sesuaikan:

| Variable | Default | Cara cek nilai asli |
|----------|---------|---------------------|
| `base_url` | `http://localhost:8000` | Sesuai port `php artisan serve` atau URL Laragon (`http://project-timbangan-digital.test`) |
| `token_fg_pasuruan` | `FG-PASURUAN-001` | Dari `database/seeders/DatabaseSeeder.php` — jalankan `php artisan migrate:fresh --seed` dulu |
| `token_fg_psn` | `FG-PSN-001` | idem |
| `token_fg_sby` | `FG-SBY-001` | idem |
| `token_formulasi` | `FORM-PASURUAN-001` | idem |
| `token_singkong` | `INC-SINGKONG-001` | idem |
| `token_rmpm` | `INC-RMPM-001` | idem |
| `token_cs_noodle` | `CS-NOODLE-SBY-001` | **Belum ada di seeder** — cek tabel `devices` atau tambah manual |
| `token_cs_fg` | `CS-FG-SBY-001` | **Belum ada di seeder** — cek tabel `devices` atau tambah manual |
| `sync_token` | `your-sync-token-here` | Dari `.env` key `ONLINE_SYNC_TOKEN` |
| `qr_code_driver` | `DRV-xxxxxxxx` | Query `SELECT qr_code FROM drivers LIMIT 1;` |

**Query cek token device:**
```bash
php artisan tinker
>>> App\Models\Device::pluck('device_token', 'device_code');
```

### C.2.2 Jalankan Server Lokal

```bash
cd Timbangan
php artisan serve
# atau via Laragon (Apache/Nginx auto)

# Pastikan queue worker & Reverb jalan jika mau test broadcast:
php artisan queue:work
php artisan reverb:start
```

## C.3 Urutan Testing yang Disarankan

1. **Health Check** — pastikan server hidup (`GET /api/v1/status` → `200 OK`)
2. **Login sebagai operator** via browser (bukan Postman) → **Start Session** di kiosk modul terkait, supaya cache `session_operator_{id}` terisi
3. **Settings** — `GET /{modul}/settings` → harus balas `status: ready` (bukan `idle`) jika sesi aktif
4. **Weight** — `POST /{modul}/weight` → cek response `200 success` + `record_id`
5. **Ping** — `POST/GET /{modul}/ping` → cek `total_penimbangan_sesi` bertambah
6. **Driver Identify** — pakai `qr_code` valid dari DB
7. **Sync** — pastikan header `X-Sync-Token` cocok `.env`, cek idempotensi dengan kirim `uuid` sama 2x (response ke-2 tetap `200` tapi tidak duplikat row)

## C.4 Error Umum & Penyebab

| Response | Penyebab | Solusi |
|----------|----------|--------|
| `401 Unauthorized` | `token` salah / device tidak ada di DB | Cek `devices.device_token` |
| `403 Tidak ada operator aktif` | Belum ada operator dengan `tipe` sesuai modul yang login & sesi aktif | Login operator via browser, start sesi kiosk |
| `400 Kode Produksi belum diinput` | Endpoint FG butuh `kode_produksi` saat tidak ada sesi cache | Isi `kode_produksi` di body atau start sesi dulu |
| `Sync — 403/419` | Header `X-Sync-Token` tidak dikirim / salah, atau `APP_ROLE` bukan `online` | Cek `.env` `APP_ROLE=online` + `ONLINE_SYNC_TOKEN` |
| Response kosong / connection refused | Server belum jalan / port salah | Cek `base_url`, jalankan `php artisan serve` |

## C.5 Testing Realtime (Opsional, di luar Postman)

Postman tidak native mendukung WebSocket subscribe untuk Laravel Echo/Pusher protocol. Untuk verifikasi broadcast:

1. Buka dashboard admin/operator via browser (sudah pakai Echo)
2. Kirim `POST /weight` via Postman
3. Amati apakah data muncul realtime di browser tanpa refresh

Alternatif: gunakan **Postman WebSocket Request** (fitur baru Postman) connect ke `ws://localhost:8080/app/{REVERB_APP_KEY}` untuk raw inspect frame Pusher-protocol (opsional, cukup teknis).

## C.6 File Terkait

| File | Lokasi | Keterangan |
|------|--------|------------|
| Postman Collection | `Timbangan-Postman-Collection.json` | Import langsung ke Postman |
| Seeder | `Timbangan/database/seeders/DatabaseSeeder.php` | Sumber device token & data awal |
| Config sync | `Timbangan/config/hmi.php` | Role, site, sync settings |
| Env contoh | `Timbangan/.env.example` | Template environment variable |
