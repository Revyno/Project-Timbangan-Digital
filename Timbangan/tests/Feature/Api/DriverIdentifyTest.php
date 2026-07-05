<?php

namespace Tests\Feature\Api;

use App\Models\Driver;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Endpoint identifikasi driver via QR (/api/v1/driver/identify & /api/driver/identify).
 */
class DriverIdentifyTest extends TestCase
{
    use RefreshDatabase;

    public function test_identifies_driver_by_qr_code(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'PT Singkong Jaya']);
        $driver   = Driver::factory()->create([
            'name'        => 'Ahmad Sopir',
            'supplier_id' => $supplier->id,
            'qr_code'     => 'DRV-QR-123',
            'nomor_plat'  => 'L 1234 AB',
        ]);

        $this->postJson('/api/v1/driver/identify', ['qr_code' => 'DRV-QR-123'])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'driver'  => [
                    'id'         => $driver->id,
                    'name'       => 'Ahmad Sopir',
                    'supplier'   => 'PT Singkong Jaya',
                    'nomor_plat' => 'L 1234 AB',
                ],
            ]);
    }

    public function test_unknown_qr_code_returns_not_found(): void
    {
        $this->postJson('/api/v1/driver/identify', ['qr_code' => 'TIDAK-ADA'])
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    public function test_qr_code_is_required(): void
    {
        $this->postJson('/api/v1/driver/identify', [])
            ->assertStatus(422);
    }

    public function test_legacy_endpoint_also_works(): void
    {
        $supplier = Supplier::factory()->create();
        Driver::factory()->create(['qr_code' => 'DRV-LEGACY-1', 'supplier_id' => $supplier->id]);

        $this->postJson('/api/driver/identify', ['qr_code' => 'DRV-LEGACY-1'])
            ->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
