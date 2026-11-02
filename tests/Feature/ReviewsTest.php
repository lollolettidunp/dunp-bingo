<?php

namespace Tests\Feature;

use App\Livewire\Admin\Reviews;
use App\Models\Board;
use App\Models\BoardCell;
use App\Models\BoardSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);

        return User::factory()->create(['email' => 'admin@azienda.it']);
    }

    /** A board with its top row (line 0) fully marked. */
    private function boardWithTopRow(): Board
    {
        $board = Board::factory()->create();
        foreach (range(0, 4) as $position) {
            BoardCell::factory()->for($board)->create(['position' => $position, 'marked_at' => now()]);
        }

        return $board;
    }

    public function test_admin_can_approve_or_reject_a_pending_submission(): void
    {
        $admin = $this->admin();
        $submission = BoardSubmission::factory()->for($this->boardWithTopRow(), 'board')->create();

        Livewire::actingAs($admin)->test(Reviews::class)->call('approve', $submission->id);
        $this->assertSame(BoardSubmission::APPROVED, $submission->fresh()->status);

        $rejected = BoardSubmission::factory()->for($this->boardWithTopRow(), 'board')->create();
        Livewire::actingAs($admin)->test(Reviews::class)->set('note', 'no')->call('reject', $rejected->id);
        $this->assertSame(BoardSubmission::REJECTED, $rejected->fresh()->status);
        $this->assertSame('no', $rejected->fresh()->review_note);
    }

    public function test_the_first_bingo_is_doubled_before_eleven_and_a_half_before_three(): void
    {
        $admin = $this->admin();
        Carbon::setTestNow('2026-09-17 10:30:00');

        $early = BoardSubmission::factory()->for($this->boardWithTopRow(), 'board')->create(['submitted_at' => now()]);
        Livewire::actingAs($admin)->test(Reviews::class)->call('approve', $early->id);
        $this->assertSame(2.0, $early->fresh()->points);
        $this->assertSame(2.0, $early->fresh()->multiplier);

        $midday = BoardSubmission::factory()->for($this->boardWithTopRow(), 'board')
            ->create(['submitted_at' => Carbon::parse('2026-09-17 14:00:00')]);
        Livewire::actingAs($admin)->test(Reviews::class)->call('approve', $midday->id);
        $this->assertSame(1.5, $midday->fresh()->points);

        $late = BoardSubmission::factory()->for($this->boardWithTopRow(), 'board')
            ->create(['submitted_at' => Carbon::parse('2026-09-17 16:20:00')]);
        Livewire::actingAs($admin)->test(Reviews::class)->call('approve', $late->id);
        $this->assertSame(1.0, $late->fresh()->points);
    }

    public function test_extra_bingos_are_worth_half_a_point_whatever_the_hour(): void
    {
        $admin = $this->admin();
        Carbon::setTestNow('2026-09-17 16:00:00');

        $board = $this->boardWithTopRow();
        // Prima colonna (linea 5): con la casella 0 gia' segnata restano queste quattro.
        foreach ([5, 10, 15, 20] as $position) {
            BoardCell::factory()->for($board)->create(['position' => $position, 'marked_at' => now()]);
        }

        $first = BoardSubmission::factory()->for($board, 'board')->create(['lines' => [0], 'submitted_at' => now()]);
        $extra = BoardSubmission::factory()->for($board, 'board')
            ->create(['lines' => [5], 'submitted_at' => Carbon::parse('2026-09-17 09:00:00')]);

        $test = Livewire::actingAs($admin)->test(Reviews::class);
        $test->call('approve', $first->id);
        $test->call('approve', $extra->id);

        $this->assertSame(1.0, $first->fresh()->points, 'primo bingo fuori fascia');
        $this->assertSame(0.5, $extra->fresh()->points, 'il moltiplicatore non tocca gli extra');
        $this->assertSame(1.5, $board->fresh()->load('submissions')->points());
    }

    public function test_a_submission_whose_line_was_unmarked_can_still_be_approved(): void
    {
        $admin = $this->admin();
        $board = $this->boardWithTopRow();
        $submission = BoardSubmission::factory()->for($board, 'board')->create();
        $board->cells()->where('position', 2)->update(['marked_at' => null]);

        Livewire::actingAs($admin)->test(Reviews::class)
            ->assertSee('Le caselle sono cambiate dopo l\'invio', false)
            ->call('approve', $submission->id)
            ->assertOk();

        $this->assertSame(BoardSubmission::APPROVED, $submission->fresh()->status);
    }

    public function test_admin_can_preview_the_claimed_board_in_a_popup(): void
    {
        $admin = $this->admin();
        $board = Board::factory()->for(User::factory()->create(['name' => 'Player']))->create();
        BoardCell::factory()->for($board)->create(['position' => 0, 'text' => 'Preview cell', 'marked_at' => now()]);
        $submission = BoardSubmission::factory()->for($board, 'board')->create();

        Livewire::actingAs($admin)->test(Reviews::class)
            ->call('preview', $submission->id)
            ->assertSet('previewSubmissionId', $submission->id)
            ->assertSee('Scheda di Player')
            ->assertSee('Preview cell')
            ->call('closePreview')
            ->assertSet('previewSubmissionId', null);
    }
}
