<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_role_helpers(): void
    {
        $admin    = User::factory()->admin()->create();
        $operator = User::factory()->operator()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isOperator());
        $this->assertTrue($operator->isOperator());
        $this->assertFalse($operator->isAdmin());
    }

    public function test_session_not_locked_returns_false(): void
    {
        $operator = User::factory()->operator()->create(['session_locked' => false]);

        $this->assertFalse($operator->isSessionLocked());
    }

    public function test_admin_is_never_session_locked(): void
    {
        $admin = User::factory()->admin()->create(['session_locked' => true]);

        $this->assertFalse($admin->isSessionLocked());
    }

    public function test_lock_from_previous_shift_is_auto_unlocked(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 10, 12, 0, 0)); // siang

        $operator = User::factory()->operator()->create([
            'session_locked' => true,
            'shift_start'    => '08:00:00',
        ]);

        // Dikunci kemarin (shift sebelumnya) → harus auto-unlock
        cache()->put("session_locked_at_{$operator->id}", Carbon::create(2026, 1, 9, 9, 0, 0), now()->addDays(2));

        $this->assertFalse($operator->isSessionLocked());
        $this->assertFalse((bool) $operator->fresh()->session_locked);
    }

    public function test_lock_within_current_shift_stays_locked(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 10, 12, 0, 0));

        $operator = User::factory()->operator()->create([
            'session_locked' => true,
            'shift_start'    => '08:00:00',
        ]);

        // Dikunci jam 09:00 hari ini (setelah shift mulai 08:00) → tetap terkunci
        cache()->put("session_locked_at_{$operator->id}", Carbon::create(2026, 1, 10, 9, 0, 0), now()->addDays(2));

        $this->assertTrue($operator->isSessionLocked());
    }
}
