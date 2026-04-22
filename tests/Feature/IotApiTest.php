<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Penimbangan;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IotApiTest extends TestCase
{
    use RefreshDatabase;

    protected $device;
    protected $user;
    protected $produk;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test user
        $this->user = User::create([
            'name' => 'Operator Test',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'operator',
            'shift' => '1'
        ]);

        // Create a test product
        $this->produk = Produk::create([
            'nama_produk' => 'Baking Powder',
            'target_berat' => 10.0
        ]);

        // Create a test device
        $this->device = Device::create([
            'device_code' => 'ARD-001',
            'device_name' => 'Test Scale',
            'device_token' => 'test_token_123',
            'is_active' => true
        ]);
    }

    /** @test */
    public function it_can_ping_the_server()
    {
        $response = $this->postJson('/api/iot/ping', [
            'token' => 'test_token_123'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'alive']);
        
        $this->assertNotNull($this->device->fresh()->last_online);
    }

    /** @test */
    public function it_returns_error_for_invalid_token()
    {
        $response = $this->postJson('/api/iot/ping', [
            'token' => 'wrong_token'
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_fetch_latest_pending_settings()
    {
        // 1. Create a pending record
        $penimbangan = Penimbangan::create([
            'tanggal_penimbangan' => now(),
            'produk_id' => $this->produk->id,
            'user_id' => $this->user->id,
            'kode_produksi' => 'LOT-TEST-001',
            'status' => 'menunggu'
        ]);

        // 2. Fetch settings from API
        $response = $this->getJson('/api/iot/settings?token=test_token_123');

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'ready',
                     'kode_produksi' => 'LOT-TEST-001',
                     'nama_produk' => 'Baking Powder'
                 ]);
    }

    /** @test */
    public function it_can_submit_weight_data()
    {
        // 1. Create a pending record
        $penimbangan = Penimbangan::create([
            'tanggal_penimbangan' => now(),
            'produk_id' => $this->produk->id,
            'user_id' => $this->user->id,
            'kode_produksi' => 'LOT-TEST-001',
            'status' => 'menunggu'
        ]);

        // 2. Submit weight
        $response = $this->postJson('/api/iot/weight', [
            'token' => 'test_token_123',
            'kode_produksi' => 'LOT-TEST-001',
            'berat' => 10.5
        ]);

        $response->assertStatus(200)
                 ->assertJson(['status' => 'success']);

        // 3. Verify record updated
        $updated = $penimbangan->fresh();
        $this->assertEquals('selesai', $updated->status);
        $this->assertEquals(10.5, $updated->berat);
        $this->assertEquals(0.5, $updated->selisih); // 10.5 - 10.0
    }
}
