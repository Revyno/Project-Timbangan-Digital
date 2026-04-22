<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Penimbangan;
use Illuminate\Http\Request;

class IotController extends Controller
{
    /**
     * POST /api/iot/weight
     * Receive weight data from Arduino and update penimbangan record.
     */
    public function receiveWeight(Request $request)
    {
        $validated = $request->validate([
            'token'         => 'required|string',
            'kode_produksi' => 'required|string',
            'berat'         => 'required|numeric|min:0',
        ]);

        // 1. Authenticate device
        $device = Device::where('device_token', $validated['token'])
                        ->where('is_active', true)
                        ->first();

        if (! $device) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Token device tidak valid atau device nonaktif.',
            ], 401);
        }

        // 2. Update heartbeat
        $device->update(['last_online' => now()]);

        // 3. Find matching pending record
        $penimbangan = Penimbangan::with('produk')
            ->where('kode_produksi', $validated['kode_produksi'])
            ->where('status', 'menunggu')
            ->first();

        if (! $penimbangan) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Kode produksi tidak ditemukan atau sudah selesai.',
            ], 404);
        }

        // 4. Calculate selisih and save
        $targetBerat = (float) ($penimbangan->produk->target_berat ?? 0);
        $berat       = (float) $validated['berat'];
        $selisih     = round($berat - $targetBerat, 3);

        $penimbangan->update([
            'berat'     => $berat,
            'selisih'   => $selisih,
            'device_id' => $device->id,
            'status'    => 'selesai',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Berat berhasil disimpan.',
        ], 200);
    }

    /**
     * GET /api/iot/settings
     * Arduino calls this to get the latest pending kode_produksi.
     */
    public function getSettings(Request $request)
    {
        $token = $request->query('token');
        
        $device = Device::where('device_token', $token)->first();
        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Invalid token'], 401);
        }

        // Find the latest record with status 'menunggu'
        $latest = Penimbangan::with('produk')
            ->where('status', 'menunggu')
            ->orderByDesc('created_at')
            ->first();

        if (!$latest) {
            return response()->json([
                'status' => 'idle',
                'message' => 'Tidak ada antrian penimbangan.'
            ]);
        }

        return response()->json([
            'status'         => 'ready',
            'kode_produksi'  => $latest->kode_produksi,
            'nama_produk'    => $latest->produk->nama_produk,
            'target_berat'   => $latest->produk->target_berat
        ]);
    }

    /**
     * POST /api/iot/ping
     */
    public function ping(Request $request)
    {
        $token = $request->input('token') ?? $request->query('token');
        $device = Device::where('device_token', $token)->first();
        
        if ($device) {
            $device->update(['last_online' => now()]);
            return response()->json(['status' => 'alive']);
        }
        
        return response()->json(['status' => 'error'], 401);
    }
}
