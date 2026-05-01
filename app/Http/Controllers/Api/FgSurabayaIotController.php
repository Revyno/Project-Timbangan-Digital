<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Penimbangan;
use App\Models\Produk;
use App\Models\User;
use App\Events\WeightReceived;
use Illuminate\Http\Request;

class FgSurabayaIotController extends Controller
{
    public function getSettings(Request $request)
    {
        $token = $request->query('token');
        $device = Device::where('device_token', $token)->first();

        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $device->update(['last_online' => now()]);

        $operator = User::where('tipe', 'fg_surabaya')
            ->where('role', 'operator')
            ->where('session_locked', false)
            ->first();

        if (!$operator) {
            return response()->json(['status' => 'idle', 'message' => 'Tidak ada operator aktif', 'operator' => 'N/A']);
        }

        $session = cache()->get("session_fg_sby_{$operator->id}");

        if (!$session) {
            return response()->json(['status' => 'idle', 'message' => 'Belum ada sesi aktif', 'operator' => $operator->name]);
        }

        $produk = Produk::find($session['produk_id']);

        return response()->json([
            'status' => 'ready',
            'kode_produksi' => $session['kode_produksi'],
            'nama_produk' => $produk ? $produk->nama_produk : 'Unknown',
            'operator' => $operator->name,
            'expired' => $session['tanggal_expired'] ?? '-',
        ]);
    }

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

        $operator = User::where('tipe', 'fg_surabaya')
            ->where('role', 'operator')
            ->where('session_locked', false)
            ->first();

        if (!$operator) {
            return response()->json(['status' => 'error', 'message' => 'No active operator'], 400);
        }

        $session = cache()->get("session_fg_sby_{$operator->id}");

        if (!$session) {
            return response()->json(['status' => 'error', 'message' => 'No active session'], 400);
        }

        $produk = Produk::find($session['produk_id']);
        $target = $produk ? $produk->target_berat : 0;
        $selisih = $weight - $target;

        $record = Penimbangan::create([
            'tanggal_penimbangan' => now()->toDateString(),
            'produk_id' => $session['produk_id'],
            'user_id' => $operator->id,
            'device_id' => $device->id,
            'kode_produksi' => $session['kode_produksi'],
            'tanggal_expired' => $session['tanggal_expired'],
            'berat' => $weight,
            'selisih' => $selisih,
            'status' => 'selesai',
        ]);

        // Broadcast WebSocket Event
        broadcast(new WeightReceived('fg_surabaya', [
            'weight' => $weight,
            'operator' => $operator->name,
            'product' => $produk ? $produk->nama_produk : 'Unknown',
            'kode_produksi' => $session['kode_produksi'],
            'status' => 'selesai'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => "Data berat {$weight} kg tersimpan",
            'record_id' => $record->id,
        ]);
    }

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
