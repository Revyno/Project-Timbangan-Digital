# PRD — Migrasi Sistem Timbangan Digital ke MQTT Broker (HMI Flow)

> Dokumen PRD ini menjabarkan **migrasi** dari arsitektur REST + Reverb WebSocket saat ini ke arsitektur **MQTT Broker** sebagai transport utama untuk HMI. Fokus pada alur HMI (Bahan Baku + Formulasi), pertukaran data device ↔ server, dan integrasi ke aplikasi HRGA.

---

## 1. Latar Belakang & Tujuan

### 1.1 Kondisi Saat Ini (AS-IS)

- Device (Arduino/ESP8266) → **REST POST** ke `/api/v1/{modul}/weight`
- HMI kiosk → **REST POST** ke `/hmi-display/{menu}/live` dan `/print`
- Server → **Reverb WebSocket** broadcast ke channel `scale.{device}`, `iot-weights`
- Frontend HMI → **Laravel Echo** listen event `ScaleReading`, `WeightReceived`
- Sync edge→cloud → HTTP batch job (`ForwardWeighingsBatch`)

**Masalah:**
1. REST polling boros bandwidth untuk berat live (streaming 5-10 Hz)
2. Reverb hanya untuk server→client, tidak bisa device→device
3. Tidak ada QoS/persistence bawaan — pesan hilang saat network down
4. Sulit scale ke multi-pabrik dengan latency rendah
5. Device Arduino harus buka koneksi HTTP tiap send (overhead TLS/handshake)

### 1.2 Target Setelah Migrasi (TO-BE)

- Device, HMI, Server, HRGA **semua** subscribe/publish ke MQTT broker
- Broker: **EMQX** atau **Mosquitto** (rekomendasi EMQX untuk clustering + dashboard)
- QoS 1 untuk data kritis (print/final weight), QoS 0 untuk berat live
- **Retained message** untuk state device terakhir (product aktif, operator)
- **Last Will & Testament (LWT)** untuk deteksi disconnect otomatis
- HRGA subscribe topic tertentu untuk terima data realtime tanpa polling

### 1.3 Tujuan Bisnis

| Goal | Metric | Target |
|------|--------|--------|
| Latency data device→dashboard | ms | < 100ms (P95) |
| Bandwidth device (per jam) | KB | < 50% dari REST |
| Uptime pesan | % | 99.9% (dengan QoS 1 + retained) |
| Waktu implementasi | minggu | 4-6 minggu |
| Compatibility legacy device | — | REST endpoint TETAP ada (transisi 6 bulan) |

---

## 2. Arsitektur Target

### 2.1 High-Level Diagram

```
┌──────────────┐                    ┌────────────────────┐                    ┌──────────────┐
│  Arduino /   │                    │                    │                    │  HMI Kiosk    │
│  ESP8266     │◄──── MQTT/TCP ────►│   MQTT BROKER      │◄─── MQTT/WSS ─────►│  (Vue+MQTT.js)│
│  (PubSubClient)                   │   (EMQX :1883/:8083)                    └──────────────┘
└──────────────┘                    │                    │                    ┌──────────────┐
                                    │   - QoS 0/1/2      │◄─── MQTT/TCP ─────►│  HRGA App    │
┌──────────────┐                    │   - Retained msgs  │                    │  (subscriber) │
│  Timbangan   │◄──── MQTT/TCP ────►│   - LWT            │                    └──────────────┘
│  Laravel     │                    │   - ACL + Auth     │
│  (php-mqtt)  │                    │                    │
└──────┬───────┘                    └────────────────────┘
       │
       ▼
   ┌────────┐
   │ MySQL  │  (persist PRINT + audit)
   └────────┘
```

### 2.2 Komponen

| Komponen | Peran | Library |
|----------|-------|---------|
| **MQTT Broker** | Message routing, QoS, retained, ACL | EMQX 5.x (rekomendasi) atau Mosquitto 2.x |
| **Device (Arduino)** | Publisher berat live + PRINT | `PubSubClient` (ESP8266/ESP32) |
| **HMI Frontend** | Subscribe berat live, Publish PRINT confirmation | `mqtt.js` (browser via WebSocket :8083) |
| **Laravel Backend** | Subscribe PRINT → persist DB, Publish state device | `php-mqtt/laravel-client` |
| **HRGA App** | Subscribe data final, Publish command (opsional) | Sesuai stack HRGA (mqtt.js, paho-mqtt, dll) |

### 2.3 Deployment Docker

```yaml
# docker-compose.yml (tambahan)
services:
  emqx:
    image: emqx/emqx:5.4
    ports:
      - "1883:1883"     # MQTT TCP
      - "8083:8083"     # MQTT WebSocket
      - "8883:8883"     # MQTT TLS
      - "8084:8084"     # MQTT WSS
      - "18083:18083"   # Dashboard UI
    environment:
      EMQX_NAME: emqx-ladang
      EMQX_HOST: 0.0.0.0
      EMQX_DASHBOARD__DEFAULT_PASSWORD: "your-strong-password"
    volumes:
      - emqx_data:/opt/emqx/data
      - emqx_log:/opt/emqx/log

volumes:
  emqx_data:
  emqx_log:
```

---

## 3. Topic Structure (Naming Convention)

**Format:** `ladang/{site}/{modul}/{device_code}/{action}`

- `ladang` → prefix aplikasi (untuk isolasi jika broker dipakai bareng)
- `site` → `pasuruan` | `surabaya`
- `modul` → `fg` | `formulasi` | `fg-psn` | `fg-surabaya` | `cs-noodle-sby` | `cs-fg-sby` | `incoming-singkong` | `incoming-rmpm` | `hmi-bahan-baku` | `hmi-formulasi`
- `device_code` → kode device (e.g., `SCALE-001`, `HMI-KIOSK-01`)
- `action` → `live` | `print` | `settings` | `status` | `ack`

### 3.1 Topic Table

| Topic | Direction | QoS | Retained | Payload |
|-------|-----------|-----|----------|---------|
| `ladang/{site}/{modul}/{device}/live` | Device → Broker → HMI | 0 | No | Berat live streaming |
| `ladang/{site}/{modul}/{device}/print` | HMI → Broker → Server | 1 | No | Data PRINT final |
| `ladang/{site}/{modul}/{device}/ack` | Server → Broker → HMI | 1 | No | Konfirmasi PRINT tersimpan |
| `ladang/{site}/{modul}/{device}/settings` | Server → Broker → Device | 1 | **Yes** | Produk aktif, target, operator |
| `ladang/{site}/{modul}/{device}/status` | Device → Broker → All | 1 | **Yes** | Online/offline (via LWT) |
| `ladang/{site}/{modul}/+/print` | HRGA subscribe wildcard | 1 | No | Semua PRINT di modul |
| `ladang/{site}/+/+/print` | HRGA subscribe (per site) | 1 | No | Semua PRINT di site |
| `ladang/+/+/+/print` | HRGA subscribe global | 1 | No | Semua PRINT global |
| `ladang/system/heartbeat` | Server → Broker | 0 | Yes | Health check server |

### 3.2 Wildcard Rules

- `+` = single level wildcard (e.g., `ladang/pasuruan/+/SCALE-001/live` untuk semua modul device tsb)
- `#` = multi level wildcard (e.g., `ladang/pasuruan/#` untuk semua topic pasuruan)

---

## 4. Alur Kerja HMI dengan MQTT

### 4.1 Flow HMI Bahan Baku (End-to-End)

```
┌─────────┐  1. publish live    ┌──────────┐  2. broker forward   ┌──────────┐
│ Device  │──────────────────► │  BROKER  │─────────────────────►│   HMI    │
│ SCALE-01│  QoS 0, no retain  │          │  subscribe live       │  KIOSK   │
└─────────┘                    └──────────┘                       └────┬─────┘
                                                                       │
                                                                       │ 3. operator klik PRINT
                                                                       ▼
┌─────────┐  6. publish ack     ┌──────────┐  4. publish print   ┌──────────┐
│ Server  │◄──────────────────  │  BROKER  │◄────────────────────│   HMI    │
│ Laravel │  QoS 1              │          │  QoS 1              │  KIOSK   │
└────┬────┘                     └──────────┘                     └──────────┘
     │
     │ 5. persist ke MySQL (HmiWeighing)
     ▼
 ┌────────┐
 │ MySQL  │
 └────────┘
     │
     │ 7. publish ke topic HRGA
     ▼
┌──────────┐  8. broker forward  ┌──────────┐
│  BROKER  │────────────────────►│   HRGA    │
└──────────┘  QoS 1              │  App     │
                                 └──────────┘
```

**Detail step:**

1. **Device baca berat** → publish ke `ladang/pasuruan/hmi-bahan-baku/SCALE-01/live` dengan payload `{ weight: 25.5, unit: "kg", ts: 1738000000 }` (QoS 0, non-retained)
2. **Broker route** ke semua subscriber (HMI kiosk yang subscribe topic sama)
3. **Operator klik PRINT** di HMI → HMI publish ke `ladang/pasuruan/hmi-bahan-baku/SCALE-01/print` dengan payload lengkap (QoS 1)
4. **Broker** persist di antrean (QoS 1), forward ke Laravel subscriber
5. **Laravel** terima → validasi → insert ke tabel `hmi_weighings`
6. **Laravel publish ACK** ke `ladang/pasuruan/hmi-bahan-baku/SCALE-01/ack` → HMI update UI (loading → success)
7. **Laravel publish** ke topic HRGA `ladang/pasuruan/hmi-bahan-baku/SCALE-01/print` (retained: no) — HRGA yang subscribe dapat data
8. HRGA update dashboardnya

### 4.2 Flow Settings Update

```
Admin update produk aktif di Laravel
       │
       ▼
Laravel publish ke topic settings (retained: YES)
   ladang/pasuruan/formulasi/SCALE-01/settings
   payload: { produk_id, nama_produk, target, operator, ... }
       │
       ▼
Device (Arduino) yang subscribe topic ini langsung dapat update.
Device baru yang connect kemudian langsung dapat retained message terakhir.
```

### 4.3 Flow Status Device (LWT)

```
Device connect ke broker
       │
       ▼
Register LWT (Last Will):
   Topic:   ladang/pasuruan/formulasi/SCALE-01/status
   Payload: { status: "offline", ts: X }
   QoS:     1
   Retained: true
       │
       ▼
Device publish sekarang (setelah connect):
   payload: { status: "online", ts: NOW }
       │
       ▼
Jika device disconnect abnormal (kabel putus/power off),
broker OTOMATIS publish LWT ke topic status.
       │
       ▼
HMI + Laravel + HRGA yang subscribe langsung tahu device offline.
```

---

## 5. Payload Schema (JSON)

### 5.1 Live Weight

**Topic:** `ladang/{site}/{modul}/{device}/live`
**QoS:** 0 | **Retained:** No

```json
{
    "device": "SCALE-01",
    "weight": 25.5,
    "unit": "kg",
    "ts": 1738000000
}
```

### 5.2 Print (HMI → Server)

**Topic:** `ladang/{site}/{modul}/{device}/print`
**QoS:** 1 | **Retained:** No

```json
{
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "device": "SCALE-01",
    "operator": "Budi Santoso",
    "karu": "Karu 1",
    "shift": "A",
    "menu": "bahan-baku",
    "produk": "Garam Meja",
    "nama_item": "Garam",
    "kode_batch": "BATCH-2026-001",
    "target": 50.0,
    "berat": 50.25,
    "unit": "kg",
    "timbangan_ke": 1,
    "ts": 1738000000
}
```

> `uuid` wajib — untuk **idempotensi** (server bisa detect duplikat saat re-publish akibat QoS 1 retry).

### 5.3 Ack (Server → HMI)

**Topic:** `ladang/{site}/{modul}/{device}/ack`
**QoS:** 1 | **Retained:** No

```json
{
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "success",
    "record_id": 42,
    "message": "Data tersimpan",
    "ts": 1738000005
}
```

**Error case:**
```json
{
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "error",
    "message": "Validation failed: berat must be numeric",
    "ts": 1738000005
}
```

### 5.4 Settings (Server → Device)

**Topic:** `ladang/{site}/{modul}/{device}/settings`
**QoS:** 1 | **Retained:** Yes

```json
{
    "produk_id": 1,
    "nama_produk": "Mie Goreng 85g",
    "kode_produksi": "KP-2026-001",
    "target_berat": 85.0,
    "operator": "Budi Santoso",
    "operator_id": 5,
    "shift": "A",
    "expired": "2026-12-31",
    "updated_at": 1738000000
}
```

### 5.5 Status / LWT

**Topic:** `ladang/{site}/{modul}/{device}/status`
**QoS:** 1 | **Retained:** Yes

**Online:**
```json
{
    "device": "SCALE-01",
    "status": "online",
    "ip": "192.168.1.50",
    "fw_version": "1.2.3",
    "ts": 1738000000
}
```

**Offline (auto-publish oleh broker via LWT):**
```json
{
    "device": "SCALE-01",
    "status": "offline",
    "ts": 1738000000
}
```

---

## 6. Autentikasi & Otorisasi

### 6.1 Authentication

**Metode:** Username + Password (Client ID unique) — didaftarkan di EMQX dashboard atau via API.

| Client | Client ID | Username | Password | Role |
|--------|-----------|----------|----------|------|
| Device Arduino | `device-scale-01` | `device_scale_01` | `{token_dari_devices.device_token}` | device |
| HMI Kiosk | `hmi-kiosk-01` | `hmi_kiosk_01` | `{env_HMI_MQTT_PASS}` | hmi |
| Laravel Backend | `laravel-server` | `laravel` | `{env_LARAVEL_MQTT_PASS}` | server |
| HRGA App | `hrga-app` | `hrga` | `{env_HRGA_MQTT_PASS}` | subscriber |

### 6.2 ACL (Access Control List)

**EMQX ACL Rules (contoh `acl.conf`):**
```
%% Device hanya boleh publish ke topic device-nya sendiri
{allow, {user, "device_scale_01"}, publish, ["ladang/+/+/SCALE-01/live", "ladang/+/+/SCALE-01/status"]}.
{allow, {user, "device_scale_01"}, subscribe, ["ladang/+/+/SCALE-01/settings"]}.

%% HMI boleh subscribe live + publish print + subscribe ack
{allow, {user, "hmi_kiosk_01"}, subscribe, ["ladang/+/+/+/live", "ladang/+/+/+/ack"]}.
{allow, {user, "hmi_kiosk_01"}, publish, ["ladang/+/+/+/print"]}.

%% Laravel server: full access
{allow, {user, "laravel"}, pubsub, ["ladang/#"]}.

%% HRGA: read-only subscribe
{allow, {user, "hrga"}, subscribe, ["ladang/#"]}.
{deny, {user, "hrga"}, publish, ["#"]}.

%% Default deny
{deny, all}.
```

### 6.3 Transport Security

| Env | Port | Protocol | Cert |
|-----|------|----------|------|
| Development | `1883` | MQTT plain | — |
| Development | `8083` | MQTT over WebSocket | — |
| Production | `8883` | MQTT over TLS | Let's Encrypt |
| Production | `8084` | MQTT over WSS | Let's Encrypt |

---

## 7. Perubahan Kode (Migration Plan)

### 7.1 Laravel Backend

**Install package:**
```bash
composer require php-mqtt/laravel-client
php artisan vendor:publish --provider="PhpMqtt\Client\MqttClientServiceProvider" --tag="config"
```

**Config `config/mqtt-client.php`:**
```php
return [
    'default_connection' => 'default',
    'connections' => [
        'default' => [
            'host' => env('MQTT_HOST', '127.0.0.1'),
            'port' => (int) env('MQTT_PORT', 1883),
            'protocol' => \PhpMqtt\Client\ConnectionSettings::MQTT_3_1_1,
            'client_id' => env('MQTT_CLIENT_ID', 'laravel-server'),
            'use_clean_session' => false,  // persistent session
            'auth' => [
                'username' => env('MQTT_USERNAME'),
                'password' => env('MQTT_PASSWORD'),
            ],
            'last_will' => [
                'topic' => 'ladang/system/laravel/status',
                'message' => json_encode(['status' => 'offline']),
                'quality_of_service' => 1,
                'retain' => true,
            ],
        ],
    ],
];
```

**New Service: `app/Services/MqttBroker.php`**
```php
<?php
namespace App\Services;

use PhpMqtt\Client\Facades\MQTT;

class MqttBroker
{
    public static function publishSettings(string $site, string $modul, string $device, array $payload): void
    {
        MQTT::publish(
            "ladang/{$site}/{$modul}/{$device}/settings",
            json_encode($payload),
            1,      // QoS
            true    // retained
        );
    }

    public static function publishAck(string $site, string $modul, string $device, array $payload): void
    {
        MQTT::publish(
            "ladang/{$site}/{$modul}/{$device}/ack",
            json_encode($payload),
            1,
            false
        );
    }

    public static function forwardPrintToHrga(string $site, string $modul, string $device, array $payload): void
    {
        // Same topic — HRGA subscribe wildcard
        MQTT::publish(
            "ladang/{$site}/{$modul}/{$device}/print",
            json_encode($payload),
            1,
            false
        );
    }
}
```

**New Command: `app/Console/Commands/MqttSubscriber.php`**
```php
<?php
namespace App\Console\Commands;

use App\Models\HmiWeighing;
use App\Services\MqttBroker;
use Illuminate\Console\Command;
use PhpMqtt\Client\Facades\MQTT;

class MqttSubscriber extends Command
{
    protected $signature = 'mqtt:subscribe';
    protected $description = 'Subscribe topic MQTT untuk terima PRINT dari HMI';

    public function handle(): void
    {
        $mqtt = MQTT::connection();

        // Subscribe semua PRINT (wildcard)
        $mqtt->subscribe('ladang/+/+/+/print', function ($topic, $message) {
            $data = json_decode($message, true);
            $parts = explode('/', $topic);  // ladang/{site}/{modul}/{device}/print
            [, $site, $modul, $device] = $parts;

            // Idempotensi via uuid
            $weighing = HmiWeighing::updateOrCreate(
                ['uuid' => $data['uuid']],
                [
                    'menu' => $data['menu'],
                    'site' => $site,
                    'operator_name' => $data['operator'],
                    'karu_name' => $data['karu'] ?? null,
                    'shift' => $data['shift'] ?? null,
                    'produk' => $data['produk'] ?? null,
                    'nama_item' => $data['nama_item'],
                    'kode_batch' => $data['kode_batch'] ?? null,
                    'target' => $data['target'] ?? null,
                    'berat' => $data['berat'],
                    'unit' => $data['unit'] ?? 'kg',
                    'timbangan_ke' => $data['timbangan_ke'] ?? 1,
                    'sync_status' => 'synced',
                    'synced_at' => now(),
                    'tanggal' => now()->toDateString(),
                ]
            );

            // Ack ke HMI
            MqttBroker::publishAck($site, $modul, $device, [
                'uuid' => $data['uuid'],
                'status' => 'success',
                'record_id' => $weighing->id,
                'message' => 'Data tersimpan',
                'ts' => now()->timestamp,
            ]);

            // Forward ke HRGA (jika perlu — sudah otomatis via subscribe wildcard)
        }, 1);

        // Loop
        $mqtt->loop(true);
    }
}
```

**Register di scheduler (`app/Console/Kernel.php`):**
```php
protected function schedule(Schedule $schedule): void
{
    // Jalankan subscriber sebagai daemon (dikelola supervisor)
    $schedule->command('mqtt:subscribe')->everyMinute()->withoutOverlapping();
}
```

**Supervisor config (`/etc/supervisor/conf.d/mqtt-subscriber.conf`):**
```ini
[program:mqtt-subscriber]
process_name=%(program_name)s
command=php /var/www/timbangan/artisan mqtt:subscribe
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/mqtt-subscriber.log
```

### 7.2 HMI Frontend (Vue)

**Install:**
```bash
npm install mqtt
```

**New composable: `resources/js/composables/useMqtt.js`**
```javascript
import mqtt from 'mqtt';
import { ref, onMounted, onUnmounted } from 'vue';

export function useMqtt(options = {}) {
    const client = ref(null);
    const connected = ref(false);
    const liveWeight = ref(0);
    const lastAck = ref(null);

    const {
        broker = 'ws://localhost:8083/mqtt',
        username,
        password,
        clientId = `hmi-${Math.random().toString(16).slice(2, 10)}`,
        site = 'pasuruan',
        modul,
        device,
    } = options;

    onMounted(() => {
        client.value = mqtt.connect(broker, {
            clientId,
            username,
            password,
            clean: true,
            reconnectPeriod: 3000,
        });

        client.value.on('connect', () => {
            connected.value = true;
            // Subscribe live + ack
            client.value.subscribe(`ladang/${site}/${modul}/${device}/live`, { qos: 0 });
            client.value.subscribe(`ladang/${site}/${modul}/${device}/ack`, { qos: 1 });
        });

        client.value.on('message', (topic, payload) => {
            const data = JSON.parse(payload.toString());
            if (topic.endsWith('/live')) liveWeight.value = data.weight;
            if (topic.endsWith('/ack')) lastAck.value = data;
        });
    });

    onUnmounted(() => client.value?.end());

    function publishPrint(data) {
        const uuid = crypto.randomUUID();
        client.value.publish(
            `ladang/${site}/${modul}/${device}/print`,
            JSON.stringify({ ...data, uuid, ts: Date.now() / 1000 }),
            { qos: 1 }
        );
        return uuid;
    }

    return { connected, liveWeight, lastAck, publishPrint };
}
```

**Update HMI page (`resources/js/Pages/Hmi/BahanBaku.vue`):**
```vue
<script setup>
import { useMqtt } from '@/composables/useMqtt';

const { connected, liveWeight, lastAck, publishPrint } = useMqtt({
    broker: import.meta.env.VITE_MQTT_BROKER,
    username: import.meta.env.VITE_MQTT_USERNAME,
    password: import.meta.env.VITE_MQTT_PASSWORD,
    site: 'pasuruan',
    modul: 'hmi-bahan-baku',
    device: 'HMI-KIOSK-01',
});

function onPrint() {
    publishPrint({
        operator: 'Budi',
        menu: 'bahan-baku',
        nama_item: 'Garam',
        berat: liveWeight.value,
        target: 50,
        unit: 'kg',
        timbangan_ke: 1,
    });
}
</script>
```

### 7.3 Arduino / ESP8266

**Library:** `PubSubClient` (Nick O'Leary)

```cpp
#include <ESP8266WiFi.h>
#include <PubSubClient.h>

const char* ssid = "WIFI-LADANG";
const char* pass = "wifi-password";
const char* mqtt_server = "192.168.1.10";
const int   mqtt_port = 1883;
const char* mqtt_user = "device_scale_01";
const char* mqtt_pass = "DEV-TOKEN-XXX";
const char* client_id = "device-scale-01";

WiFiClient espClient;
PubSubClient mqtt(espClient);

const char* T_LIVE     = "ladang/pasuruan/hmi-bahan-baku/SCALE-01/live";
const char* T_STATUS   = "ladang/pasuruan/hmi-bahan-baku/SCALE-01/status";
const char* T_SETTINGS = "ladang/pasuruan/hmi-bahan-baku/SCALE-01/settings";

void onMessage(char* topic, byte* payload, unsigned int length) {
    if (strcmp(topic, T_SETTINGS) == 0) {
        // Parse payload -> update produk aktif
    }
}

void connectMqtt() {
    while (!mqtt.connected()) {
        // Connect with LWT
        if (mqtt.connect(client_id, mqtt_user, mqtt_pass,
                         T_STATUS, 1, true,
                         "{\"status\":\"offline\"}")) {
            // Publish online status
            mqtt.publish(T_STATUS, "{\"status\":\"online\"}", true);
            mqtt.subscribe(T_SETTINGS, 1);
        } else {
            delay(2000);
        }
    }
}

void setup() {
    WiFi.begin(ssid, pass);
    mqtt.setServer(mqtt_server, mqtt_port);
    mqtt.setCallback(onMessage);
    mqtt.setKeepAlive(60);
}

void loop() {
    if (!mqtt.connected()) connectMqtt();
    mqtt.loop();

    static unsigned long lastSend = 0;
    if (millis() - lastSend > 200) {  // 5 Hz
        float weight = readScale();
        char payload[80];
        snprintf(payload, sizeof(payload),
                 "{\"weight\":%.2f,\"unit\":\"kg\",\"ts\":%lu}",
                 weight, millis() / 1000);
        mqtt.publish(T_LIVE, payload, false);  // QoS 0, non-retained
        lastSend = millis();
    }
}
```

### 7.4 HRGA Integration

**Subscribe pattern (HRGA app subscribe wildcard):**
```javascript
// Node.js example (HRGA backend)
const mqtt = require('mqtt');
const client = mqtt.connect('mqtt://timbangan.ladanglima.com:1883', {
    username: 'hrga',
    password: process.env.MQTT_HRGA_PASS,
});

client.on('connect', () => {
    // Subscribe semua PRINT global
    client.subscribe('ladang/+/+/+/print', { qos: 1 });
    // Subscribe status semua device
    client.subscribe('ladang/+/+/+/status', { qos: 1 });
});

client.on('message', (topic, payload) => {
    const data = JSON.parse(payload.toString());
    const [, site, modul, device, action] = topic.split('/');

    if (action === 'print') {
        // Update dashboard HRGA
        saveToHrgaDatabase({ site, modul, device, ...data });
    }

    if (action === 'status') {
        // Update device status di HRGA
        updateDeviceStatus(device, data.status);
    }
});
```

---

## 8. Environment Variables

```env
# MQTT Broker
MQTT_HOST=127.0.0.1
MQTT_PORT=1883
MQTT_CLIENT_ID=laravel-server
MQTT_USERNAME=laravel
MQTT_PASSWORD=your-strong-password

# Frontend (expose via VITE_)
VITE_MQTT_BROKER=ws://localhost:8083/mqtt
VITE_MQTT_USERNAME=hmi_kiosk_01
VITE_MQTT_PASSWORD=hmi-password

# Site
SITE_CODE=pasuruan
```

---

## 9. Testing Plan

### 9.1 Unit Test

- `MqttBroker` service — mock publish, assert topic + payload
- `MqttSubscriber` command — mock message, assert `HmiWeighing` created dengan uuid unique

### 9.2 Integration Test

- **Loopback test:** Spin up EMQX di Docker, publish di test → assert subscriber terima
- **Idempotensi:** Publish print sama 3x → assert hanya 1 row di DB (via uuid)
- **QoS 1 retry:** Simulate broker down 5 detik → assert message tetap delivered saat broker up

### 9.3 Load Test

- Tool: `mqtt-benchmark` atau `EMQX MQTT-X CLI`
- Target: 100 device concurrent, 5 Hz publish live, 1 print/menit
- Metric: Broker CPU < 50%, latency P95 < 100ms

### 9.4 Failover Test

- Kill device → assert LWT publish `offline` dalam < 60 detik (keep-alive interval)
- Kill Laravel subscriber → assert broker queue message (QoS 1), replay saat subscriber up

---

## 10. Rollout Plan

| Phase | Durasi | Aktivitas |
|-------|--------|-----------|
| **Phase 1: Setup** | 1 minggu | Install EMQX broker, config ACL, buat kredensial |
| **Phase 2: Backend** | 1 minggu | Implementasi `MqttBroker` + `MqttSubscriber` di Laravel, testing di dev |
| **Phase 3: Device** | 1-2 minggu | Update firmware 1-2 device pilot, testing dual-stack (REST + MQTT) |
| **Phase 4: HMI** | 1 minggu | Update Vue kiosk pakai `mqtt.js`, testing di 1 kiosk |
| **Phase 5: HRGA** | 1 minggu | Integrasi HRGA subscribe topic, testing E2E |
| **Phase 6: Rollout** | 1-2 minggu | Deploy bertahap ke semua device (10-20% per hari) |
| **Phase 7: Deprecation** | 6 bulan | Legacy REST tetap ada, monitor traffic, deprecate setelah 0 device pakai |

### 10.1 Rollback Strategy

- Device: firmware lama tetap disimpan, OTA rollback via ESP8266 OTA update
- HMI: feature flag `USE_MQTT=false` fallback ke Echo lama
- Backend: REST endpoint TETAP ada — tidak ada breaking change

---

## 11. Monitoring

### 11.1 EMQX Dashboard

- URL: `http://localhost:18083`
- Metrics: connected clients, messages/sec, subscription count, retained messages

### 11.2 Prometheus Exporter

**EMQX built-in metrics:** `http://localhost:18083/api/v5/prometheus/stats`

**Add ke Prometheus scrape config:**
```yaml
scrape_configs:
  - job_name: emqx
    static_configs:
      - targets: ['emqx:18083']
    metrics_path: /api/v5/prometheus/stats
```

### 11.3 Grafana Dashboard

Import dashboard ID `17446` (EMQX 5.x official).

### 11.4 Alert Rules

| Alert | Threshold | Aksi |
|-------|-----------|------|
| Broker down | uptime 0 | PagerDuty |
| Connected clients drop > 30% | 5 menit | Slack warning |
| Message queue > 10.000 | 2 menit | Slack critical |
| Retained messages > 1.000 | — | Cleanup script |

---

## 12. Perbandingan AS-IS vs TO-BE

| Aspek | AS-IS (REST + Reverb) | TO-BE (MQTT) |
|-------|----------------------|--------------|
| Transport device→server | HTTP POST | MQTT PUBLISH |
| QoS/reliability | Manual retry app-level | Built-in QoS 0/1/2 |
| Bandwidth (5 Hz live) | ~2 KB/req × 5 = 10 KB/s | ~80 bytes × 5 = 400 B/s |
| Latency (P95) | 200-500ms | 20-80ms |
| Connection overhead | TLS handshake per req | Persistent connection |
| Broadcast device→device | Tidak bisa | Native (pub/sub) |
| Offline resilience | Data hilang | Queued (QoS 1) + retained |
| State device terakhir | Query DB | Retained message (instan) |
| Auto-detect disconnect | Timeout heuristic | LWT (built-in) |
| Multi-subscriber | Custom broadcast code | Native wildcard subscribe |
| Scaling | Horizontal Laravel + Reverb sticky | EMQX cluster (built-in) |

---

## 13. Risiko & Mitigasi

| Risiko | Dampak | Mitigasi |
|--------|--------|----------|
| Broker single point of failure | Semua device offline | EMQX cluster 3-node (production) |
| Device firmware bug post-deploy | Sebagian device tidak kirim data | OTA rollback + phased rollout |
| ACL misconfig → unauthorized publish | Data pollution | Test ACL di staging + audit log EMQX |
| Message flood dari device malfungsi | Broker overload | Rate limit per client (EMQX built-in) |
| HRGA subscribe wildcard `#` → overload | HRGA app crash | Subscribe filtered (`ladang/+/+/+/print` saja) |
| Payload JSON schema drift | Consumer error | Versioning topic: `ladang/v1/{site}/...` |

---

## 14. Referensi

- **EMQX docs:** https://www.emqx.io/docs/en/v5.4/
- **PhpMqtt Laravel Client:** https://github.com/php-mqtt/laravel-client
- **MQTT.js (Vue):** https://github.com/mqttjs/MQTT.js
- **PubSubClient (Arduino):** https://github.com/knolleary/pubsubclient
- **MQTT 5.0 spec:** https://docs.oasis-open.org/mqtt/mqtt/v5.0/mqtt-v5.0.html
