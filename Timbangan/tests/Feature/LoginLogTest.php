<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Login yang berhasil harus dicatat ke tabel login_logs.
 */
class LoginLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_is_recorded(): void
    {
        $user = User::factory()->operator()->create();

        $this->post('/', [
            'email'    => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('login_logs', ['user_id' => $user->id]);
    }

    public function test_failed_login_is_not_recorded(): void
    {
        $user = User::factory()->operator()->create();

        $this->post('/', [
            'email'    => $user->email,
            'password' => 'salah-password',
        ]);

        $this->assertGuest();
        $this->assertDatabaseCount('login_logs', 0);
    }
}
