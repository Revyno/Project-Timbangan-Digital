<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\IncomingSingkong;
use App\Models\User;
use App\Events\WeightReceived;
use Illuminate\Http\Request;

class IncomingSingkongIotController extends Controller
{
    /**
     * GET /api/iot/incoming-singkong/settings
     * Arduino calls this to get context about the current session.
     */
    public function getSettings(Request $request)
    {
        $token = $request->query('token');
        $device = Device::where('device_token', $token)->first();

        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $device->update(['last_online' => now()]);

        // Find active operator for incoming_singkong
        $operator = User::where('tipe', 'incoming_singkong')
            ->where('role', 'operator')
            ->where('session_locked', false)
            ->first();

        if (!$operator) {
            return response()->json([
                'status' => 'idle',
                'message' => 'Tidak ada operator aktif',
                'operator' => 'N/A',
            ]);
        }

        $session = cache()->get("session_singkong_{$operator->id}");

        if (!$session) {
            return response()->json([
                'status' => 'idle',
                'message' => 'Belum ada sesi aktif',
                'operator' => $operator->name,
            ]);
        }

        return response()->json([
            'status' => 'ready',
            'kode_produksi' => $session['kode_produksi'],
            'nama_produk' => $session['jenis_singkong'],
            'operator' => $operator->name,
            'expired' => '-',
            'no_surat' => $session['no_surat'],
            'nama_supplier' => $session['nama_supplier'],
        ]);
    }

    /**
     * POST /api/iot/incoming-singkong/weight
     * Arduino sends weight data here.
     */
    public function receiveWeight(Request $request)
    {
        $token = $request->input('token');
        $device = Device::where('device_token', $token)->first();

        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $weight = floatval($request->input('weight', 0));

        if ($weight <= 0) {
            return response()->json(['status' => 'error', 'message' => 'Berat tidak valid'], 400);
        }

        $operator = User::where('tipe', 'incoming_singkong')
            ->where('role', 'operator')
            ->where('session_locked', false)
            ->first();

        if (!$operator) {
            return response()->json(['status' => 'error', 'message' => 'No active operator'], 400);
        }

        $session = cache()->get("session_singkong_{$operator->id}");

        if (!$session) {
            return response()->json(['status' => 'error', 'message' => 'No active session'], 400);
        }

        $record = IncomingSingkong::create([
            'tanggal_penimbangan' => now()->toDateString(),
            'no_surat' => $session['no_surat'],
            'nama_supplier' => $session['nama_supplier'],
            'asal' => $session['asal'],
            'nama_sopir' => $session['nama_sopir'],
            'nomor_plat' => $session['nomor_plat'],
            'jenis_singkong' => $session['jenis_singkong'],
            'kode_produksi' => $session['kode_produksi'],
            'berat' => $weight,
            'user_id' => $operator->id,
            'device_id' => $device->id,
            'status' => 'selesai',
        ]);

        // Broadcast WebSocket Event
        broadcast(new WeightReceived('incoming_singkong', [
            'weight' => $weight,
            'operator' => $operator->name,
            'product' => $session['jenis_singkong'],
            'kode_produksi' => $session['kode_produksi'],
            'status' => 'selesai'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => "Data berat {$weight} kg tersimpan",
            'record_id' => $record->id,
        ]);
    }

    /**
     * GET/POST /api/iot/incoming-singkong/ping
     */
    public function ping(Request $request)
    {
        $token = $request->input('token') ?? $request->query('token');
        $device = Device::where('device_token', $token)->first();

        if ($device) {
            $device->update(['last_online' => now()]);
            return response()->json(['status' => 'ok', 'server_time' => now()->toDateTimeString()]);
        }

        return response()->json(['status' => 'error', 'message' => 'Unknown device'], 401);
    }
}
