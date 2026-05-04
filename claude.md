Sistem digunakan untuk industri dengan multi lokasi (Surabaya dan Pasuruan).
tanbahkan dari sistem yang sudah di buaat saat ini tidak perlu ubah terlalu banyak hany yang di minta saja dan sesuaikan dengan kebutuhan say

Fokus utama:

Mengambil data berat timbangan secara realtime
Minim input manual dari operator
Menggunakan QR Code untuk identifikasi sopir

========================================

FITUR UTAMA:

MASTER DATA:
Suppliers (id, name)
Drivers (id, name, supplier_id, qr_code unik)

Catatan:

QR Code hanya berisi kode unik (UUID atau random string)
Tidak menyimpan nama atau data sensitif di QR

========================================

FLOW TIMBANGAN:

Langkah proses:

Operator membuka halaman scan (PWA)
Scan QR driver
Sistem otomatis:
Ambil data driver
Ambil supplier
Tampilkan di UI (tanpa input manual)
Operator klik tombol "START"
Sistem menunggu data dari timbangan (ESP8266)
ESP8266 mengirim data berat ke API Laravel
Sistem langsung menyimpan data ke database (tanpa proses transaksi panjang)

========================================

DATABASE (SIMPLE)

Tabel utama:

drivers:
id, name, supplier_id, qr_code
suppliers:
id, name
devices:
id, device_code, device_token, location_id
weigh_logs:
id
driver_id
supplier_id
berat
device_id
created_at

Catatan:

Tidak perlu tabel transaksi kompleks
Fokus ke log data timbang saja

========================================

OPERATOR PAGE (WORKSTATION)

Komponen:

Tombol Scan QR
Display:
Nama Sopir
Supplier
Tombol START
Display berat realtime
Auto reset setelah selesai

Behavior:

Scan → auto isi data
START → sistem siap menerima data
Data berat masuk → langsung disimpan

========================================

API ESP8266

Endpoint:
POST /api/weight

Header:

Authorization: device_token

Body:
{
"berat": 1250
}

Logic:

Validasi device_token
Ambil session timbang aktif
Simpan ke weigh_logs
Broadcast realtime ke frontend

========================================

REALTIME

Gunakan Laravel Reverb untuk:

Update berat secara live di dashboard operator
Monitoring oleh admin

========================================

VALIDASI
QR tidak valid → tolak
Device tidak valid → tolak
Harus ada session aktif sebelum simpan berat

========================================

TUJUAN:

Membuat sistem timbangan digital yang:

Cepat
Minim input manual
Akurat
Siap digunakan di industri
Mudah dikembangkan ke fitur lanjutan