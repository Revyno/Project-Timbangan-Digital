<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\IncomingSingkong;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DriverRegistrationController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/DriverRegister', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'asalOptions' => IncomingSingkong::asalOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'supplier_id' => 'required|exists:suppliers,id',
            'nomor_plat' => 'required|string|max:20',
            'asal' => 'required|string',
        ]);

        $qrCode = 'DRV-' . strtoupper(Str::random(8));

        $driver = Driver::create([
            'name' => $validated['name'],
            'supplier_id' => $validated['supplier_id'],
            'nomor_plat' => $validated['nomor_plat'],
            'asal' => $validated['asal'],
            'qr_code' => $qrCode,
        ]);

        return Inertia::render('Auth/DriverRegister', [
            'suppliers' => Supplier::orderBy('name')->get(),
            'asalOptions' => IncomingSingkong::asalOptions(),
            'success' => true,
            'driver' => $driver->load('supplier'),
        ]);
    }
}
