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
   
    public function getSettings(Request $request)
    {
        $token = $request->query('token');
        $device = Device::where('device_token', $token)->first();

        if (!$device) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $device->update(['last_online' => now()]);

        $operators = User::where('role', 'operator')
            ->where('session_locked', false)
            ->get();

        if ($operators->isEmpty()) {
            return response()->json([
                'status' => 'idle',
                'message' => 'Tidak ada operator aktif',
                'operator' => 'N/A',
            ]);
        }

        $activeOperator = null;
        $session = null;

        foreach ($operators as $op) {
            $sess = cache()->get("session_singkong_{$op->id}");
            if ($sess) {
                $activeOperator = $op;
                $session = $sess;
                break;
            }
        }

        if (!$session) {
            return response()->json([
                'status' => 'idle',
                'message' => 'Belum ada sesi aktif',
                'operator' => $operators->first()->name,
            ]);
        }

        $operator = $activeOperator;

        $query = IncomingSingkong::where('user_id', $operator->id)
            ->where('kode_produksi', $session['kode_produksi'])
            ->where('status', 'selesai')
            ->whereDate('created_at', today());

        $totalPenimbangan = $query->count();
        $totalBerat = (float) $query->sum('berat');

        return response()->json([
            'status' => 'ready',
            'kode_produksi' => $session['kode_produksi'],
            'nama_produk' => $session['jenis_singkong'],
            'operator' => $operator->name,
            'expired' => '-',
            'no_surat' => $session['no_surat'],
            'nama_supplier' => $session['nama_supplier'],
            'total_penimbangan_sesi' => $totalPenimbangan,
            'total_berat_sesi' => $totalBerat,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/incoming-singkong/weight",
     *     tags={"Incoming Singkong"},
     *     summary="Kirim data berat timbangan Incoming Singkong",
     *     description="Menyimpan data berat singkong masuk. Membutuhkan sesi aktif yang sudah diinput oleh operator.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/x-www-form-urlencoded",
     *             @OA\Schema(
     *                 required={"token", "weight"},
     *                 @OA\Property(property="token", type="string", example="DEV-TOKEN-SINGKONG-001"),
     *                 @OA\Property(property="weight", type="number", format="float", example=1500.0, description="Berat dalam kg")
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

        $operators = User::where('role', 'operator')
            ->where('session_locked', false)
            ->get();

        if ($operators->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'No active operator'], 400);
        }

        $activeOperator = null;
        $session = null;

        foreach ($operators as $op) {
            $sess = cache()->get("session_singkong_{$op->id}");
            if ($sess) {
                $activeOperator = $op;
                $session = $sess;
                break;
            }
        }

        if (!$session) {
            return response()->json(['status' => 'error', 'message' => 'No active session'], 400);
        }

        $operator = $activeOperator;

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

        try {
            broadcast(new WeightReceived('incoming_singkong', [
                'weight' => $weight,
                'operator' => $operator->name,
                'product' => $session['jenis_singkong'],
                'kode_produksi' => $session['kode_produksi'],
                'status' => 'selesai',
                'ip_address' => $request->ip()
            ]));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Broadcast failed: " . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => "Data berat {$weight} kg tersimpan",
            'record_id' => $record->id,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/incoming-singkong/ping",
     *     tags={"Incoming Singkong"},
     *     summary="Ping perangkat timbangan Incoming Singkong",
     *     @OA\RequestBody(required=true, @OA\MediaType(mediaType="application/x-www-form-urlencoded", @OA\Schema(ref="#/components/schemas/PingRequest"))),
     *     @OA\Response(response=200, description="Pong", @OA\JsonContent(ref="#/components/schemas/PingResponse")),
     *     @OA\Response(response=401, description="Token tidak valid", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     * @OA\Get(
     *     path="/api/v1/incoming-singkong/ping",
     *     tags={"Incoming Singkong"},
     *     summary="Ping perangkat timbangan Incoming Singkong (GET)",
     *     @OA\Parameter(name="token", in="query", required=true, @OA\Schema(type="string", example="DEV-TOKEN-SINGKONG-001")),
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
            
            $response = [
                'status' => 'ok',
                'server_time' => now()->toDateTimeString(),
                'total_penimbangan_sesi' => 0,
                'total_berat_sesi' => 0,
                'berat_sebelumnya' => 0,
            ];

            $operators = User::where('role', 'operator')->where('session_locked', false)->get();
            $session = null;
            $activeOperator = null;

            foreach ($operators as $op) {
                $sess = cache()->get("session_singkong_{$op->id}");
                if ($sess) {
                    $activeOperator = $op;
                    $session = $sess;
                    break;
                }
            }

            if ($session && $activeOperator) {
                $query = IncomingSingkong::where('user_id', $activeOperator->id)
                    ->where('kode_produksi', $session['kode_produksi'])
                    ->where('status', 'selesai')
                    ->whereDate('created_at', today());

                $response['total_penimbangan_sesi'] = $query->count();
                $response['total_berat_sesi'] = (float) $query->sum('berat');

                $lastRecord = IncomingSingkong::where('user_id', $activeOperator->id)
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

        return response()->json(['status' => 'error', 'message' => 'Unknown device'], 401);
    }
}
