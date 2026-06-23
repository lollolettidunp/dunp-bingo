<?php

namespace Tests\Feature;

use App\Livewire\Admin\Reviews;
use App\Models\Board;
use App\Models\BoardCell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_or_reject_pending_valid_bingos(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);
        $admin = User::factory()->create(['email' => 'admin@azienda.it']);
        $board = Board::factory()->create(['status' => Board::PENDING]);
        foreach (range(0, 4) as $position) {
            BoardCell::factory()->for($board)->create(['position' => $position, 'marked_at' => now()]);
        }

        Livewire::actingAs($admin)->test(Reviews::class)->call('approve', $board->id);
        $this->assertSame(Board::APPROVED, $board->fresh()->status);

        $rejected = Board::factory()->create(['status' => Board::PENDING]);
        Livewire::actingAs($admin)->test(Reviews::class)->set('note', 'no')->call('reject', $rejected->id);
        $this->assertSame(Board::REJECTED, $rejected->fresh()->status);
    }
    public function test_admin_can_preview_pending_board_in_a_popup(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);
        $admin = User::factory()->create(['email' => 'admin@azienda.it']);
        $board = Board::factory()->for(User::factory()->create(['name' => 'Player']))->create(['status' => Board::PENDING]);
        BoardCell::factory()->for($board)->create(['position' => 0, 'text' => 'Preview cell', 'marked_at' => now()]);

        Livewire::actingAs($admin)->test(Reviews::class)
            ->call('preview', $board->id)
            ->assertSet('previewBoardId', $board->id)
            ->assertSee('Scheda di Player')
            ->assertSee('Preview cell')
            ->call('closePreview')
            ->assertSet('previewBoardId', null);
    }
}
