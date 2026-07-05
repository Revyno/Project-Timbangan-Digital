<?php

namespace Tests\Feature\Api;

use Tests\TestCase;

/**
 * Endpoint health-check (/api/v1/status & /api/status).
 */
class HealthCheckTest extends TestCase
{
    public function test_v1_status_is_online(): void
    {
        $this->getJson('/api/v1/status')
            ->assertStatus(200)
            ->assertJson([
                'status'  => 'online',
                'version' => 'v1',
            ])
            ->assertJsonStructure(['status', 'version', 'app', 'server_time']);
    }

    public function test_legacy_status_is_online(): void
    {
        $this->getJson('/api/status')
            ->assertStatus(200)
            ->assertJson(['status' => 'online']);
    }
}
