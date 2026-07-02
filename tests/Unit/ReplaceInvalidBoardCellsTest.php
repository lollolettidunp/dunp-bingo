<?php

namespace Tests\Unit;

use App\Actions\ReplaceInvalidBoardCells;
use App\Models\Board;
use App\Models\BoardCell;
use App\Models\Cell;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReplaceInvalidBoardCellsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_replaces_invalid_today_cells_and_clears_the_mark(): void
    {
        $user = User::factory()->create();
        $blocked = Cell::factory()->create(['text' => 'Assente straordinario']);
        $replacement = Cell::factory()->create(['text' => 'Nuova valida', 'difficulty' => 3]);
        Cell::factory()->count(24)->create();
        $board = Board::factory()->for($user)->create(['played_on' => '2026-06-23', 'status' => Board::PLAYING]);
        BoardCell::factory()->for($board)->create([
            'cell_id' => $blocked->id,
            'position' => 4,
            'text' => $blocked->text,
            'difficulty' => $blocked->difficulty,
            'marked_at' => now(),
        ]);
        BoardCell::factory()->for($board)->create([
            'cell_id' => Cell::factory()->create()->id,
            'position' => 5,
        ]);
        $blocked->update(['is_active' => false]);

        app(ReplaceInvalidBoardCells::class)($blocked, CarbonImmutable::parse('2026-06-23'));

        $changed = $board->fresh()->cells()->where('position', 4)->first();
        $this->assertSame($replacement->id, $changed->cell_id);
        $this->assertSame('Nuova valida', $changed->text);
        $this->assertSame(3, $changed->difficulty);
        $this->assertNull($changed->marked_at);
        $this->assertStringContainsString('Assente straordinario', $board->fresh()->notice);
        $this->assertStringContainsString('Nuova valida', $board->fresh()->notice);
    }
}
