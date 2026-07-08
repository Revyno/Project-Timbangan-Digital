<?php

namespace Tests\Feature\Hmi;

use App\Events\ScaleReading;
use App\Events\WeightReceived;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Ingest sync di server ONLINE: proteksi token, upsert idempoten by uuid,
 * dan broadcast hanya untuk baris baru (retry tidak mengirim notifikasi ganda).
 */
class SyncIngestTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'secret-sync-token';

    protected function setUp(): void
    {
        parent::setUp();
        config(['hmi.role' => 'online', 'hmi.online.token' => self::TOKEN]);
    }

    private function payload(string $uuid, string $item = 'Garam'): array
    {
        return [
            'uuid'          => $uuid,
            'menu'          => 'bahan-baku',
            'site'          => 'pabrik-a',
            'operator_name' => 'Amrozi',
            'tanggal'       => now()->toDateString(),
            'nama_item'     => $item,
            'berat'         => 50.70,
            'unit'          => 'kg',
        ];
    }

    public function test_rejects_missing_token(): void
    {
        $this->postJson('/api/v1/sync/weighings', ['weighings' => [$this->payload(Str::uuid())]])
            ->assertStatus(401);
    }

    public function test_rejects_wrong_token(): void
    {
        $this->withHeaders(['X-Sync-Token' => 'salah'])
            ->postJson('/api/v1/sync/weighings', ['weighings' => [$this->payload(Str::uuid())]])
            ->assertStatus(401);
    }

    public function test_rejects_when_role_is_not_online(): void
    {
        config(['hmi.role' => 'local']);

        $this->withHeaders(['X-Sync-Token' => self::TOKEN])
            ->postJson('/api/v1/sync/weighings', ['weighings' => [$this->payload(Str::uuid())]])
            ->assertStatus(403);
    }

    public function test_stores_batch_and_broadcasts_each_new_row(): void
    {
        Event::fake([WeightReceived::class]);

        $a = (string) Str::uuid();
        $b = (string) Str::uuid();

        $this->withHeaders(['X-Sync-Token' => self::TOKEN])
            ->postJson('/api/v1/sync/weighings', [
                'weighings' => [$this->payload($a, 'Garam'), $this->payload($b, 'Gula Pasir')],
            ])
            ->assertOk()
            ->assertJson(['status' => 'ok', 'synced' => 2]);

        $this->assertDatabaseCount('hmi_weighings', 2);
        $this->assertDatabaseHas('hmi_weighings', ['uuid' => $a, 'sync_status' => 'synced']);
        Event::assertDispatchedTimes(WeightReceived::class, 2);
    }

    public function test_is_idempotent_and_does_not_rebroadcast(): void
    {
        Event::fake([WeightReceived::class]);

        $a = (string) Str::uuid();
        $b = (string) Str::uuid();

        // Kirim A
        $this->withHeaders(['X-Sync-Token' => self::TOKEN])
            ->postJson('/api/v1/sync/weighings', ['weighings' => [$this->payload($a)]])
            ->assertOk();

        // Kirim ulang A (retry) + B baru
        $this->withHeaders(['X-Sync-Token' => self::TOKEN])
            ->postJson('/api/v1/sync/weighings', ['weighings' => [$this->payload($a), $this->payload($b)]])
            ->assertOk();

        // Tidak ada duplikat (A hanya 1 baris), dan broadcast total 2 (A sekali, B sekali).
        $this->assertDatabaseCount('hmi_weighings', 2);
        Event::assertDispatchedTimes(WeightReceived::class, 2);
    }

    public function test_event_broadcast_contracts(): void
    {
        // Fix inti: WeightReceived ter-QUEUE (ShouldBroadcast), BUKAN ShouldBroadcastNow.
        $wr = class_implements(WeightReceived::class);
        $this->assertContains(ShouldBroadcast::class, $wr);
        $this->assertNotContains(ShouldBroadcastNow::class, $wr);

        // Berat live sebaliknya: ShouldBroadcastNow (latensi rendah, tanpa antre DB).
        $this->assertContains(ShouldBroadcastNow::class, class_implements(ScaleReading::class));
    }
}
