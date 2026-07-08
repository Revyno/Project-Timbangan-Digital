<?php

namespace Tests\Feature\Hmi;

use App\Events\WeightReceived;
use App\Models\HmiWeighing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Kiosk HMI: render halaman + alur PRINT (satu-satunya pemicu simpan/kirim) +
 * berat live yang ephemeral (tidak tersimpan).
 */
class KioskPrintTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->operator = User::factory()->operator()->create();
    }

    public function test_operator_can_open_kiosk_pages(): void
    {
        $this->actingAs($this->operator)->get('/kiosk/bahan-baku')->assertOk();
        $this->actingAs($this->operator)->get('/kiosk/formulasi')->assertOk();
    }

    public function test_guest_is_redirected_from_kiosk(): void
    {
        $this->get('/kiosk/bahan-baku')->assertRedirect(route('login'));
    }

    public function test_admin_may_also_open_kiosk_via_role_bypass(): void
    {
        // RoleMiddleware memberi admin bypass ke semua route role-restricted.
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/kiosk/bahan-baku')->assertOk();
    }

    public function test_print_creates_pending_row_on_local_role(): void
    {
        // Edge/local + tanpa ONLINE_SYNC_URL -> job forward no-op, baris tetap pending.
        config(['hmi.role' => 'local', 'hmi.online.base_url' => '', 'hmi.online.token' => '']);

        $this->actingAs($this->operator)
            ->postJson(route('kiosk.print', ['menu' => 'bahan-baku']), [
                'nama_item' => 'Garam',
                'berat'     => 50.70,
                'unit'      => 'kg',
                'kode_batch' => 'PUTR-120924',
            ])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('hmi_weighings', [
            'menu'          => 'bahan-baku',
            'nama_item'     => 'Garam',
            'operator_name' => $this->operator->name,
            'kode_batch'    => 'PUTR-120924',
            'sync_status'   => 'pending',
            'timbangan_ke'  => 1,
        ]);
    }

    public function test_print_marks_synced_and_broadcasts_on_online_role(): void
    {
        config(['hmi.role' => 'online']);
        Event::fake([WeightReceived::class]);

        $this->actingAs($this->operator)
            ->postJson(route('kiosk.print', ['menu' => 'formulasi']), [
                'produk'    => 'Cookies Blackmond',
                'nama_item' => 'Garam',
                'berat'     => 250.02,
                'target'    => 250,
                'unit'      => 'gr',
            ])
            ->assertOk();

        $row = HmiWeighing::first();
        $this->assertNotNull($row);
        $this->assertSame('synced', $row->sync_status);
        $this->assertNotNull($row->synced_at);
        $this->assertEquals(0.02, (float) $row->selisih); // berat - target

        Event::assertDispatched(WeightReceived::class, 1);
    }

    public function test_live_weight_is_not_persisted(): void
    {
        $this->actingAs($this->operator)
            ->postJson(route('kiosk.live', ['menu' => 'bahan-baku']), [
                'device' => 'op-1',
                'weight' => 12.34,
                'unit'   => 'kg',
            ])
            ->assertNoContent();

        $this->assertDatabaseCount('hmi_weighings', 0);
    }

    public function test_print_requires_nama_item(): void
    {
        $this->actingAs($this->operator)
            ->postJson(route('kiosk.print', ['menu' => 'bahan-baku']), ['berat' => 10])
            ->assertStatus(422);
    }

    public function test_unknown_menu_is_rejected(): void
    {
        $this->actingAs($this->operator)
            ->postJson('/kiosk/tidak-ada/print', ['nama_item' => 'X', 'berat' => 1])
            ->assertNotFound();
    }
}
