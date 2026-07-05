<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Penimbangan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Endpoint IoT legacy FG Pasuruan (/api/iot/*) — App\Http\Controllers\Api\IotController.
 * Alur menggunakan ShiftService::getActiveOperator() + sesi cache operator.
 */
class IotApiTest extends TestCase
{
    use RefreshDatabase;

    private Device $device;
    private User $operator;
    private Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();

        // Operator dengan shift mencakup waktu sekarang → jadi "active operator".
        $this->operator = User::factory()->operator('fg')->create(['name' => 'Operator FG']);
        $this->produk   = Produk::factory()->create(['nama_produk' => 'Baking Powder', 'target_berat' => 10.0]);
        $this->device   = Device::factory()->create(['device_token' => 'test_token_123']);
    }

    public function test_ping_updates_last_online_and_returns_success(): void
    {
        $this->postJson('/api/iot/ping', ['token' => 'test_token_123'])
            ->assertStatus(200)
            ->assertJson(['status' => 'success']);

        $this->assertNotNull($this->device->fresh()->last_online);
    }

    public function test_ping_with_invalid_token_is_unauthorized(): void
    {
        $this->postJson('/api/iot/ping', ['token' => 'wrong_token'])
            ->assertStatus(401);
    }

    public function test_settings_returns_ready_when_pending_record_exists(): void
    {
        Penimbangan::factory()->create([
            'user_id'       => $this->operator->id,
            'produk_id'     => $this->produk->id,
            'kode_produksi' => 'LOT-TEST-001',
            'status'        => 'menunggu',
        ]);

        $this->getJson('/api/iot/settings?token=test_token_123')
            ->assertStatus(200)
            ->assertJson([
                'status'        => 'ready',
                'kode_produksi' => 'LOT-TEST-001',
                'nama_produk'   => 'Baking Powder',
            ]);
    }

    public function test_settings_returns_idle_when_no_session(): void
    {
        $this->getJson('/api/iot/settings?token=test_token_123')
            ->assertStatus(200)
            ->assertJson(['status' => 'idle']);
    }

    public function test_weight_from_cache_session_creates_completed_record(): void
    {
        cache()->put("session_operator_{$this->operator->id}", [
            'produk_id'       => $this->produk->id,
            'kode_produksi'   => 'LOT-CACHE-001',
            'tanggal_expired' => now()->addYear()->toDateString(),
        ], now()->addHour());

        $this->postJson('/api/iot/weight', [
            'token' => 'test_token_123',
            'berat' => 12.5,
        ])->assertStatus(200)->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('penimbangans', [
            'kode_produksi' => 'LOT-CACHE-001',
            'user_id'       => $this->operator->id,
            'status'        => 'selesai',
            'berat'         => 12.5,
        ]);
    }

    public function test_weight_updates_existing_pending_record(): void
    {
        $penimbangan = Penimbangan::factory()->create([
            'user_id'       => $this->operator->id,
            'produk_id'     => $this->produk->id,
            'kode_produksi' => 'LOT-PENDING-001',
            'status'        => 'menunggu',
        ]);

        $this->postJson('/api/iot/weight', [
            'token'         => 'test_token_123',
            'kode_produksi' => 'LOT-PENDING-001',
            'berat'         => 10.5,
        ])->assertStatus(200)->assertJson(['status' => 'success']);

        $updated = $penimbangan->fresh();
        $this->assertSame('selesai', $updated->status);
        $this->assertEquals(10.5, (float) $updated->berat);
    }

    public function test_weight_with_invalid_token_is_unauthorized(): void
    {
        $this->postJson('/api/iot/weight', [
            'token' => 'wrong_token',
            'berat' => 5,
        ])->assertStatus(401);
    }
}
