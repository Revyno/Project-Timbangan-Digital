<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FgPsnController;
use App\Http\Controllers\FgSurabayaController;
use App\Http\Controllers\CsNoodleSbyController;
use App\Http\Controllers\CsFgSbyController;
use App\Http\Controllers\IncomingSingkongController;
use App\Http\Controllers\IncomingRmpmController;
use App\Http\Controllers\MasterDataController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
});

// ============================================================
// SHARED ROUTES (Admin + Operator can both access)
// ============================================================
Route::middleware(['auth', 'verified'])->group(function () {
    // Main dashboard router — auto-redirects based on role
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ============================================================
// ADMIN ROUTES — /admin prefix
// ============================================================
Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->group(function () {
    // Admin data views (Read-Only with filters + export)
    Route::get('/fg', [DashboardController::class, 'operatorDashboard'])->name('admin.fg');
    Route::get('/fg/export', [DashboardController::class, 'export'])->name('admin.fg.export');

    Route::get('/fg-psn', [FgPsnController::class, 'dashboard'])->name('admin.fg-psn');
    Route::get('/fg-psn/export', [FgPsnController::class, 'export'])->name('admin.fg-psn.export');

    Route::get('/incoming/singkong', [IncomingSingkongController::class, 'dashboard'])->name('admin.incoming.singkong');
    Route::get('/incoming/singkong/export', [IncomingSingkongController::class, 'export'])->name('admin.incoming.singkong.export');

    Route::get('/incoming/rmpm', [IncomingRmpmController::class, 'dashboard'])->name('admin.incoming.rmpm');
    Route::get('/incoming/rmpm/export', [IncomingRmpmController::class, 'export'])->name('admin.incoming.rmpm.export');

    Route::get('/fg-surabaya', [FgSurabayaController::class, 'dashboard'])->name('admin.fg-surabaya');
    Route::get('/fg-surabaya/export', [FgSurabayaController::class, 'export'])->name('admin.fg-surabaya.export');

    Route::get('/cs-noodle-sby', [CsNoodleSbyController::class, 'dashboard'])->name('admin.cs-noodle-sby');
    Route::get('/cs-noodle-sby/export', [CsNoodleSbyController::class, 'export'])->name('admin.cs-noodle-sby.export');

    Route::get('/cs-fg-sby', [CsFgSbyController::class, 'dashboard'])->name('admin.cs-fg-sby');
    Route::get('/cs-fg-sby/export', [CsFgSbyController::class, 'export'])->name('admin.cs-fg-sby.export');

    // Master Data
    Route::get('/master/suppliers', [MasterDataController::class, 'suppliers'])->name('admin.master.suppliers');
    Route::post('/master/suppliers', [MasterDataController::class, 'storeSupplier'])->name('admin.master.suppliers.store');
    Route::get('/master/drivers', [MasterDataController::class, 'drivers'])->name('admin.master.drivers');
    Route::post('/master/drivers', [MasterDataController::class, 'storeDriver'])->name('admin.master.drivers.store');
});

// ============================================================
// OPERATOR ROUTES — /operator prefix
// ============================================================
Route::middleware(['auth', 'verified', 'role:operator'])->prefix('operator')->group(function () {
    // Pasuruan - Formulasi (FG)
    Route::get('/fg', [DashboardController::class, 'operatorDashboard'])->name('fg.dashboard');
    Route::post('/fg/store', [DashboardController::class, 'storePenimbangan'])->name('penimbangan.store');
    Route::post('/fg/next', [DashboardController::class, 'nextSession'])->name('penimbangan.next');
    Route::post('/fg/stop', [DashboardController::class, 'stopShift'])->name('penimbangan.stop');

    // Pasuruan - Finished Goods (PSN)
    Route::get('/fg-psn', [FgPsnController::class, 'dashboard'])->name('fg-psn.dashboard');
    Route::post('/fg-psn/store', [FgPsnController::class, 'store'])->name('fg-psn.store');
    Route::post('/fg-psn/next', [FgPsnController::class, 'nextSession'])->name('fg-psn.next');
    Route::post('/fg-psn/stop', [FgPsnController::class, 'stop'])->name('fg-psn.stop');

    // Incoming Singkong
    Route::get('/incoming/singkong', [IncomingSingkongController::class, 'dashboard'])->name('incoming.singkong.dashboard');
    Route::post('/incoming/singkong/store', [IncomingSingkongController::class, 'start'])->name('incoming.singkong.store');
    Route::post('/incoming/singkong/next', [IncomingSingkongController::class, 'nextSession'])->name('incoming.singkong.next');
    Route::post('/incoming/singkong/stop', [IncomingSingkongController::class, 'stop'])->name('incoming.singkong.stop');

    // Incoming RMPM
    Route::get('/incoming/rmpm', [IncomingRmpmController::class, 'dashboard'])->name('incoming.rmpm.dashboard');
    Route::post('/incoming/rmpm/store', [IncomingRmpmController::class, 'start'])->name('incoming.rmpm.store');
    Route::post('/incoming/rmpm/next', [IncomingRmpmController::class, 'nextSession'])->name('incoming.rmpm.next');
    Route::post('/incoming/rmpm/stop', [IncomingRmpmController::class, 'stop'])->name('incoming.rmpm.stop');

    // Surabaya - Formulasi
    Route::get('/fg-surabaya', [FgSurabayaController::class, 'dashboard'])->name('fg-surabaya.dashboard');
    Route::post('/fg-surabaya/store', [FgSurabayaController::class, 'store'])->name('fg-surabaya.store');
    Route::post('/fg-surabaya/next', [FgSurabayaController::class, 'nextSession'])->name('fg-surabaya.next');
    Route::post('/fg-surabaya/stop', [FgSurabayaController::class, 'stop'])->name('fg-surabaya.stop');

    // Surabaya - CS Noodle
    Route::get('/cs-noodle-sby', [CsNoodleSbyController::class, 'dashboard'])->name('cs-noodle-sby.dashboard');
    Route::post('/cs-noodle-sby/store', [CsNoodleSbyController::class, 'store'])->name('cs-noodle-sby.store');
    Route::post('/cs-noodle-sby/next', [CsNoodleSbyController::class, 'nextSession'])->name('cs-noodle-sby.next');
    Route::post('/cs-noodle-sby/stop', [CsNoodleSbyController::class, 'stop'])->name('cs-noodle-sby.stop');

    // Surabaya - CS FG-Sby
    Route::get('/cs-fg-sby', [CsFgSbyController::class, 'dashboard'])->name('cs-fg-sby.dashboard');
    Route::post('/cs-fg-sby/store', [CsFgSbyController::class, 'store'])->name('cs-fg-sby.store');
    Route::post('/cs-fg-sby/next', [CsFgSbyController::class, 'nextSession'])->name('cs-fg-sby.next');
    Route::post('/cs-fg-sby/stop', [CsFgSbyController::class, 'stop'])->name('cs-fg-sby.stop');
});

// ============================================================
// EXPORT ROUTES (accessible by both admin and operator)
// ============================================================
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/export/penimbangan', [DashboardController::class, 'export'])->name('penimbangan.export');
    Route::get('/export/fg-psn', [FgPsnController::class, 'export'])->name('fg-psn.export');
    Route::get('/export/incoming-singkong', [IncomingSingkongController::class, 'export'])->name('incoming.singkong.export');
    Route::get('/export/incoming-rmpm', [IncomingRmpmController::class, 'export'])->name('incoming.rmpm.export');
    Route::get('/export/fg-surabaya', [FgSurabayaController::class, 'export'])->name('fg-surabaya.export');
    Route::get('/export/cs-noodle-sby', [CsNoodleSbyController::class, 'export'])->name('cs-noodle-sby.export');
    Route::get('/export/cs-fg-sby', [CsFgSbyController::class, 'export'])->name('cs-fg-sby.export');
});

require __DIR__.'/auth.php';

// Public Driver Registration
Route::get('/register-driver', [App\Http\Controllers\DriverRegistrationController::class, 'create'])->name('driver.register');
Route::post('/register-driver', [App\Http\Controllers\DriverRegistrationController::class, 'store'])->name('driver.register.store');
