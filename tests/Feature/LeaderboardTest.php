<?php

namespace Tests\Feature;

use App\Livewire\Leaderboard;
use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboard_adds_starting_score_and_approved_boards_only(): void
    {
        $user = User::factory()->create(['name' => 'Winner', 'starting_score' => 3]);
        Board::factory()->for($user)->create(['status' => Board::APPROVED, 'reviewed_at' => now()]);
        Board::factory()->for($user)->create(['status' => Board::PENDING, 'played_on' => today()->addDay()]);

        Livewire::actingAs($user)->test(Leaderboard::class)
            ->assertSee('Winner')
            ->assertSee('Istruzioni')
            ->assertSee('Ogni bingo approvato vale 1 punto.')
            ->assertSee('4');
    }
}
