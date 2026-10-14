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
        $board = Board::factory()->for($user)->create(['played_on' => '2026-06-23']);
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

    public function test_it_replaces_an_invalid_cell_hidden_under_a_sticker(): void
    {
        $user = User::factory()->create();
        $blocked = Cell::factory()->create(['text' => 'Assente straordinario']);
        $replacement = Cell::factory()->create(['text' => 'Nuova valida', 'difficulty' => 3]);
        $sticker = Cell::factory()->create(['text' => 'Adesivo', 'special_date' => '2026-06-23']);
        $board = Board::factory()->for($user)->create(['played_on' => '2026-06-23']);
        $cell = BoardCell::factory()->for($board)->create([
            'cell_id' => $sticker->id,
            'position' => 4,
            'text' => $sticker->text,
            'covered_cell_id' => $blocked->id,
            'covered_text' => $blocked->text,
            'covered_difficulty' => $blocked->difficulty,
            'covered_marked_at' => now(),
        ]);
        $blocked->update(['is_active' => false]);

        app(ReplaceInvalidBoardCells::class)($blocked, CarbonImmutable::parse('2026-06-23'));

        $cell->refresh();
        $this->assertSame($sticker->id, $cell->cell_id, 'the visible sticker stays');
        $this->assertSame($replacement->id, $cell->covered_cell_id);
        $this->assertSame('Nuova valida', $cell->covered_text);
        $this->assertSame(3, $cell->covered_difficulty);
        $this->assertNull($cell->covered_marked_at);
    }
}
