<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FgPsnController;
use App\Http\Controllers\FgSurabayaController;
use App\Http\Controllers\CsNoodleSbyController;
use App\Http\Controllers\CsFgSbyController;
use App\Http\Controllers\IncomingSingkongController;
use App\Http\Controllers\IncomingRmpmController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Main Dashboard (Overview)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pasuruan - Formulasi (FG)
    Route::get('/fg', [DashboardController::class, 'operatorDashboard'])->name('fg.dashboard');
    Route::post('/penimbangan/store', [DashboardController::class, 'storePenimbangan'])->name('penimbangan.store');
    Route::post('/penimbangan/next', [DashboardController::class, 'nextSession'])->name('penimbangan.next');
    Route::post('/penimbangan/stop', [DashboardController::class, 'stopShift'])->name('penimbangan.stop');
    Route::get('/penimbangan/export', [DashboardController::class, 'export'])->name('penimbangan.export');

    // Pasuruan - Finished Goods (PSN)
    Route::get('/fg-psn', [FgPsnController::class, 'dashboard'])->name('fg-psn.dashboard');
    Route::post('/fg-psn/store', [FgPsnController::class, 'store'])->name('fg-psn.store');
    Route::post('/fg-psn/next', [FgPsnController::class, 'nextSession'])->name('fg-psn.next');
    Route::post('/fg-psn/stop', [FgPsnController::class, 'stop'])->name('fg-psn.stop');
    Route::get('/fg-psn/export', [FgPsnController::class, 'export'])->name('fg-psn.export');

    // Incoming Singkong
    Route::get('/incoming/singkong', [IncomingSingkongController::class, 'dashboard'])->name('incoming.singkong.dashboard');
    Route::post('/incoming/singkong/store', [IncomingSingkongController::class, 'start'])->name('incoming.singkong.store');
    Route::post('/incoming/singkong/next', [IncomingSingkongController::class, 'nextSession'])->name('incoming.singkong.next');
    Route::post('/incoming/singkong/stop', [IncomingSingkongController::class, 'stop'])->name('incoming.singkong.stop');
    Route::get('/incoming/singkong/export', [IncomingSingkongController::class, 'export'])->name('incoming.singkong.export');

    // Incoming RMPM
    Route::get('/incoming/rmpm', [IncomingRmpmController::class, 'dashboard'])->name('incoming.rmpm.dashboard');
    Route::post('/incoming/rmpm/store', [IncomingRmpmController::class, 'start'])->name('incoming.rmpm.store');
    Route::post('/incoming/rmpm/next', [IncomingRmpmController::class, 'nextSession'])->name('incoming.rmpm.next');
    Route::post('/incoming/rmpm/stop', [IncomingRmpmController::class, 'stop'])->name('incoming.rmpm.stop');
    Route::get('/incoming/rmpm/export', [IncomingRmpmController::class, 'export'])->name('incoming.rmpm.export');

    // Surabaya - Formulasi
    Route::get('/fg-surabaya', [FgSurabayaController::class, 'dashboard'])->name('fg-surabaya.dashboard');
    Route::post('/fg-surabaya/store', [FgSurabayaController::class, 'store'])->name('fg-surabaya.store');
    Route::post('/fg-surabaya/next', [FgSurabayaController::class, 'nextSession'])->name('fg-surabaya.next');
    Route::post('/fg-surabaya/stop', [FgSurabayaController::class, 'stop'])->name('fg-surabaya.stop');
    Route::get('/fg-surabaya/export', [FgSurabayaController::class, 'export'])->name('fg-surabaya.export');

    // Surabaya - CS Noodle
    Route::get('/cs-noodle-sby', [CsNoodleSbyController::class, 'dashboard'])->name('cs-noodle-sby.dashboard');
    Route::post('/cs-noodle-sby/store', [CsNoodleSbyController::class, 'store'])->name('cs-noodle-sby.store');
    Route::post('/cs-noodle-sby/next', [CsNoodleSbyController::class, 'nextSession'])->name('cs-noodle-sby.next');
    Route::post('/cs-noodle-sby/stop', [CsNoodleSbyController::class, 'stop'])->name('cs-noodle-sby.stop');
    Route::get('/cs-noodle-sby/export', [CsNoodleSbyController::class, 'export'])->name('cs-noodle-sby.export');

    // Surabaya - CS FG-Sby
    Route::get('/cs-fg-sby', [CsFgSbyController::class, 'dashboard'])->name('cs-fg-sby.dashboard');
    Route::post('/cs-fg-sby/store', [CsFgSbyController::class, 'store'])->name('cs-fg-sby.store');
    Route::post('/cs-fg-sby/next', [CsFgSbyController::class, 'nextSession'])->name('cs-fg-sby.next');
    Route::post('/cs-fg-sby/stop', [CsFgSbyController::class, 'stop'])->name('cs-fg-sby.stop');
    Route::get('/cs-fg-sby/export', [CsFgSbyController::class, 'export'])->name('cs-fg-sby.export');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
