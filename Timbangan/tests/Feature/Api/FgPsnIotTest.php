<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Endpoint API v1 FG PSN (/api/v1/fg-psn/*).
 */
class FgPsnIotTest extends TestCase
{
    use RefreshDatabase;

    private Device $device;
    private User $operator;
    private Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->operator('fg_psn')->create();
        $this->produk   = Produk::factory()->create(['nama_produk' => 'Cookies FG', 'target_berat' => 15.0]);
        $this->device   = Device::factory()->create(['device_token' => 'FGPSN-TOKEN-001']);
    }

    private function openSession(string $kode = 'KP-FGPSN-001'): void
    {
        cache()->put("session_fg_psn_{$this->operator->id}", [
            'produk_id'       => $this->produk->id,
            'kode_produksi'   => $kode,
            'tanggal_expired' => now()->addYear()->toDateString(),
        ], now()->addHour());
    }

    public function test_settings_unknown_token_unauthorized(): void
    {
        $this->getJson('/api/v1/fg-psn/settings?token=nope')->assertStatus(401);
    }

    public function test_settings_ready_with_session(): void
    {
        $this->openSession('KP-FGPSN-9');

        $this->getJson('/api/v1/fg-psn/settings?token=FGPSN-TOKEN-001')
            ->assertStatus(200)
            ->assertJson([
                'status'        => 'ready',
                'kode_produksi' => 'KP-FGPSN-9',
                'nama_produk'   => 'Cookies FG',
            ]);
    }

    public function test_weight_stored_with_session(): void
    {
        $this->openSession('KP-FGPSN-5');

        $this->postJson('/api/v1/fg-psn/weight', [
            'token'  => 'FGPSN-TOKEN-001',
            'weight' => 15.5,
        ])->assertStatus(200)->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('penimbangans', [
            'kode_produksi' => 'KP-FGPSN-5',
            'user_id'       => $this->operator->id,
            'status'        => 'selesai',
            'berat'         => 15.5,
        ]);
    }

    public function test_weight_without_session_fails(): void
    {
        $this->postJson('/api/v1/fg-psn/weight', [
            'token'  => 'FGPSN-TOKEN-001',
            'weight' => 12,
        ])->assertStatus(400);
    }

    public function test_ping_ok(): void
    {
        $this->postJson('/api/v1/fg-psn/ping', ['token' => 'FGPSN-TOKEN-001'])
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }
}
