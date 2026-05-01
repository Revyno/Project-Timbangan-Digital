<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\IncomingRmpm;
use App\Models\User;
use App\Events\WeightReceived;
use Illuminate\Http\Request;

class IncomingRmpmIotController extends Controller
{
    public function getSettings(Request $request)
    {
        $token = $request->query('token');
        $device = Device::where('device_token', $token)->first();

        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $device->update(['last_online' => now()]);

        $operator = User::where('tipe', 'incoming_rmpm')
            ->where('role', 'operator')
            ->where('session_locked', false)
            ->first();

        if (!$operator) {
            return response()->json(['status' => 'idle', 'message' => 'Tidak ada operator aktif', 'operator' => 'N/A']);
        }

        $session = cache()->get("session_rmpm_{$operator->id}");

        if (!$session) {
            return response()->json(['status' => 'idle', 'message' => 'Belum ada sesi aktif', 'operator' => $operator->name]);
        }

        return response()->json([
            'status' => 'ready',
            'kode_produksi' => $session['kode_batch'] ?? $session['no_surat'],
            'nama_produk' => $session['nama_barang'],
            'operator' => $operator->name,
            'expired' => $session['expired_date'] ?? '-',
            'no_surat' => $session['no_surat'],
            'nama_supplier' => $session['nama_supplier'],
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

        $operator = User::where('tipe', 'incoming_rmpm')
            ->where('role', 'operator')
            ->where('session_locked', false)
            ->first();

        if (!$operator) {
            return response()->json(['status' => 'error', 'message' => 'No active operator'], 400);
        }

        $session = cache()->get("session_rmpm_{$operator->id}");

        if (!$session) {
            return response()->json(['status' => 'error', 'message' => 'No active session'], 400);
        }

        $record = IncomingRmpm::create([
            'tanggal_kedatangan' => $session['tanggal_kedatangan'],
            'petugas_penerima' => $session['petugas_penerima'],
            'nama_barang' => $session['nama_barang'],
            'jenis_barang' => $session['jenis_barang'],
            'asal' => $session['asal'],
            'nama_supplier' => $session['nama_supplier'],
            'no_surat' => $session['no_surat'],
            'nama_sopir' => $session['nama_sopir'],
            'nomor_plat' => $session['nomor_plat'],
            'total_qty' => $session['total_qty'],
            'kode_batch' => $session['kode_batch'],
            'expired_date' => $session['expired_date'],
            'berat' => $weight,
            'user_id' => $operator->id,
            'device_id' => $device->id,
            'status' => 'selesai',
        ]);

        // Broadcast WebSocket Event
        broadcast(new WeightReceived('incoming_rmpm', [
            'weight' => $weight,
            'operator' => $operator->name,
            'product' => $session['nama_barang'],
            'kode_produksi' => $session['kode_batch'] ?? $session['no_surat'],
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
