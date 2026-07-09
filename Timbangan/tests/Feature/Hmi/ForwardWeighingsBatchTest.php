<?php

namespace Tests\Feature\Hmi;

use App\Jobs\ForwardWeighingsBatch;
use App\Models\HmiWeighing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Job forward (server LOKAL -> ONLINE): batch, tandai synced saat sukses,
 * biarkan pending saat online down (tahan gagal), dan tidak jalan di role online.
 */
class ForwardWeighingsBatchTest extends TestCase
{
    use RefreshDatabase;

    private function pendingRow(string $item = 'Garam'): HmiWeighing
    {
        return HmiWeighing::create([
            'menu'          => 'bahan-baku',
            'operator_name' => 'Amrozi',
            'tanggal'       => now()->toDateString(),
            'nama_item'     => $item,
            'berat'         => 50.70,
            'unit'          => 'kg',
            'sync_status'   => 'pending',
        ]);
    }

    public function test_forwards_pending_rows_and_marks_synced(): void
    {
        config([
            'hmi.role'            => 'local',
            'hmi.online.base_url' => 'https://online.test',
            'hmi.online.token'    => 'secret',
        ]);

        // Balas acks per-uuid yang dikirim.
        Http::fake([
            'online.test/*' => fn ($request) => Http::response([
                'status' => 'ok',
                'acks'   => collect($request['weighings'])->mapWithKeys(
                    fn ($w, $i) => [$w['uuid'] => 1000 + $i]
                )->all(),
            ], 200),
        ]);

        $a = $this->pendingRow('Garam');
        $b = $this->pendingRow('Gula Pasir');

        (new ForwardWeighingsBatch)->handle();

        foreach ([$a, $b] as $row) {
            $row->refresh();
            $this->assertSame('synced', $row->sync_status);
            $this->assertNotNull($row->synced_at);
            $this->assertNotNull($row->online_id);
        }

        Http::assertSent(fn ($request) =>
            $request->url() === 'https://online.test/api/v1/sync/weighings'
            && $request->hasHeader('X-Sync-Token', 'secret')
        );
    }

    public function test_leaves_rows_pending_when_online_fails(): void
    {
        config([
            'hmi.role'            => 'local',
            'hmi.online.base_url' => 'https://online.test',
            'hmi.online.token'    => 'secret',
        ]);
        Http::fake(['online.test/*' => Http::response('server error', 500)]);

        $row = $this->pendingRow();

        (new ForwardWeighingsBatch)->handle();

        $row->refresh();
        $this->assertSame('pending', $row->sync_status);
        $this->assertNull($row->synced_at);
    }

    public function test_skips_when_online_url_not_configured(): void
    {
        config(['hmi.role' => 'local', 'hmi.online.base_url' => '', 'hmi.online.token' => '']);
        Http::fake();

        $row = $this->pendingRow();
        (new ForwardWeighingsBatch)->handle();

        $this->assertSame('pending', $row->refresh()->sync_status);
        Http::assertNothingSent();
    }

    public function test_does_nothing_on_online_role(): void
    {
        config(['hmi.role' => 'online']);
        Http::fake();

        $row = $this->pendingRow();
        (new ForwardWeighingsBatch)->handle();

        $this->assertSame('pending', $row->refresh()->sync_status);
        Http::assertNothingSent();
    }
}
