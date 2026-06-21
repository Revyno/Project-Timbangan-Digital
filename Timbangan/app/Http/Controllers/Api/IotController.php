<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Penimbangan;
use App\Models\Produk;
use App\Models\User;
use App\Services\ShiftService;
use App\Events\WeightReceived;
use Illuminate\Http\Request;

class IotController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/fg-pasuruan/settings",
     *     tags={"FG Pasuruan"},
     *     summary="Get pengaturan sesi aktif timbangan FG Pasuruan",
     *     description="Dipanggil oleh Arduino/ESP8266 untuk mendapatkan informasi sesi aktif (produk, operator, kode produksi). Gunakan token perangkat sebagai query parameter.",
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         description="Token unik perangkat timbangan",
     *         @OA\Schema(type="string", example="DEV-TOKEN-FG-PSN-001")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sukses - status ready atau idle",
     *         @OA\JsonContent(ref="#/components/schemas/SettingsResponse")
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token tidak valid",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     *
     * GET /api/v1/fg-pasuruan/settings
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

        return response()->json([
            'status' => 'idle',
            'message' => 'Silahkan timbang sekarang.',
            'operator' => $operatorName,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/fg-pasuruan/weight",
     *     tags={"FG Pasuruan"},
     *     summary="Kirim data berat timbangan FG Pasuruan",
     *     description="Menerima data berat dari ESP8266/Arduino dan menyimpannya ke database. Broadcast via WebSocket ke dashboard real-time.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"token", "berat"},
     *                 @OA\Property(property="token", type="string", example="DEV-TOKEN-FG-PSN-001", description="Token unik perangkat"),
     *                 @OA\Property(property="berat", type="number", format="float", example=25.5, description="Berat dalam kg"),
     *                 @OA\Property(property="kode_produksi", type="string", example="KP-2024-001", description="Kode produksi (opsional jika sudah ada sesi aktif)"),
     *                 @OA\Property(property="produk_id", type="integer", example=1, description="ID produk (opsional)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berat berhasil disimpan",
     *         @OA\JsonContent(ref="#/components/schemas/WeightSuccessResponse")
     *     ),
     *     @OA\Response(response=401, description="Token tidak valid", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=400, description="Kode produksi belum diinput atau berat tidak valid", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=403, description="Tidak ada operator aktif", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     *
     * POST /api/v1/fg-pasuruan/weight
     */
    public function receiveWeight(Request $request)
    {
        $validated = $request->validate([
            'token'         => 'required|string',
            'kode_produksi' => 'nullable|string',
            'berat'         => 'required|numeric|min:0',
            'produk_id'     => 'nullable|exists:produks,id',
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

        // 3. Jika tidak ketemu, cari record 'menunggu' terbaru untuk operator ini
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

            $kodeInput = $validated['kode_produksi'] ?? null;

            if (empty($kodeInput) || $kodeInput === '-') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kode Produksi (KP-) belum diinput. Silahkan input manual.'
                ], 400);
            }

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

            broadcast(new WeightReceived('fg', [
                'weight' => $berat,
                'operator' => $activeOperator->name,
                'product' => $produk->nama_produk,
                'kode_produksi' => $kode_produksi,
                'status' => 'selesai'
            ]));

            return response()->json([
                'status' => 'success',
                'message' => "Kode-Produksi: {$kode_produksi}",
                'kode_produksi' => $kode_produksi
            ]);
        }

        // 5. MANUAL UPDATE: If we found a pending record (Legacy mode)
        $produk = $penimbangan->produk;

        $penimbangan->update([
            'berat'     => $berat,
            'device_id' => $device->id,
            'status'    => 'selesai',
        ]);

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
     * @OA\Post(
     *     path="/api/v1/fg-pasuruan/device/update-product",
     *     tags={"FG Pasuruan"},
     *     summary="Update produk yang sedang ditimbang oleh perangkat",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"token", "produk_id"},
     *                 @OA\Property(property="token", type="string", example="DEV-TOKEN-FG-PSN-001"),
     *                 @OA\Property(property="produk_id", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Produk perangkat berhasil diupdate"),
     *     @OA\Response(response=401, description="Token tidak valid", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     *
     * POST /api/v1/fg-pasuruan/device/update-product
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
     * @OA\Post(
     *     path="/api/v1/fg-pasuruan/ping",
     *     tags={"FG Pasuruan"},
     *     summary="Ping perangkat timbangan FG Pasuruan",
     *     description="Digunakan perangkat untuk memperbarui status online dan mengecek koneksi ke server.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(ref="#/components/schemas/PingRequest")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Pong", @OA\JsonContent(ref="#/components/schemas/PingResponse")),
     *     @OA\Response(response=401, description="Token tidak valid", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     * @OA\Get(
     *     path="/api/v1/fg-pasuruan/ping",
     *     tags={"FG Pasuruan"},
     *     summary="Ping perangkat timbangan FG Pasuruan (GET)",
     *     @OA\Parameter(name="token", in="query", required=true, @OA\Schema(type="string", example="DEV-TOKEN-FG-PSN-001")),
     *     @OA\Response(response=200, description="Pong", @OA\JsonContent(ref="#/components/schemas/PingResponse")),
     *     @OA\Response(response=401, description="Token tidak valid", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     *
     * POST/GET /api/v1/fg-pasuruan/ping
     */
    public function ping(Request $request)
    {
        $token = $request->input('token') ?? $request->query('token');
        $device = Device::where('device_token', $token)->first();

        if ($device) {
            $device->update(['last_online' => now()]);

            $response = [
                'status' => 'success', // Kept for backward compatibility
                'message' => 'Timbangan Active',
                'device' => $device->device_name,
                'server_time' => now()->toDateTimeString(),
                'total_penimbangan_sesi' => 0,
                'total_berat_sesi' => 0,
                'berat_sebelumnya' => 0,
            ];

            $activeOperator = ShiftService::getActiveOperator();
            $session = null;

            if ($activeOperator) {
                $session = cache()->get("session_operator_{$activeOperator->id}");
            }

            if ($session && $activeOperator) {
                $query = Penimbangan::where('user_id', $activeOperator->id)
                    ->where('kode_produksi', $session['kode_produksi'])
                    ->where('status', 'selesai')
                    ->whereDate('created_at', today());

                $response['total_penimbangan_sesi'] = $query->count();
                $response['total_berat_sesi'] = (float) $query->sum('berat');

                $lastRecord = Penimbangan::where('user_id', $activeOperator->id)
                    ->where('kode_produksi', $session['kode_produksi'])
                    ->where('status', 'selesai')
                    ->latest('id')
                    ->first();

                if ($lastRecord) {
                    $response['berat_sebelumnya'] = (float) $lastRecord->berat;
                }
            }

            return response()->json($response);
        }

        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }
}
