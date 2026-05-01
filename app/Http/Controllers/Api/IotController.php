<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Penimbangan;
use App\Models\User;
use App\Services\ShiftService;
use App\Events\WeightReceived;
use Illuminate\Http\Request;

class IotController extends Controller
{
    /**
     * GET /api/iot/settings
     * Arduino calls this to get context about the current shift and product.
     */
    public function getSettings(Request $request)
    {
        $token = $request->query('token');
        $device = Device::where('device_token', $token)->first();

        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $device->update(['last_online' => now()]);

        // Current active operator for the shift
        $activeOperator = ShiftService::getActiveOperator();
        $operatorName = $activeOperator ? $activeOperator->name : 'N/A';

        // CEK SESI MANUAL DARI CACHE (Prioritas Utama)
        if ($activeOperator) {
            $session = cache()->get("session_operator_{$activeOperator->id}");
            if ($session) {
                $produk = Produk::find($session['produk_id']);
                return response()->json([
                    'status'         => 'ready',
                    'kode_produksi'  => $session['kode_produksi'],
                    'nama_produk'    => $produk ? $produk->nama_produk : 'Unknown',
                    'operator'       => $operatorName,
                    'expired'        => $session['tanggal_expired'] ?? '-'
                ]);
            }
        }

        // Jika tidak ada sesi manual, cek record 'menunggu' khusus untuk operator ini
        if ($activeOperator) {
            $latest = Penimbangan::with('produk')
                ->where('user_id', $activeOperator->id)
                ->where('status', 'menunggu')
                ->orderBy('created_at', 'asc')
                ->first();

            if ($latest) {
                return response()->json([
                    'status'         => 'ready',
                    'kode_produksi'  => $latest->kode_produksi,
                    'nama_produk'    => $latest->produk->nama_produk,
                    'operator'       => $operatorName,
                    'expired'        => $latest->tanggal_expired ?? '-'
                ]);
            }
        }

        // AUTO MODE: Use the device's assigned product
        // $assignedProduct = $device->currentProduct ?: Produk::first();
        
        return response()->json([
            'status' => 'idle',
            'message' => 'Silahkan timbang sekarang.',
            'operator' => $operatorName,
            // 'next_product' => $assignedProduct ? $assignedProduct->nama_produk : 'N/A',
        ]);
    }

    /**
     * POST /api/iot/weight
     * Receive weight and automatically create/update records.
     */
    public function receiveWeight(Request $request)
    {
        $validated = $request->validate([
            'token'         => 'required|string',
            'kode_produksi' => 'nullable|string', 
            'berat'         => 'required|numeric|min:0',
            'produk_id'     => 'nullable|exists:produks,id', // Tambahkan ini agar bisa ditest manual
        ]);

        $device = Device::where('device_token', $validated['token'])->first();
        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $berat = (float) $validated['berat'];
        $penimbangan = null;
        $activeOperator = ShiftService::getActiveOperator();

        // 1. CEK SESI MANUAL DARI CACHE (Prioritas Utama)
        if ($activeOperator) {
            $session = cache()->get("session_operator_{$activeOperator->id}");
            if ($session) {
                $produk = Produk::find($session['produk_id']);
                if ($produk) {
                    // Agar bisa menggunakan Kode Produksi yang sama berulang kali (karena di DB unik)
                    // Kita tambahkan suffix timestamp yang nanti bisa kita sembunyikan saat tampil
                    $kodeDb = $session['kode_produksi'];

                    $penimbangan = Penimbangan::create([
                        'tanggal_penimbangan' => now()->format('Y-m-d'),
                        'produk_id' => $produk->id,
                        'user_id' => $activeOperator->id,
                        'kode_produksi' => $kodeDb,
                        'tanggal_expired' => $session['tanggal_expired'],
                        'berat' => $berat,
                        'device_id' => $device->id,
                        'status' => 'selesai',
                    ]);

                    // Broadcast WebSocket Event
                    broadcast(new WeightReceived('fg', [
                        'weight' => $berat,
                        'operator' => $activeOperator->name,
                        'product' => $produk->nama_produk,
                        'kode_produksi' => $kodeDb,
                        'status' => 'selesai'
                    ]));

                    return response()->json([
                        'status' => 'success',
                        'message' => 'Berat Produksi berhasil disimpan: ',
                    ]);
                }
            }
        }

        // 2. Cari record 'menunggu' yang sesuai dengan kode_produksi dari Arduino (Legacy/Fallback)
        if (!empty($validated['kode_produksi']) && $validated['kode_produksi'] !== '-') {
            $penimbangan = Penimbangan::with('produk')
                ->where('kode_produksi', $validated['kode_produksi'])
                ->where('status', 'menunggu')
                ->first();
        }

        // 3. Jika tidak ketemu, dan TIDAK ADA kode_produksi di request, cari record 'menunggu' terbaru untuk operator ini
        if (!$penimbangan && $activeOperator && (empty($validated['kode_produksi']) || $validated['kode_produksi'] === '-')) {
            $penimbangan = Penimbangan::with('produk')
                ->where('user_id', $activeOperator->id)
                ->where('status', 'menunggu')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        // 4. Jika MASIH tidak ada record 'menunggu' (Auto-Create Fallback)
        if (!$penimbangan) {
            if (!$activeOperator) {
                return response()->json(['status' => 'error', 'message' => 'No active operator.'], 403);
            }

            $produkId = $validated['produk_id'] ?? ($device->current_product_id ?? Produk::first()->id);
            $produk = Produk::find($produkId);

            // kode produksi di dapat dari operator yang menginputkan manual (Request / Dashboard)
            $kodeInput = $validated['kode_produksi'] ?? null;
            
            if (empty($kodeInput) || $kodeInput === '-') {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Kode Produksi (KP-) belum diinput. Silahkan input manual.'
                ], 400);
            }

            // Tambahkan suffix agar bisa input berulang kali dengan kode yang sama (karena di DB unik)
            $kode_produksi = $kodeInput;
            
            $penimbangan = Penimbangan::create([
                'tanggal_penimbangan' => now()->format('Y-m-d'),
                'produk_id' => $produk->id,
                'user_id' => $activeOperator->id,
                'kode_produksi' => $kode_produksi,
                'tanggal_expired' => now()->addYear()->format('Y-m-d'),
                'berat' => $berat,
                'device_id' => $device->id,
                'status' => 'selesai',
            ]);

            // Broadcast WebSocket Event
            broadcast(new WeightReceived('fg', [
                'weight' => $berat,
                'operator' => $activeOperator->name,
                'product' => $produk->nama_produk,
                'kode_produksi' => $kode_produksi,
                'status' => 'selesai'
            ]));

            return response()->json([
                'status' => 'success',
                // 'message'=> 'Berat-Berhasil Di Tambahkan: {$berat} kg',
                'message' => "Kode-Produksi: {$kode_produksi}",
                'kode_produksi' => $kode_produksi
                // 'berat' => $berat,
            ]);
        }

        // 5. MANUAL UPDATE: If we found a pending record (Legacy mode)
        $produk = $penimbangan->produk;
        // $selisih = round($berat - (float) $produk->target_berat, 3);

        $penimbangan->update([
            'berat'     => $berat,
            'device_id' => $device->id,
            'status'    => 'selesai',
        ]);

        // Broadcast WebSocket Event
        broadcast(new WeightReceived('fg', [
            'weight' => $berat,
            'operator' => $activeOperator ? $activeOperator->name : 'Unknown',
            'product' => $produk->nama_produk,
            'kode_produksi' => $penimbangan->kode_produksi,
            'status' => 'selesai'
        ]));

        return response()->json([
            'status'  => 'success',
            'message' => 'Berat berhasil disimpan.',
        ]);
    }

    /**
     * POST /api/iot/device/update-product
     * Change which product this device is currently weighing.
     */
    public function updateDeviceProduct(Request $request)
    {
        $validated = $request->validate([
            'token'      => 'required|string',
            'produk_id'  => 'required|exists:produks,id',
        ]);

        $device = Device::where('device_token', $validated['token'])->first();
        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $device->update(['current_product_id' => $validated['produk_id']]);
        
        $produk = Produk::find($validated['produk_id']);

        return response()->json([
            'status' => 'success',
            'message' => "Device updated to product: {$produk->nama_produk}",
            'nama_produk' => $produk->nama_produk
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
            return response()->json([
                'status' => 'success', 
                'message' => 'Timbangan Active',
                'device' => $device->device_name
            ]);
        }
        
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }
}
