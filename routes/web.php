<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FgPsnController;
use App\Http\Controllers\FgSurabayaController;
use App\Http\Controllers\CsNoodleSbyController;
use App\Http\Controllers\CsFgSbyController;
use App\Http\Controllers\IncomingSingkongController;
use App\Http\Controllers\IncomingRmpmController;
use Illuminate\Support\Facades\Route;

// --- Public Routes ---
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
})->name('home');

// --- Protected Routes ---
Route::middleware(['auth'])->group(function () {
    // Main dashboard (routes by role/type)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // FG Pasuruan
    Route::get('/fg/dashboard', [DashboardController::class, 'operatorDashboard'])->name('fg.dashboard');
    Route::post('/penimbangan/store', [DashboardController::class, 'storePenimbangan'])->name('penimbangan.store');
    Route::post('/penimbangan/stop', [DashboardController::class, 'stopPenimbangan'])->name('penimbangan.stop');
    Route::post('/penimbangan/next', [DashboardController::class, 'nextSession'])->name('penimbangan.next');
    Route::get('/penimbangan/export', [DashboardController::class, 'export'])->name('penimbangan.export');

    // FG PSN
    Route::prefix('fg-psn')->name('fg-psn.')->group(function () {
        Route::get('/', [FgPsnController::class, 'dashboard'])->name('dashboard');
        Route::post('/store', [FgPsnController::class, 'store'])->name('store');
        Route::post('/stop', [FgPsnController::class, 'stop'])->name('stop');
        Route::post('/next', [FgPsnController::class, 'nextSession'])->name('next');
        Route::get('/export', [FgPsnController::class, 'export'])->name('export');
    });

    // FG Surabaya (Formulasi Surabaya)
    Route::prefix('fg-surabaya')->name('fg-surabaya.')->group(function () {
        Route::get('/', [FgSurabayaController::class, 'dashboard'])->name('dashboard');
        Route::post('/store', [FgSurabayaController::class, 'store'])->name('store');
        Route::post('/stop', [FgSurabayaController::class, 'stop'])->name('stop');
        Route::post('/next', [FgSurabayaController::class, 'nextSession'])->name('next');
        Route::get('/export', [FgSurabayaController::class, 'export'])->name('export');
    });

    // CS Noodle Surabaya
    Route::prefix('cs-noodle-sby')->name('cs-noodle-sby.')->group(function () {
        Route::get('/', [CsNoodleSbyController::class, 'dashboard'])->name('dashboard');
        Route::post('/store', [CsNoodleSbyController::class, 'store'])->name('store');
        Route::post('/stop', [CsNoodleSbyController::class, 'stop'])->name('stop');
        Route::post('/next', [CsNoodleSbyController::class, 'nextSession'])->name('next');
        Route::get('/export', [CsNoodleSbyController::class, 'export'])->name('export');
    });

    // CS FG-Sby Surabaya
    Route::prefix('cs-fg-sby')->name('cs-fg-sby.')->group(function () {
        Route::get('/', [CsFgSbyController::class, 'dashboard'])->name('dashboard');
        Route::post('/store', [CsFgSbyController::class, 'store'])->name('store');
        Route::post('/stop', [CsFgSbyController::class, 'stop'])->name('stop');
        Route::post('/next', [CsFgSbyController::class, 'nextSession'])->name('next');
        Route::get('/export', [CsFgSbyController::class, 'export'])->name('export');
    });

    // Incoming Singkong
    Route::prefix('incoming/singkong')->name('incoming.singkong.')->group(function () {
        Route::get('/', [IncomingSingkongController::class, 'dashboard'])->name('dashboard');
        Route::post('/start', [IncomingSingkongController::class, 'start'])->name('start');
        Route::post('/stop', [IncomingSingkongController::class, 'stop'])->name('stop');
        Route::post('/next', [IncomingSingkongController::class, 'nextSession'])->name('next');
        Route::get('/export', [IncomingSingkongController::class, 'export'])->name('export');
    });

    // Incoming RMPM
    Route::prefix('incoming/rmpm')->name('incoming.rmpm.')->group(function () {
        Route::get('/', [IncomingRmpmController::class, 'dashboard'])->name('dashboard');
        Route::post('/start', [IncomingRmpmController::class, 'start'])->name('start');
        Route::post('/stop', [IncomingRmpmController::class, 'stop'])->name('stop');
        Route::post('/next', [IncomingRmpmController::class, 'nextSession'])->name('next');
        Route::get('/export', [IncomingRmpmController::class, 'export'])->name('export');
    });
});

require __DIR__.'/auth.php';
