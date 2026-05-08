<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function identify(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string',
        ]);

        $driver = Driver::with('supplier')
            ->where('qr_code', $request->qr_code)
            ->first();

        if (!$driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver tidak ditemukan.',
            ], 404);
        }
        // else (!$driver->supplier) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Driver tidak memiliki supplier terkait.',
        //     ], 404);

        return response()->json([
            'success' => true,
            'driver' => [
                'id' => $driver->id,
                'name' => $driver->name,
                'supplier' => $driver->supplier->name,
                'nomor_plat' => $driver->nomor_plat,
            ],
        ]);
    }
}
