<?php

namespace Tests\Feature;

use App\Livewire\DailyBoard;
use App\Models\Board;
use App\Models\Cell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailyBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_toggle_submit_and_lock_a_valid_bingo(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(30)->create();

        $test = Livewire::actingAs($user)->test(DailyBoard::class);
        foreach ([0, 1, 2, 3, 4] as $position) {
            $test->call('toggle', $position);
        }
        $test->call('submit');

        $board = Board::first();
        $this->assertSame(Board::PENDING, $board->status);
        $this->assertNotNull($board->submitted_at);
        $test->call('toggle', 5)->assertStatus(409);
    }

    public function test_board_statuses_have_user_facing_labels(): void
    {
        $this->assertSame('In gioco', (new Board(['status' => Board::PLAYING]))->statusLabel());
        $this->assertSame('In Revisione', (new Board(['status' => Board::PENDING]))->statusLabel());
        $this->assertSame('Completata!', (new Board(['status' => Board::APPROVED]))->statusLabel());
    }
    public function test_board_shows_status_labels_and_uses_single_click_toggle(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(30)->create();

        Livewire::actingAs($user)->test(DailyBoard::class)
            ->assertSee('In gioco')
            ->assertSee('Istruzioni')
            ->assertSee('Tocca una casella quando succede davvero.')
            ->assertSee('wire:click="toggle(0)', false)
            ->assertDontSee('wire:dblclick', false);
    }
    public function test_bonus_cannot_be_toggled_and_generation_error_is_shown(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(24)->create();

        Livewire::actingAs($user)->test(DailyBoard::class)->call('toggle', 12)->assertStatus(422);

        Cell::first()->update(['is_active' => false]);
        $other = User::factory()->create();
        Livewire::actingAs($other)->test(DailyBoard::class)->assertSee('Servono almeno 24 celle');
    }
}
