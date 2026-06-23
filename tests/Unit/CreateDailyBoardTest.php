<?php

namespace Tests\Unit;

use App\Actions\CreateDailyBoard;
use App\Models\Board;
use App\Models\Cell;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateDailyBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_one_board_with_bonus_and_24_unique_cells(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(30)->create();

        $board = app(CreateDailyBoard::class)($user, CarbonImmutable::parse('2026-06-23'));

        $this->assertCount(25, $board->cells);
        $this->assertSame('BONUS', $board->cells[12]->text);
        $this->assertNotNull($board->cells[12]->marked_at);
        $this->assertCount(24, $board->cells->whereNotNull('cell_id')->pluck('cell_id')->unique());
    }

    public function test_it_excludes_cells_about_the_user_and_excluded_weekdays(): void
    {
        $user = User::factory()->create();
        $blocked = Cell::factory()->create(['text' => 'blocked']);
        $weekday = Cell::factory()->create(['text' => 'weekday', 'excluded_weekdays' => ['martedi']]);
        Cell::factory()->count(24)->create();
        $user->excludedCells()->attach($blocked);

        $board = app(CreateDailyBoard::class)($user, CarbonImmutable::parse('2026-06-23'));

        $this->assertFalse($board->cells->pluck('text')->contains($blocked->text));
        $this->assertFalse($board->cells->pluck('text')->contains($weekday->text));
    }

    public function test_it_always_includes_eligible_special_cells_and_keeps_snapshot(): void
    {
        $user = User::factory()->create();
        $special = Cell::factory()->create(['text' => 'special', 'special_date' => '2026-06-23']);
        Cell::factory()->count(24)->create();

        $board = app(CreateDailyBoard::class)($user, CarbonImmutable::parse('2026-06-23'));
        $special->update(['text' => 'changed']);

        $this->assertTrue($board->cells->pluck('text')->contains('special'));
        $this->assertFalse($board->fresh()->cells->pluck('text')->contains('changed'));
    }

    public function test_it_returns_the_existing_board_without_regenerating_it(): void
    {
        $user = User::factory()->create();
        Cell::factory()->count(24)->create();

        $first = app(CreateDailyBoard::class)($user, CarbonImmutable::parse('2026-06-23'));
        $second = app(CreateDailyBoard::class)($user, CarbonImmutable::parse('2026-06-23'));

        $this->assertTrue($first->is($second));
        $this->assertSame(25, $second->cells()->count());
    }

    public function test_it_fails_when_fewer_than_24_cells_are_eligible(): void
    {
        $this->expectException(DomainException::class);
        Cell::factory()->count(23)->create();

        app(CreateDailyBoard::class)(User::factory()->create(), CarbonImmutable::parse('2026-06-23'));
    }

    public function test_it_keeps_the_lowest_row_and_column_spread_found(): void
    {
        $user = User::factory()->create();
        foreach ([1, 2, 3] as $difficulty) {
            Cell::factory()->count(8)->create(['difficulty' => $difficulty]);
        }

        $board = app(CreateDailyBoard::class)($user, CarbonImmutable::parse('2026-06-23'));

        $this->assertLessThanOrEqual(4, $this->spread($board));
    }

    private function spread(Board $board): int
    {
        $weights = $board->cells->pluck('difficulty')->all();
        $lines = [[0,1,2,3,4], [5,6,7,8,9], [10,11,12,13,14], [15,16,17,18,19], [20,21,22,23,24], [0,5,10,15,20], [1,6,11,16,21], [2,7,12,17,22], [3,8,13,18,23], [4,9,14,19,24]];
        $totals = array_map(fn ($line) => array_sum(array_map(fn ($i) => $weights[$i], $line)), $lines);

        return max($totals) - min($totals);
    }
}
