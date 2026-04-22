<?php

use App\Http\Controllers\Api\IotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Arduino / IoT Integration
|--------------------------------------------------------------------------
|
| These endpoints are consumed by the ESP8266 (WEMOS D1 R2) device
| running firmware FG_Pasuruan_V6.6_OLED_260421.ino
|
| Authentication: device_token (in request body or query param)
| No CSRF. All responses are JSON.
|
*/

Route::prefix('iot')->group(function () {

    /**
     * POST /api/iot/weight
     * Send weight measurement to server.
     *
     * Body: { "token": "...", "kode_produksi": "...", "berat": 50.123 }
     */
    Route::post('/weight', [IotController::class, 'receiveWeight']);

    /**
     * GET /api/iot/settings?token=xxx
     * Sync settings (product info, session data) from server to device.
     */
    Route::get('/settings', [IotController::class, 'getSettings']);

    /**
     * POST /api/iot/ping
     * Device heartbeat — updates last_online timestamp.
     */
    Route::post('/ping', [IotController::class, 'ping']);
    Route::get('/ping',  [IotController::class, 'ping']);   // allow GET for easy testing
});

// Health check
Route::get('/status', function () {
    return response()->json([
        'status'      => 'online',
        'app'         => config('app.name'),
        'server_time' => now()->toIso8601String(),
    ]);
});