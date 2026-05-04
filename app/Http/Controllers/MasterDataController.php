<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\IncomingSingkong;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MasterDataController extends Controller
{
    public function suppliers()
    {
        return Inertia::render('Admin/Master/Suppliers', [
            'suppliers' => Supplier::orderBy('name')->get()
        ]);
    }

    public function storeSupplier(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Supplier::create($validated);

        return back()->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function drivers()
    {
        return Inertia::render('Admin/Master/Drivers', [
            'drivers' => Driver::with('supplier')->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'asalOptions' => IncomingSingkong::asalOptions(),
        ]);
    }

    public function storeDriver(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'supplier_id' => 'required|exists:suppliers,id',
            'nomor_plat' => 'required|string|max:20',
            'asal' => 'required|string',
        ]);

        // Generate a random unique QR code if not provided
        $validated['qr_code'] = 'DRV-' . strtoupper(Str::random(8));

        Driver::create([
            'name' => $validated['name'],
            'supplier_id' => $validated['supplier_id'],
            'nomor_plat' => $validated['nomor_plat'],
            'asal' => $validated['asal'],
            'qr_code' => $validated['qr_code'],
        ]);

        return back()->with('success', 'Driver berhasil ditambahkan.');
    }
}
