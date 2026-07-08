<?php

namespace App\Jobs;

use App\Models\HmiWeighing;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Server LOKAL (edge): kirim baris timbangan pending ke server ONLINE secara
 * BATCH. Inilah inti "optimasi agar server tidak meledak":
 *
 *  - Coalescing  : banyak PRINT digabung jadi sedikit HTTP call (batch_size).
 *  - No-overlap  : WithoutOverlapping memastikan hanya SATU forward berjalan,
 *                  jadi tidak ada belasan job paralel membanjiri server online.
 *  - Tahan mati  : jika online down, baris tetap `pending` dan dicoba lagi di
 *                  run berikutnya (scheduler tiap menit). Tidak pernah throw.
 *  - Idempoten   : server online upsert by uuid, retry aman.
 */
class ForwardWeighingsBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function middleware(): array
    {
        // Kunci 10 menit; rilis begitu job selesai. Cegah forward paralel.
        return [(new WithoutOverlapping('hmi-forward'))->expireAfter(600)->dontRelease()];
    }

    public function handle(): void
    {
        if (config('hmi.role') !== 'local') {
            return; // server online/all-in-one tidak mem-forward apa pun.
        }

        $baseUrl = config('hmi.online.base_url');
        $token   = config('hmi.online.token');

        if (empty($baseUrl) || empty($token)) {
            Log::warning('[HMI sync] ONLINE_SYNC_URL / ONLINE_SYNC_TOKEN belum diisi — forward dilewati.');
            return;
        }

        $batchSize = (int) config('hmi.sync.batch_size', 50);
        $maxRuns   = (int) config('hmi.sync.max_batches_per_run', 10);
        $timeout   = (int) config('hmi.sync.http_timeout', 15);
        $endpoint  = $baseUrl . '/api/v1/sync/weighings';

        for ($run = 0; $run < $maxRuns; $run++) {
            $pending = HmiWeighing::pendingSync()->orderBy('id')->limit($batchSize)->get();

            if ($pending->isEmpty()) {
                break;
            }

            $payload = $pending->map->toSyncPayload()->all();

            try {
                $response = Http::acceptJson()
                    ->withHeaders(['X-Sync-Token' => $token])
                    ->timeout($timeout)
                    ->post($endpoint, ['weighings' => $payload]);
            } catch (\Throwable $e) {
                Log::warning('[HMI sync] Gagal menghubungi server online: ' . $e->getMessage());
                break; // biarkan pending, coba lagi run berikutnya.
            }

            if (! $response->successful()) {
                Log::warning('[HMI sync] Server online membalas ' . $response->status() . ': ' . $response->body());
                break;
            }

            $acks = (array) $response->json('acks', []);

            foreach ($pending as $row) {
                $row->update([
                    'sync_status' => 'synced',
                    'synced_at'   => now(),
                    'online_id'   => $acks[$row->uuid] ?? null,
                ]);
            }

            // Kurang dari satu batch penuh -> tidak ada sisa, hentikan.
            if ($pending->count() < $batchSize) {
                break;
            }
        }
    }
}
