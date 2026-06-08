<?php

use App\Http\Controllers\Api\IotController;
use App\Http\Controllers\Api\FgPsnIotController;
use App\Http\Controllers\Api\FgSurabayaIotController;
use App\Http\Controllers\Api\CsNoodleSbyIotController;
use App\Http\Controllers\Api\CsFgSbyIotController;
use App\Http\Controllers\Api\IncomingSingkongIotController;
use App\Http\Controllers\Api\IncomingRmpmIotController;
use App\Http\Controllers\Api\DriverController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Arduino / IoT Integration
|--------------------------------------------------------------------------
|
| Semua route IoT menggunakan prefix /api/v1/ (versi baru).
| Route lama /api/iot/... tetap ada untuk backward compatibility
| dengan perangkat Arduino/ESP8266 yang sudah terpasang.
|
*/

// ===========================================================================
// API v1 — Route Baru (Rekomendasi untuk penggunaan baru)
// ===========================================================================

// ── FG Pasuruan (Formulasi) ─────────────────────────────────────────────────
Route::prefix('v1/fg-pasuruan')->group(function () {
    Route::get('/settings', [IotController::class, 'getSettings'])->name('v1.fg-pasuruan.settings');
    Route::post('/weight', [IotController::class, 'receiveWeight'])->name('v1.fg-pasuruan.weight');
    Route::match(['get', 'post'], '/ping', [IotController::class, 'ping'])->name('v1.fg-pasuruan.ping');
    Route::post('/device/update-product', [IotController::class, 'updateDeviceProduct'])->name('v1.fg-pasuruan.device.update-product');
});

// ── FG PSN (Finished Goods Pasuruan) ────────────────────────────────────────
Route::prefix('v1/fg-psn')->group(function () {
    Route::get('/settings', [FgPsnIotController::class, 'getSettings'])->name('v1.fg-psn.settings');
    Route::post('/weight', [FgPsnIotController::class, 'receiveWeight'])->name('v1.fg-psn.weight');
    Route::match(['get', 'post'], '/ping', [FgPsnIotController::class, 'ping'])->name('v1.fg-psn.ping');
});

// ── FG Surabaya ──────────────────────────────────────────────────────────────
Route::prefix('v1/fg-surabaya')->group(function () {
    Route::get('/settings', [FgSurabayaIotController::class, 'getSettings'])->name('v1.fg-surabaya.settings');
    Route::post('/weight', [FgSurabayaIotController::class, 'receiveWeight'])->name('v1.fg-surabaya.weight');
    Route::match(['get', 'post'], '/ping', [FgSurabayaIotController::class, 'ping'])->name('v1.fg-surabaya.ping');
});

// ── CS Noodle Surabaya ───────────────────────────────────────────────────────
Route::prefix('v1/cs-noodle-sby')->group(function () {
    Route::get('/settings', [CsNoodleSbyIotController::class, 'getSettings'])->name('v1.cs-noodle-sby.settings');
    Route::post('/weight', [CsNoodleSbyIotController::class, 'receiveWeight'])->name('v1.cs-noodle-sby.weight');
    Route::match(['get', 'post'], '/ping', [CsNoodleSbyIotController::class, 'ping'])->name('v1.cs-noodle-sby.ping');
});

// ── CS FG Surabaya ───────────────────────────────────────────────────────────
Route::prefix('v1/cs-fg-sby')->group(function () {
    Route::get('/settings', [CsFgSbyIotController::class, 'getSettings'])->name('v1.cs-fg-sby.settings');
    Route::post('/weight', [CsFgSbyIotController::class, 'receiveWeight'])->name('v1.cs-fg-sby.weight');
    Route::match(['get', 'post'], '/ping', [CsFgSbyIotController::class, 'ping'])->name('v1.cs-fg-sby.ping');
});

// ── Incoming Singkong ─────────────────────────────────────────────────────────
Route::prefix('v1/incoming-singkong')->group(function () {
    Route::get('/settings', [IncomingSingkongIotController::class, 'getSettings'])->name('v1.incoming-singkong.settings');
    Route::post('/weight', [IncomingSingkongIotController::class, 'receiveWeight'])->name('v1.incoming-singkong.weight');
    Route::match(['get', 'post'], '/ping', [IncomingSingkongIotController::class, 'ping'])->name('v1.incoming-singkong.ping');
});

// ── Incoming RMPM ─────────────────────────────────────────────────────────────
Route::prefix('v1/incoming-rmpm')->group(function () {
    Route::get('/settings', [IncomingRmpmIotController::class, 'getSettings'])->name('v1.incoming-rmpm.settings');
    Route::post('/weight', [IncomingRmpmIotController::class, 'receiveWeight'])->name('v1.incoming-rmpm.weight');
    Route::match(['get', 'post'], '/ping', [IncomingRmpmIotController::class, 'ping'])->name('v1.incoming-rmpm.ping');
});

// ── Driver Identification ─────────────────────────────────────────────────────
Route::post('/v1/driver/identify', [DriverController::class, 'identify'])->name('v1.driver.identify');

// ── Health Check ──────────────────────────────────────────────────────────────
Route::get('/v1/status', function () {
    return response()->json([
        'status'      => 'online',
        'version'     => 'v1',
        'app'         => config('app.name'),
        'server_time' => now()->toIso8601String(),
    ]);
})->name('v1.status');


// ===========================================================================
// LEGACY — Route Lama (Backward compatible untuk perangkat Arduino lama)
// Jangan dihapus agar perangkat yang sudah terpasang tidak terputus.
// ===========================================================================

// FG Pasuruan (legacy)
Route::prefix('iot')->group(function () {
    Route::post('/weight', [IotController::class, 'receiveWeight']);
    Route::get('/settings', [IotController::class, 'getSettings']);
    Route::post('/ping', [IotController::class, 'ping']);
    Route::get('/ping',  [IotController::class, 'ping']);
    Route::post('/device/update-product', [IotController::class, 'updateDeviceProduct']);
});

// Driver identification (legacy)
Route::post('/driver/identify', [DriverController::class, 'identify']);

// FG PSN (legacy)
Route::prefix('iot/fg-psn')->group(function () {
    Route::get('/settings', [FgPsnIotController::class, 'getSettings']);
    Route::post('/weight', [FgPsnIotController::class, 'receiveWeight']);
    Route::post('/ping', [FgPsnIotController::class, 'ping']);
    Route::get('/ping', [FgPsnIotController::class, 'ping']);
});

// FG Surabaya (legacy)
Route::prefix('iot/fg-surabaya')->group(function () {
    Route::get('/settings', [FgSurabayaIotController::class, 'getSettings']);
    Route::post('/weight', [FgSurabayaIotController::class, 'receiveWeight']);
    Route::post('/ping', [FgSurabayaIotController::class, 'ping']);
    Route::get('/ping', [FgSurabayaIotController::class, 'ping']);
});

// CS Noodle Surabaya (legacy)
Route::prefix('iot/cs-noodle-sby')->group(function () {
    Route::get('/settings', [CsNoodleSbyIotController::class, 'getSettings']);
    Route::post('/weight', [CsNoodleSbyIotController::class, 'receiveWeight']);
    Route::post('/ping', [CsNoodleSbyIotController::class, 'ping']);
    Route::get('/ping', [CsNoodleSbyIotController::class, 'ping']);
});

// CS FG-Sby Surabaya (legacy)
Route::prefix('iot/cs-fg-sby')->group(function () {
    Route::get('/settings', [CsFgSbyIotController::class, 'getSettings']);
    Route::post('/weight', [CsFgSbyIotController::class, 'receiveWeight']);
    Route::post('/ping', [CsFgSbyIotController::class, 'ping']);
    Route::get('/ping', [CsFgSbyIotController::class, 'ping']);
});

// Incoming Singkong (legacy)
Route::prefix('iot/incoming-singkong')->group(function () {
    Route::get('/settings', [IncomingSingkongIotController::class, 'getSettings']);
    Route::post('/weight', [IncomingSingkongIotController::class, 'receiveWeight']);
    Route::post('/ping', [IncomingSingkongIotController::class, 'ping']);
    Route::get('/ping', [IncomingSingkongIotController::class, 'ping']);
});

// Incoming RMPM (legacy)
Route::prefix('iot/incoming-rmpm')->group(function () {
    Route::get('/settings', [IncomingRmpmIotController::class, 'getSettings']);
    Route::post('/weight', [IncomingRmpmIotController::class, 'receiveWeight']);
    Route::post('/ping', [IncomingRmpmIotController::class, 'ping']);
    Route::get('/ping', [IncomingRmpmIotController::class, 'ping']);
});

// Health check (legacy)
Route::get('/status', function () {
    return response()->json([
        'status'      => 'online',
        'app'         => config('app.name'),
        'server_time' => now()->toIso8601String(),
    ]);
});