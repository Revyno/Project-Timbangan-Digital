<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HmiWeighing;
use App\Services\HmiBroadcaster;
use Illuminate\Http\Request;

/**
 * Ingest sinkronisasi di server ONLINE (dilindungi middleware sync.token).
 *
 * Menerima BATCH baris timbangan dari server lokal, meng-upsert berdasarkan
 * `uuid` (idempoten — retry aman), lalu broadcast HANYA untuk baris yang benar-
 * benar baru sehingga retry tidak mengirim toast/notifikasi ganda.
 *
 * device_id/user_id lokal sengaja TIDAK disalin (id-nya tidak berlaku di sini);
 * identitas operator dibawa lewat kolom denormalized operator_name/karu_name.
 */
class SyncController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'weighings'                => 'required|array|min:1',
            'weighings.*.uuid'         => 'required|uuid',
            'weighings.*.menu'         => 'required|string|max:50',
            'weighings.*.nama_item'    => 'required|string|max:255',
            'weighings.*.berat'        => 'required|numeric',
            'weighings.*.tanggal'      => 'required|date',
            'weighings.*.operator_name' => 'required|string|max:255',
            'weighings.*.site'         => 'nullable|string|max:100',
            'weighings.*.karu_name'    => 'nullable|string|max:255',
            'weighings.*.shift'        => 'nullable|string|max:50',
            'weighings.*.produk'       => 'nullable|string|max:255',
            'weighings.*.kode_batch'   => 'nullable|string|max:255',
            'weighings.*.target'       => 'nullable|numeric',
            'weighings.*.unit'         => 'nullable|string|max:8',
            'weighings.*.selisih'      => 'nullable|numeric',
            'weighings.*.timbangan_ke' => 'nullable|integer',
        ]);

        $rows  = collect($data['weighings']);
        $known = HmiWeighing::whereIn('uuid', $rows->pluck('uuid'))->pluck('uuid')->flip();

        $acks = [];

        foreach ($rows as $r) {
            $isNew = ! $known->has($r['uuid']);

            $weighing = HmiWeighing::updateOrCreate(
                ['uuid' => $r['uuid']],
                [
                    'menu'          => $r['menu'],
                    'site'          => $r['site'] ?? 'default',
                    'operator_name' => $r['operator_name'],
                    'karu_name'     => $r['karu_name'] ?? null,
                    'shift'         => $r['shift'] ?? null,
                    'tanggal'       => $r['tanggal'],
                    'produk'        => $r['produk'] ?? null,
                    'nama_item'     => $r['nama_item'],
                    'kode_batch'    => $r['kode_batch'] ?? null,
                    'target'        => $r['target'] ?? null,
                    'berat'         => $r['berat'],
                    'unit'          => $r['unit'] ?? 'kg',
                    'selisih'       => $r['selisih'] ?? null,
                    'timbangan_ke'  => $r['timbangan_ke'] ?? null,
                    'sync_status'   => 'synced',
                    'synced_at'     => now(),
                ]
            );

            if ($isNew) {
                HmiBroadcaster::weighing($weighing);
            }

            $acks[$r['uuid']] = $weighing->id;
        }

        return response()->json([
            'status' => 'ok',
            'synced' => count($acks),
            'acks'   => $acks, // uuid => id di server online
        ]);
    }
}
