<?php

namespace Tests\Feature;

use App\Livewire\Leaderboard;
use App\Models\Board;
use App\Models\BoardSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class LeaderboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_leaderboard_adds_starting_score_and_approved_points_only(): void
    {
        $user = User::factory()->create(['name' => 'Winner', 'starting_score' => 3]);
        $won = Board::factory()->for($user)->create();
        BoardSubmission::factory()->for($won, 'board')->approved(2.0)->create();
        $waiting = Board::factory()->for($user)->create(['played_on' => today()->addDay()]);
        BoardSubmission::factory()->for($waiting, 'board')->create();

        Livewire::actingAs($user)->test(Leaderboard::class)
            ->assertSee('Winner')
            ->assertSee('Istruzioni')
            ->assertSee('5');
    }

    public function test_half_points_are_shown_with_an_italian_decimal(): void
    {
        $user = User::factory()->create(['name' => 'Mezzo', 'starting_score' => 0]);
        $board = Board::factory()->for($user)->create();
        BoardSubmission::factory()->for($board, 'board')->approved(2.0)->create();
        BoardSubmission::factory()->for($board, 'board')->approved(0.5)->create(['lines' => [1]]);

        Livewire::actingAs($user)->test(Leaderboard::class)->assertSee('2,5');
    }

    public function test_week_and_month_filters_only_count_boards_played_in_the_period(): void
    {
        Carbon::setTestNow('2026-09-17 09:00:00'); // giovedi
        $user = User::factory()->create(['name' => 'Periodico', 'starting_score' => 10]);

        $thisWeek = Board::factory()->for($user)->create(['played_on' => '2026-09-16']);
        BoardSubmission::factory()->for($thisWeek, 'board')->approved(2.0)->create();

        $earlierThisMonth = Board::factory()->for($user)->create(['played_on' => '2026-09-03']);
        BoardSubmission::factory()->for($earlierThisMonth, 'board')->approved(1.0)->create();

        $lastMonth = Board::factory()->for($user)->create(['played_on' => '2026-08-20']);
        BoardSubmission::factory()->for($lastMonth, 'board')->approved(1.5)->create();

        $test = Livewire::actingAs($user)->test(Leaderboard::class);

        // Sempre: 10 di carry-over piu' 4,5 di bingo.
        $test->assertSee('14,5');

        $test->call('setRange', 'month')
            ->assertSee('Settembre 2026')
            ->assertSee('>3<', false)
            ->assertDontSee('14,5');

        $test->call('setRange', 'week')
            ->assertSee('Dal 14/09 al 20/09')
            ->assertSee('>2<', false);

        $test->call('setRange', 'nope')->assertStatus(422);
    }

    public function test_the_carry_over_score_stays_out_of_the_period_views(): void
    {
        $user = User::factory()->create(['name' => 'Veterano', 'starting_score' => 40]);

        Livewire::actingAs($user)->test(Leaderboard::class)
            ->assertSee('40')
            ->call('setRange', 'week')
            ->assertDontSee('40');
    }
}
