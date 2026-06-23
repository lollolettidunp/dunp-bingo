<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_navigation_exposes_board_colleagues_leaderboard_and_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('board'))
            ->assertOk()
            ->assertSee('Dunp-ingo')
            ->assertSee('Colleghi')
            ->assertSee('Classifica')
            ->assertSee('Esci');
    }
}
