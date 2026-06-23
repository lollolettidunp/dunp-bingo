<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_precreate_disable_and_validate_users(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);
        $admin = User::factory()->create(['email' => 'admin@azienda.it']);

        Livewire::actingAs($admin)->test(Users::class)
            ->set('email', 'USER@azienda.it')
            ->set('name', 'User')
            ->set('startingScore', 2)
            ->set('isEnabled', false)
            ->call('save');

        $this->assertDatabaseHas('users', ['email' => 'user@azienda.it', 'is_enabled' => false, 'starting_score' => 2]);
        Livewire::actingAs(User::factory()->create())->test(Users::class)->assertStatus(403);
    }
}
