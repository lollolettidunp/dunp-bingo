<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_configured_admin_reaches_admin_routes(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);

        $this->actingAs(User::factory()->create(['email' => 'user@azienda.it']))
            ->get('/admin')
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['email' => 'admin@azienda.it']))
            ->get('/admin')
            ->assertOk();
    }
}
