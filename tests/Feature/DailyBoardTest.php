<?php

namespace Tests\Feature;

use App\Livewire\DailyBoard;
use App\Models\Board;
use App\Models\BoardCell;
use App\Models\Cell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DailyBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_toggle_and_submit_a_valid_bingo(): void
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
    }

    public function test_owner_can_keep_toggling_while_board_is_pending(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(30)->create();

        $test = Livewire::actingAs($user)->test(DailyBoard::class);
        foreach ([0, 1, 2, 3, 4] as $position) {
            $test->call('toggle', $position);
        }
        $test->call('submit');

        $this->assertSame(Board::PENDING, Board::first()->status);

        $test->call('toggle', 5)->assertStatus(200);
        $this->assertNotNull(Board::first()->cells()->where('position', 5)->first()->marked_at);
    }

    public function test_owner_can_keep_toggling_after_bingo_is_approved(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(30)->create();

        $test = Livewire::actingAs($user)->test(DailyBoard::class);
        foreach ([0, 1, 2, 3, 4] as $position) {
            $test->call('toggle', $position);
        }
        $test->call('submit');

        $board = Board::first();
        $board->update(['status' => Board::APPROVED, 'reviewed_at' => now()]);

        $test->call('toggle', 5)->assertStatus(200);
        $this->assertNotNull($board->cells()->where('position', 5)->first()->marked_at);
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
    public function test_it_flags_cells_a_colleague_already_checked(): void
    {
        $viewer = User::factory()->create();
        $colleague = User::factory()->create();
        $shared = Cell::factory()->create(['text' => 'Caffè versato']);
        $mine = Cell::factory()->create(['text' => 'Stampante rotta']);

        // Pre-costruisco la board del viewer: mount riusa quella di oggi.
        $board = Board::factory()->for($viewer)->create(['played_on' => today()]);
        BoardCell::factory()->for($board)->create(['cell_id' => $shared->id, 'position' => 0, 'text' => 'Caffè versato']);
        BoardCell::factory()->for($board)->create(['cell_id' => $mine->id, 'position' => 1, 'text' => 'Stampante rotta']);

        // Un collega ha checkato la casella condivisa: deve comparire evidenziata.
        $his = Board::factory()->for($colleague)->create(['played_on' => today()]);
        BoardCell::factory()->for($his)->create(['cell_id' => $shared->id, 'position' => 3, 'text' => 'Caffè versato', 'marked_at' => now()]);

        Livewire::actingAs($viewer)->test(DailyBoard::class)
            ->assertSee('missed-hint', false)
            ->assertViewHas('missedCellIds', fn ($ids) => in_array($shared->id, $ids, true) && ! in_array($mine->id, $ids, true));
    }

    public function test_it_stops_flagging_a_cell_once_the_viewer_marks_it(): void
    {
        $viewer = User::factory()->create();
        $colleague = User::factory()->create();
        $shared = Cell::factory()->create(['text' => 'Caffè versato']);

        $board = Board::factory()->for($viewer)->create(['played_on' => today()]);
        BoardCell::factory()->for($board)->create(['cell_id' => $shared->id, 'position' => 0, 'text' => 'Caffè versato']);

        $his = Board::factory()->for($colleague)->create(['played_on' => today()]);
        BoardCell::factory()->for($his)->create(['cell_id' => $shared->id, 'position' => 3, 'text' => 'Caffè versato', 'marked_at' => now()]);

        Livewire::actingAs($viewer)->test(DailyBoard::class)
            ->assertSee('missed-hint', false)
            ->call('toggle', 0)
            ->assertDontSee('missed-hint', false);
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
