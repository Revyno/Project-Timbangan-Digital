<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use Illuminate\Support\Facades\Route;

// --- Public Routes ---
Route::get('/', [AuthController::class, 'showLogin'])->name('home');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// --- Protected Routes ---
Route::middleware(['auth', 'shift'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/operator', [DashboardController::class, 'operator'])->name('operator');
    Route::post('/penimbangan/store', [DashboardController::class, 'storePenimbangan'])->name('penimbangan.store');
    Route::post('/penimbangan/stop', [DashboardController::class, 'stopPenimbangan'])->name('penimbangan.stop');
    Route::get('/penimbangan/export', [DashboardController::class, 'export'])->name('penimbangan.export')->middleware('role:admin');
});
  
//     Route::get('/simulator', function() {
//         return view('simulator.arduino', [
//             'produks' => \App\Models\Produk::all()
//         ]);
//     })->name('simulator');


// Route::get('/devices', [DeviceController::class,'index'])->name('devices.index');
// Route::get('/devices/create', [DeviceController::class,'create'])->name('devices.create');
// Route::post('/devices/store', [DeviceController::class,'store'])->name('devices.store');
// Route::get('/devices/{id}/edit', [DeviceController::class,'edit'])->name('devices.edit');
// Route::post('/devices/{id}/update', [DeviceController::class,'update'])->name('devices.update');
// Route::post('/devices/{id}/delete', [DeviceController::class,'destroy'])->name('devices.destroy');