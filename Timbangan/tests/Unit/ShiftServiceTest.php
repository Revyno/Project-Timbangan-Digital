<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\ShiftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ShiftServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bekukan waktu di siang hari agar shift 08:00–16:00 deterministik.
        Carbon::setTestNow(Carbon::create(2026, 1, 10, 12, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_admin_can_always_access(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue(ShiftService::canAccess($admin));
    }

    public function test_operator_inside_shift_can_access(): void
    {
        $operator = User::factory()->operator()->create([
            'shift_start' => '08:00:00',
            'shift_end'   => '16:00:00',
        ]);

        $this->assertTrue(ShiftService::canAccess($operator));
    }

    public function test_operator_outside_shift_cannot_access(): void
    {
        $operator = User::factory()->operator()->create([
            'shift_start' => '13:00:00', // mulai setelah jam 12:00
            'shift_end'   => '20:00:00',
        ]);

        $this->assertFalse(ShiftService::canAccess($operator));
    }

    public function test_get_active_operator_returns_operator_on_shift(): void
    {
        $operator = User::factory()->operator()->create([
            'shift_start' => '08:00:00',
            'shift_end'   => '16:00:00',
        ]);

        $active = ShiftService::getActiveOperator();

        $this->assertNotNull($active);
        $this->assertSame($operator->id, $active->id);
    }

    public function test_get_active_operator_ignores_locked_operator(): void
    {
        User::factory()->operator()->create([
            'shift_start'    => '08:00:00',
            'shift_end'      => '16:00:00',
            'session_locked' => true,
        ]);

        $this->assertNull(ShiftService::getActiveOperator());
    }
}
