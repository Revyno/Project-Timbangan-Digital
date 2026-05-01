<?php

use App\Http\Controllers\Api\IotController;
use App\Http\Controllers\Api\FgPsnIotController;
use App\Http\Controllers\Api\FgSurabayaIotController;
use App\Http\Controllers\Api\CsNoodleSbyIotController;
use App\Http\Controllers\Api\CsFgSbyIotController;
use App\Http\Controllers\Api\IncomingSingkongIotController;
use App\Http\Controllers\Api\IncomingRmpmIotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Arduino / IoT Integration
|--------------------------------------------------------------------------
*/

// FG Pasuruan IoT
Route::prefix('iot')->group(function () {
    Route::post('/weight', [IotController::class, 'receiveWeight']);
    Route::get('/settings', [IotController::class, 'getSettings']);
    Route::post('/ping', [IotController::class, 'ping']);
    Route::get('/ping',  [IotController::class, 'ping']);
    Route::post('/device/update-product', [IotController::class, 'updateDeviceProduct']);
});

// FG PSN IoT
Route::prefix('iot/fg-psn')->group(function () {
    Route::get('/settings', [FgPsnIotController::class, 'getSettings']);
    Route::post('/weight', [FgPsnIotController::class, 'receiveWeight']);
    Route::post('/ping', [FgPsnIotController::class, 'ping']);
    Route::get('/ping', [FgPsnIotController::class, 'ping']);
});

// FG Surabaya IoT
Route::prefix('iot/fg-surabaya')->group(function () {
    Route::get('/settings', [FgSurabayaIotController::class, 'getSettings']);
    Route::post('/weight', [FgSurabayaIotController::class, 'receiveWeight']);
    Route::post('/ping', [FgSurabayaIotController::class, 'ping']);
    Route::get('/ping', [FgSurabayaIotController::class, 'ping']);
});

// CS Noodle Surabaya IoT
Route::prefix('iot/cs-noodle-sby')->group(function () {
    Route::get('/settings', [CsNoodleSbyIotController::class, 'getSettings']);
    Route::post('/weight', [CsNoodleSbyIotController::class, 'receiveWeight']);
    Route::post('/ping', [CsNoodleSbyIotController::class, 'ping']);
    Route::get('/ping', [CsNoodleSbyIotController::class, 'ping']);
});

// CS FG-Sby Surabaya IoT
Route::prefix('iot/cs-fg-sby')->group(function () {
    Route::get('/settings', [CsFgSbyIotController::class, 'getSettings']);
    Route::post('/weight', [CsFgSbyIotController::class, 'receiveWeight']);
    Route::post('/ping', [CsFgSbyIotController::class, 'ping']);
    Route::get('/ping', [CsFgSbyIotController::class, 'ping']);
});

// Incoming Singkong IoT
Route::prefix('iot/incoming-singkong')->group(function () {
    Route::get('/settings', [IncomingSingkongIotController::class, 'getSettings']);
    Route::post('/weight', [IncomingSingkongIotController::class, 'receiveWeight']);
    Route::post('/ping', [IncomingSingkongIotController::class, 'ping']);
    Route::get('/ping', [IncomingSingkongIotController::class, 'ping']);
});

// Incoming RMPM IoT
Route::prefix('iot/incoming-rmpm')->group(function () {
    Route::get('/settings', [IncomingRmpmIotController::class, 'getSettings']);
    Route::post('/weight', [IncomingRmpmIotController::class, 'receiveWeight']);
    Route::post('/ping', [IncomingRmpmIotController::class, 'ping']);
    Route::get('/ping', [IncomingRmpmIotController::class, 'ping']);
});

// Health check
Route::get('/status', function () {
    return response()->json([
        'status'      => 'online',
        'app'         => config('app.name'),
        'server_time' => now()->toIso8601String(),
    ]);
});