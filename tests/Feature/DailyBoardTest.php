<?php

namespace Tests\Feature;

use App\Livewire\DailyBoard;
use App\Models\Board;
use App\Models\BoardCell;
use App\Models\BoardSubmission;
use App\Models\Cell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class DailyBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_board_stays_sealed_until_the_player_opens_the_pack(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(30)->create();

        $test = Livewire::actingAs($user)->test(DailyBoard::class)
            ->assertSee('Apri la bustina')
            ->assertDontSee('wire:click="toggle(0)', false);
        $this->assertSame(0, Board::count(), 'visiting the page must not deal the board');

        $test->call('openPack')
            ->assertDispatched('board-unpacked')
            ->assertDontSee('Apri la bustina')
            ->assertSee('wire:click="toggle(0)', false);
        $this->assertSame(1, Board::count());
    }

    public function test_changes_made_before_opening_the_pack_shape_the_board(): void
    {
        $user = User::factory()->create();
        $cells = Cell::factory()->count(24)->create();
        $backToday = Cell::factory()->create(['is_active' => false]);

        $test = Livewire::actingAs($user)->test(DailyBoard::class);

        // The admin fixes the day while the pack is still sealed: one cell goes out, one comes back.
        $cells->first()->update(['is_active' => false]);
        $backToday->update(['is_active' => true]);
        $test->call('openPack');

        $dealt = Board::first()->cells()->pluck('cell_id');
        $this->assertContains($backToday->id, $dealt);
        $this->assertNotContains($cells->first()->id, $dealt);
    }

    public function test_an_already_opened_board_skips_the_pack(): void
    {
        $user = User::factory()->create();
        Board::factory()->for($user)->create();

        Livewire::actingAs($user)->test(DailyBoard::class)
            ->assertDontSee('Apri la bustina')
            ->assertSee('In gioco');
    }

    public function test_a_pack_opened_on_another_device_shows_up_on_the_next_poll(): void
    {
        $user = User::factory()->create();
        $test = Livewire::actingAs($user)->test(DailyBoard::class)->assertSee('Apri la bustina');

        Board::factory()->for($user)->create();

        $test->refresh()
            ->assertDontSee('Apri la bustina')
            ->assertNotDispatched('board-unpacked');
    }

    public function test_a_failed_opening_can_be_retried(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(23)->create();

        $test = Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack')
            ->assertDispatched('board-pack-failed')
            ->assertSee('Servono almeno 24 celle')
            ->assertSee('Riprova');

        Cell::factory()->create();
        $test->call('openPack')
            ->assertDontSee('Servono almeno 24 celle')
            ->assertSee('In gioco');
    }

    public function test_only_the_admin_can_be_reminded_to_fix_the_day_before_opening(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);

        // The flag drives the admin note on the sealed pack; nobody else may ever get it.
        Livewire::actingAs(User::factory()->create(['email' => 'admin@azienda.it']))->test(DailyBoard::class)
            ->assertViewHas('isAdmin', true);

        Livewire::actingAs(User::factory()->create())->test(DailyBoard::class)
            ->assertSee('Apri la bustina')
            ->assertViewHas('isAdmin', false)
            ->assertDontSee('Vai al pannello admin');
    }

    public function test_owner_can_toggle_and_submit_a_valid_bingo(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(30)->create();

        $test = Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack');
        foreach ([0, 1, 2, 3, 4] as $position) {
            $test->call('toggle', $position);
        }
        $test->call('submit');

        $submission = Board::first()->submissions()->first();
        $this->assertSame(BoardSubmission::PENDING, $submission->status);
        $this->assertSame([0], $submission->lines, 'the top row is line 0');
        $this->assertNotNull($submission->submitted_at);
    }

    public function test_owner_can_send_in_an_extra_bingo_after_the_first_one(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(30)->create();

        $test = Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack');
        foreach ([0, 1, 2, 3, 4] as $position) {
            $test->call('toggle', $position);
        }
        $test->call('submit');

        // Second row: a brand new line, sent in on its own.
        foreach ([5, 6, 7, 8, 9] as $position) {
            $test->call('toggle', $position);
        }
        $test->call('submit')->assertSee('2 bingo inviati');

        $submissions = Board::first()->submissions;
        $this->assertCount(2, $submissions);
        $this->assertSame([1], $submissions->last()->lines);
    }

    public function test_a_line_already_sent_in_cannot_be_claimed_twice(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(30)->create();

        $test = Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack');
        foreach ([0, 1, 2, 3, 4] as $position) {
            $test->call('toggle', $position);
        }
        $test->call('submit');
        $test->call('submit')->assertStatus(422);

        $this->assertCount(1, Board::first()->submissions);
    }

    public function test_owner_can_keep_toggling_while_a_bingo_is_pending(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(30)->create();

        $test = Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack');
        foreach ([0, 1, 2, 3, 4] as $position) {
            $test->call('toggle', $position);
        }
        $test->call('submit');

        $this->assertSame(BoardSubmission::PENDING, Board::first()->state());

        $test->call('toggle', 5)->assertStatus(200);
        $this->assertNotNull(Board::first()->cells()->where('position', 5)->first()->marked_at);
    }

    public function test_owner_can_keep_toggling_after_bingo_is_approved(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(30)->create();

        $test = Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack');
        foreach ([0, 1, 2, 3, 4] as $position) {
            $test->call('toggle', $position);
        }
        $test->call('submit');

        $board = Board::first();
        $board->submissions()->first()->update(['status' => BoardSubmission::APPROVED, 'reviewed_at' => now(), 'points' => 2]);

        $test->call('toggle', 5)->assertStatus(200);
        $this->assertNotNull($board->cells()->where('position', 5)->first()->marked_at);
    }

    public function test_board_statuses_have_user_facing_labels(): void
    {
        $playing = Board::factory()->create();
        $this->assertSame('In gioco', $playing->statusLabel());

        $pending = Board::factory()->create();
        BoardSubmission::factory()->for($pending, 'board')->create();
        $this->assertSame('In Revisione', $pending->load('submissions')->statusLabel());

        $approved = Board::factory()->create();
        BoardSubmission::factory()->for($approved, 'board')->approved()->create();
        $this->assertSame('Completata!', $approved->load('submissions')->statusLabel());
    }
    public function test_board_shows_status_labels_and_uses_single_click_toggle(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(30)->create();

        Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack')
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

        Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack')->call('toggle', 12)->assertStatus(422);

        Cell::first()->update(['is_active' => false]);
        $other = User::factory()->create();
        Livewire::actingAs($other)->test(DailyBoard::class)->call('openPack')->assertSee('Servono almeno 24 celle');
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

        $test = Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack')
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
    public function test_user_moves_a_placed_sticker_and_gets_the_covered_cell_back(): void
    {
        Carbon::setTestNow('2026-06-23 10:00:00');
        $user = User::factory()->create();
        Cell::factory()->count(24)->create();
        $sticker = Cell::factory()->create(['text' => 'Sticker one-time', 'special_date' => '2026-06-23']);

        $test = Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack');
        $board = Board::first();
        $misclicked = $board->cells()->where('position', 0)->first();
        $misclicked->update(['marked_at' => now()]);
        $original = $misclicked->only(['cell_id', 'text', 'difficulty']);
        $wanted = $board->cells()->where('position', 1)->first();
        $wantedOriginal = $wanted->only(['cell_id', 'text', 'difficulty']);

        $test->call('applySpecialCell', $sticker->id, 0)
            ->assertSee('Sulla scheda')
            ->assertSee('data-special-cell-id="'.$sticker->id.'"', false);
        $test->call('toggle', 0);
        $test->call('applySpecialCell', $sticker->id, 1)
            ->assertDispatched('special-cell-applied', position: 1)
            ->assertViewHas('specialCells', fn ($cells) => $cells->doesntContain('id', $sticker->id));

        $misclicked->refresh();
        $this->assertSame($original, $misclicked->only(['cell_id', 'text', 'difficulty']));
        $this->assertNotNull($misclicked->marked_at, 'the covered cell gets its own mark back');
        $this->assertFalse($misclicked->isCovering());

        $wanted->refresh();
        $this->assertSame($sticker->id, $wanted->cell_id);
        $this->assertNotNull($wanted->marked_at, 'the sticker carries its mark along');
        $this->assertSame($wantedOriginal, [
            'cell_id' => $wanted->covered_cell_id,
            'text' => $wanted->covered_text,
            'difficulty' => $wanted->covered_difficulty,
        ]);
    }

    public function test_moving_a_sticker_onto_another_sticker_swaps_them(): void
    {
        Carbon::setTestNow('2026-06-23 10:00:00');
        $user = User::factory()->create();
        Cell::factory()->count(24)->create();
        $first = Cell::factory()->create(['text' => 'Primo adesivo', 'special_date' => '2026-06-23']);
        $second = Cell::factory()->create(['text' => 'Secondo adesivo', 'special_date' => '2026-06-23']);

        $test = Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack');
        $board = Board::first();
        $covered = $board->cells()->whereIn('position', [0, 1])->pluck('text', 'position');

        $test->call('applySpecialCell', $first->id, 0)
            ->call('applySpecialCell', $second->id, 1)
            ->call('applySpecialCell', $first->id, 1);

        $cells = $board->cells()->whereIn('position', [0, 1])->get()->keyBy('position');
        $this->assertSame($second->id, $cells[0]->cell_id);
        $this->assertSame($first->id, $cells[1]->cell_id);
        $this->assertSame($covered[0], $cells[0]->covered_text);
        $this->assertSame($covered[1], $cells[1]->covered_text);
    }

    public function test_replacing_a_sticker_with_another_keeps_the_original_covered_cell(): void
    {
        Carbon::setTestNow('2026-06-23 10:00:00');
        $user = User::factory()->create();
        Cell::factory()->count(24)->create();
        $first = Cell::factory()->create(['text' => 'Primo adesivo', 'special_date' => '2026-06-23']);
        $second = Cell::factory()->create(['text' => 'Secondo adesivo', 'special_date' => '2026-06-23']);

        $test = Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack');
        $original = Board::first()->cells()->where('position', 0)->value('text');

        $test->call('applySpecialCell', $first->id, 0)
            ->call('applySpecialCell', $second->id, 0)
            ->assertViewHas('specialCells', fn ($cells) => $cells->contains('id', $first->id));

        $cell = Board::first()->cells()->where('position', 0)->first();
        $this->assertSame($second->id, $cell->cell_id);
        $this->assertSame($original, $cell->covered_text);
    }

    public function test_ordinary_cells_cannot_be_moved_like_stickers(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(30)->create();

        $test = Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack');
        $ordinary = Board::first()->cells()->where('position', 0)->value('cell_id');

        $test->call('applySpecialCell', $ordinary, 1)->assertStatus(409);
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

        Livewire::actingAs($user)->test(DailyBoard::class)->call('openPack')
            ->assertSee('Come funzionano gli adesivi')
            ->assertSee('special-help__button', false)
            ->assertSee('specialHelpOpen', false)
            ->assertDontSee('<details class="special-help"', false)
            ->assertDontSee('special-trigger__badge', false);
    }
}
