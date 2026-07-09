<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Peran Server (Store-and-Forward)
    |--------------------------------------------------------------------------
    |
    | 'local'  = server edge di pabrik (on-prem). Menerima PRINT dari HMI,
    |            menyimpan ke DB lokal, lalu mem-forward BATCH ke server online.
    | 'online' = server cloud/VPS. Menerima sync dari server lokal, meng-upsert,
    |            lalu broadcast ke dashboard via Reverb.
    |
    | Deployment satu-mesin (all-in-one) cukup pakai 'online': PRINT langsung
    | disimpan + broadcast tanpa perlu forward.
    |
    */
    'role' => env('APP_ROLE', 'online'),

    // Kode situs/pabrik pengirim (dipakai server online membedakan asal data).
    'site' => env('SITE_CODE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Target Server Online (dipakai saat role = local)
    |--------------------------------------------------------------------------
    */
    'online' => [
        'base_url' => rtrim((string) env('ONLINE_SYNC_URL', ''), '/'),
        'token'    => env('ONLINE_SYNC_TOKEN', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sinkronisasi (anti "server meledak")
    |--------------------------------------------------------------------------
    |
    | batch_size          : maksimal baris pending yang dikirim per HTTP call.
    | max_batches_per_run : batas jumlah batch per eksekusi job (mencegah loop
    |                       tak berujung; sisanya diproses run berikutnya).
    | http_timeout        : timeout (detik) tiap POST ke server online.
    |
    */
    'sync' => [
        'batch_size'          => (int) env('SYNC_BATCH_SIZE', 50),
        'max_batches_per_run' => (int) env('SYNC_MAX_BATCHES', 10),
        'http_timeout'        => (int) env('SYNC_HTTP_TIMEOUT', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Live Weight (ephemeral — tidak disimpan, tidak di-forward)
    |--------------------------------------------------------------------------
    |
    | throttle_ms : jarak minimum antar broadcast berat live per device,
    |               untuk mencegah membanjiri Reverb saat timbangan streaming.
    |
    */
    'live' => [
        'throttle_ms' => (int) env('HMI_LIVE_THROTTLE_MS', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Master Data Grid (fallback bila DB kosong)
    |--------------------------------------------------------------------------
    |
    | Daftar bahan untuk menu "Bahan Baku" mengikuti mockup HMI. Sumber utama
    | tetap App\Models\IncomingRmpm::namaBarangOptions(); ini fallback + urutan
    | tampilan HMI. Produk formulasi sumber utamanya tabel `produks`.
    |
    */
    'materials' => [
        'Garam', 'Gula Pasir', 'T. LL', 'T. Beras', 'T. Maizena',
        'Coconut', 'Gula palm', 'Mentega', 'Coklat Blok', 'Coklat putih Block',
        'Keju Parmesan', 'Matcha Powder', 'Keju Putih', 'Gula Halus', 'Flaxide',
    ],

    'products' => [
        'Cookies Blackmond', 'Cookies Blackthins', 'Cookies Pumberry',
        'Cheese Thins', 'Matcha Thins', 'Noodle Korean', 'Noodle Beet',
        'Noodle Ayam Bawang', 'Pasta', 'Bumbu Minyak Korea',
        'Bumbu Powder Korean', 'Bumbu Flake Korean', 'Minyak Ayam Bawang',
        'Bumbu Cabe Bubuk', 'Bawang Goreng',
    ],

    // Menu HMI yang valid (dipakai validasi route/controller).
    'menus' => ['bahan-baku', 'formulasi'],
];
