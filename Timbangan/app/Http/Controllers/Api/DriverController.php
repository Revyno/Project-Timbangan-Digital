<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/driver/identify",
     *     tags={"Driver"},
     *     summary="Identifikasi driver via QR Code",
     *     description="Memvalidasi QR Code driver dan mengembalikan informasi driver beserta supplier-nya. Digunakan oleh scanner/tablet di pintu masuk.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 required={"qr_code"},
     *                 @OA\Property(property="qr_code", type="string", example="DRV-2024-001", description="Kode QR driver yang di-scan")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Driver ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="driver",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Ahmad Sopir"),
     *                 @OA\Property(property="supplier", type="string", example="PT. Singkong Jaya"),
     *                 @OA\Property(property="nomor_plat", type="string", example="L 1234 AB")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Driver tidak ditemukan",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Driver tidak ditemukan.")
     *         )
     *     )
     * )
     */
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
