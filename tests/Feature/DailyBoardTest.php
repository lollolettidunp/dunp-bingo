<?php

namespace Tests\Feature;

use App\Livewire\DailyBoard;
use App\Models\Board;
use App\Models\Cell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
    public function test_user_applies_one_daily_sticker_to_replace_a_board_cell(): void
    {
        Carbon::setTestNow('2026-06-23 10:00:00');
        $user = User::factory()->create();
        Cell::factory()->count(24)->create();
        $sticker = Cell::factory()->create([
            'text' => 'Sticker one-time',
            'difficulty' => 1,
            'special_date' => '2026-06-23',
        ]);

        $test = Livewire::actingAs($user)->test(DailyBoard::class)
            ->assertSee('Sticker one-time');

        $board = Board::first();
        $target = $board->cells()->where('position', 0)->first();
        $target->update(['marked_at' => now()]);

        $test->call('applySpecialCell', $sticker->id, 0)
            ->assertViewHas('specialCells', fn ($cells) => $cells->doesntContain('id', $sticker->id))
            ->assertSee('special-applied', false);

        $target->refresh();
        $this->assertSame($sticker->id, $target->cell_id);
        $this->assertSame('Sticker one-time', $target->text);
        $this->assertSame(1, $target->difficulty);
        $this->assertNull($target->marked_at);

        $test->call('toggle', 0)
            ->assertSee('special-completed', false);
    }
    public function test_daily_sticker_help_replaces_count_badge(): void
    {
        Carbon::setTestNow('2026-06-23 10:00:00');
        $user = User::factory()->create();
        Cell::factory()->count(24)->create();
        Cell::factory()->create(['text' => 'Sticker one-time', 'special_date' => '2026-06-23']);

        Livewire::actingAs($user)->test(DailyBoard::class)
            ->assertSee('Come funzionano gli adesivi')
            ->assertSee('special-help__button', false)
            ->assertSee('specialHelpOpen', false)
            ->assertDontSee('<details class="special-help"', false)
            ->assertDontSee('special-trigger__badge', false);
    }
}
