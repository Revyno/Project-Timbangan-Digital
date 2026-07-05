<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kontrol akses dashboard berdasarkan peran.
 */
class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_admin_can_open_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
    }

    public function test_operator_can_open_dashboard(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)->get('/dashboard')->assertOk();
    }

    public function test_operator_cannot_access_admin_only_route(): void
    {
        $operator = User::factory()->operator()->create();

        // Route /admin/master/login-logs dibatasi middleware role:admin
        $this->actingAs($operator)->get('/admin/master/login-logs')->assertForbidden();
    }

    public function test_admin_can_access_login_logs(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/master/login-logs')->assertOk();
    }
}
