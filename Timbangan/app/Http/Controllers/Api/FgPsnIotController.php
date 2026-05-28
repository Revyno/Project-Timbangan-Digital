<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Penimbangan;
use App\Models\Produk;
use App\Models\User;
use App\Events\WeightReceived;
use Illuminate\Http\Request;

class FgPsnIotController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/fg-psn/settings",
     *     tags={"FG PSN"},
     *     summary="Get pengaturan sesi aktif timbangan FG PSN",
     *     description="Dipanggil oleh Arduino/ESP8266 untuk mendapatkan informasi sesi aktif operator FG PSN.",
     *     @OA\Parameter(
     *         name="token",
     *         in="query",
     *         required=true,
     *         description="Token unik perangkat timbangan",
     *         @OA\Schema(type="string", example="DEV-TOKEN-FG-PSN-001")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Sukses",
     *         @OA\JsonContent(ref="#/components/schemas/SettingsResponse")
     *     ),
     *     @OA\Response(response=401, description="Token tidak valid", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function getSettings(Request $request)
    {
        $token = $request->query('token');
        $device = Device::where('device_token', $token)->first();

        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $device->update(['last_online' => now()]);

        $operator = User::where('tipe', 'fg_psn')
            ->where('role', 'operator')
            ->where('session_locked', false)
            ->first();

        if (!$operator) {
            return response()->json(['status' => 'idle', 'message' => 'Tidak ada operator aktif', 'operator' => 'N/A']);
        }

        $session = cache()->get("session_fg_psn_{$operator->id}");

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

    /**
     * @OA\Post(
     *     path="/api/v1/fg-psn/weight",
     *     tags={"FG PSN"},
     *     summary="Kirim data berat timbangan FG PSN",
     *     description="Menerima data berat dari ESP8266/Arduino untuk modul Finished Goods PSN.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"token", "weight"},
     *                 @OA\Property(property="token", type="string", example="DEV-TOKEN-FG-PSN-001"),
     *                 @OA\Property(property="weight", type="number", format="float", example=10.25, description="Berat dalam kg")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Berat berhasil disimpan", @OA\JsonContent(ref="#/components/schemas/WeightSuccessResponse")),
     *     @OA\Response(response=400, description="Berat tidak valid / Tidak ada sesi aktif", @OA\JsonContent(ref="#/components/schemas/ErrorResponse")),
     *     @OA\Response(response=401, description="Token tidak valid", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
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

        $operator = User::where('tipe', 'fg_psn')
            ->where('role', 'operator')
            ->where('session_locked', false)
            ->first();

        if (!$operator) {
            return response()->json(['status' => 'error', 'message' => 'No active operator'], 400);
        }

        $session = cache()->get("session_fg_psn_{$operator->id}");

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

        broadcast(new WeightReceived('fg_psn', [
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

    /**
     * @OA\Post(
     *     path="/api/v1/fg-psn/ping",
     *     tags={"FG PSN"},
     *     summary="Ping perangkat timbangan FG PSN",
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
     *     path="/api/v1/fg-psn/ping",
     *     tags={"FG PSN"},
     *     summary="Ping perangkat timbangan FG PSN (GET)",
     *     @OA\Parameter(name="token", in="query", required=true, @OA\Schema(type="string", example="DEV-TOKEN-FG-PSN-001")),
     *     @OA\Response(response=200, description="Pong", @OA\JsonContent(ref="#/components/schemas/PingResponse")),
     *     @OA\Response(response=401, description="Token tidak valid", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
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
