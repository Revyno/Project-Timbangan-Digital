Create a full Laravel 12 application using Laravel Jetstream (Livewire stack) with Tailwind CSS, Alpine.js, and Flowbite UI components.

SYSTEM PURPOSE:
IoT-based weighing system (industrial production) where Arduino (ESP8266) sends weight data to Laravel API. The system manages production weighing of raw materials.

==================================================

1. AUTHENTICATION
   ==================================================

* Use Laravel Jetstream (Livewire)
* Disable registration (login only)
* Users are created using seeder only

Roles:

* admin → can view all data
* operator → can input and view their own data

==================================================
2. DATABASE STRUCTURE
=====================

Table: users

* id
* name
* email
* password
* role (admin/operator)
* shift (1,2,3)
* timestamps

Table: produks (raw materials)

* id
* nama_produk
* target_berat (decimal, optional)
* timestamps

Table: devices

* id
* device_code
* device_name
* device_token
* last_online
* is_active
* timestamps

Table: penimbangans

* id
* tanggal_penimbangan (auto use current date, no input)
* produk_id
* user_id
* kode_produksi (unique)
* tanggal_expired (manual input)
* berat (filled by Arduino)
* selisih (berat - target_berat)
* device_id
* status (menunggu, selesai, invalid)
* timestamps

==================================================
3. SEEDER DATA (IMPORTANT)
==========================

USERS (OPERATORS):

* Hamimah → shift 1
* Luknan → shift 2
* Zahra → shift 3
* Zahro → shift 1

Default password for all:
password

ADMIN:

* [admin@test.com](mailto:admin@test.com)
* password: password

PRODUKS (RAW MATERIALS):

1. Baking Powder
2. Baking Soda
3. Basil
4. Bawang Putih
5. Choco Flavor
6. Coklat Powder
7. Daun Bawang
8. DMG/GMS
9. Fibersse
10. Garam
11. Gula Kelapa
12. Gula Pasir Halus
13. Kaldu Ayam No Garam
14. Ketumbar
15. Kunir Bubuk
16. Lada Bubuk
17. Maizena
18. Minyak Goreng
19. Non Dairy Creamer
20. Pati Kentang
21. Pati Modifikasi
22. Pati Sagu
23. Softener
24. STTP
25. Tepung Beras
26. Tepung LL
27. Vanila Bubuk
28. Xantham Gum

DEVICE:

* device_code: ARD-001
* device_token: token_rahasia_ard001

==================================================
4. SYSTEM LOGIC
===============

* tanggal_penimbangan → auto (use current date)
* tanggal_expired → manual input
* shift → auto from user login (NOT input form)
* nama pegawai → auto from user login

==================================================
5. API FOR ARDUINO
==================

POST /api/iot/weight

Request JSON:
{
"token": "token_rahasia_ard001",
"kode_produksi": "KP-001",
"berat": 25.5
}

PROCESS:

* Validate device_token

* Find penimbangan:
  kode_produksi
  status = 'menunggu'

* Update:
  berat
  selisih
  device_id
  status = 'selesai'
  update last_online

Return:
{ "status": "success" }

==================================================
6. DASHBOARD (LIVEWIRE)
=======================

ADMIN:

* View all penimbangan
* Filter by tanggal, shift, produk
* Summary statistics

OPERATOR:

* Input penimbangan form:

  * produk
  * kode produksi
  * tanggal expired

AUTO:

* user_id from login
* shift from user
* tanggal_penimbangan auto

TABLE:

* Show:
  produk
  pegawai
  shift
  berat
  selisih
  status

REALTIME:

* Use wire:poll every 5 seconds

==================================================
7. UI (FLOWBITE)
================

Use:

* Cards (summary)
* Tables (data)
* Badges for status

Status color:

* menunggu → yellow
* selesai → green
* invalid → red

==================================================
8. ARDUINO CONFIG (IMPORTANT)
=============================

Replace Google Script with:

URL:
http://YOUR_IP:8000/api/iot/weight

POST JSON:

String body = "{";
body += ""token":"token_rahasia_ard001",";
body += ""kode_produksi":"KP-001",";
body += ""berat":25.5";
body += "}";

==================================================
9. TEST FLOW
============

1. Login operator (Hamimah, Luknan, etc)
2. Input penimbangan (status = menunggu)
3. Arduino send data
4. Data auto update:

   * berat masuk
   * status berubah ke selesai

==================================================
10. OUTPUT REQUIRED
===================

* Migration files
* Models (Eloquent)
* Seeder (users + produk + device)
* API Controller
* Livewire Dashboard
* Middleware role
* Blade UI (Flowbite)
* Arduino test example

END.
