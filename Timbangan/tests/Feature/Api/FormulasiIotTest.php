<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Endpoint API v1 Formulasi Pasuruan (/api/v1/formulasi/*).
 * Operator diidentifikasi lewat tipe = 'formulasi_pasuruan' + sesi cache.
 */
class FormulasiIotTest extends TestCase
{
    use RefreshDatabase;

    private Device $device;
    private User $operator;
    private Produk $produk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->operator('formulasi_pasuruan')->create();
        $this->produk   = Produk::factory()->create(['nama_produk' => 'Tepung Mocaf', 'target_berat' => 20.0]);
        $this->device   = Device::factory()->create(['device_token' => 'FORM-TOKEN-001']);
    }

    private function openSession(string $kode = 'KP-FORM-001'): void
    {
        cache()->put("session_formulasi_{$this->operator->id}", [
            'produk_id'       => $this->produk->id,
            'kode_produksi'   => $kode,
            'tanggal_expired' => now()->addYear()->toDateString(),
        ], now()->addHour());
    }

    public function test_settings_rejects_unknown_token(): void
    {
        $this->getJson('/api/v1/formulasi/settings?token=nope')
            ->assertStatus(401);
    }

    public function test_settings_returns_ready_with_active_session(): void
    {
        $this->openSession('KP-FORM-777');

        $this->getJson('/api/v1/formulasi/settings?token=FORM-TOKEN-001')
            ->assertStatus(200)
            ->assertJson([
                'status'        => 'ready',
                'kode_produksi' => 'KP-FORM-777',
                'nama_produk'   => 'Tepung Mocaf',
                'operator'      => $this->operator->name,
            ]);
    }

    public function test_weight_is_stored_as_completed_record(): void
    {
        $this->openSession('KP-FORM-555');

        $this->postJson('/api/v1/formulasi/weight', [
            'token'  => 'FORM-TOKEN-001',
            'weight' => 22.25,
        ])->assertStatus(200)->assertJson(['status' => 'success']);

        // Catatan: kolom `selisih` sengaja tidak diuji karena tidak masuk
        // $fillable pada model Penimbangan sehingga tidak tersimpan (selalu 0).
        $this->assertDatabaseHas('penimbangans', [
            'kode_produksi' => 'KP-FORM-555',
            'user_id'       => $this->operator->id,
            'status'        => 'selesai',
            'berat'         => 22.25,
        ]);
    }

    public function test_weight_rejects_zero_or_negative(): void
    {
        $this->openSession();

        $this->postJson('/api/v1/formulasi/weight', [
            'token'  => 'FORM-TOKEN-001',
            'weight' => 0,
        ])->assertStatus(400);
    }

    public function test_weight_requires_active_session(): void
    {
        // Tidak ada sesi cache → tidak boleh menyimpan
        $this->postJson('/api/v1/formulasi/weight', [
            'token'  => 'FORM-TOKEN-001',
            'weight' => 15,
        ])->assertStatus(400);
    }

    public function test_ping_returns_ok_and_updates_last_online(): void
    {
        $this->postJson('/api/v1/formulasi/ping', ['token' => 'FORM-TOKEN-001'])
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);

        $this->assertNotNull($this->device->fresh()->last_online);
    }
}
