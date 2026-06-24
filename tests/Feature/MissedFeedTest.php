<?php

namespace Tests\Feature;

use App\Livewire\MissedFeed;
use App\Models\Board;
use App\Models\BoardCell;
use App\Models\Cell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MissedFeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_only_the_first_colleague_to_check_each_cell(): void
    {
        $viewer = User::factory()->create();
        $alessandro = User::factory()->create(['name' => 'Alessandro']);
        $bruno = User::factory()->create(['name' => 'Bruno']);
        $cell = Cell::factory()->create(['text' => 'Caffè versato']);

        $first = Board::factory()->for($alessandro)->create(['played_on' => today()]);
        BoardCell::factory()->for($first)->create([
            'cell_id' => $cell->id, 'position' => 0, 'text' => 'Caffè versato', 'marked_at' => now()->subMinutes(10),
        ]);

        // Bruno segna la stessa casella più tardi: non deve comparire.
        $second = Board::factory()->for($bruno)->create(['played_on' => today()]);
        BoardCell::factory()->for($second)->create([
            'cell_id' => $cell->id, 'position' => 5, 'text' => 'Caffè versato', 'marked_at' => now(),
        ]);

        Livewire::actingAs($viewer)->test(MissedFeed::class)
            ->assertSee('Cosa potresti esserti perso')
            ->assertSee('Alessandro')
            ->assertSee('Caffè versato')
            ->assertDontSee('Bruno');
    }

    public function test_it_hides_cells_the_viewer_already_marked_and_their_own_checks(): void
    {
        $viewer = User::factory()->create(['name' => 'Io']);
        $alessandro = User::factory()->create(['name' => 'Alessandro']);
        $shared = Cell::factory()->create(['text' => 'Riunione infinita']);
        $mineOnly = Cell::factory()->create(['text' => 'Stampante rotta']);

        // Il viewer ha già segnato la casella condivisa: non l'ha "persa".
        $myBoard = Board::factory()->for($viewer)->create(['played_on' => today()]);
        BoardCell::factory()->for($myBoard)->create([
            'cell_id' => $shared->id, 'position' => 0, 'text' => 'Riunione infinita', 'marked_at' => now(),
        ]);
        BoardCell::factory()->for($myBoard)->create([
            'cell_id' => $mineOnly->id, 'position' => 1, 'text' => 'Stampante rotta', 'marked_at' => now(),
        ]);

        $hisBoard = Board::factory()->for($alessandro)->create(['played_on' => today()]);
        BoardCell::factory()->for($hisBoard)->create([
            'cell_id' => $shared->id, 'position' => 2, 'text' => 'Riunione infinita', 'marked_at' => now(),
        ]);

        Livewire::actingAs($viewer)->test(MissedFeed::class)
            ->assertSee('Per ora non ti sei perso niente.')
            ->assertDontSee('Riunione infinita')
            ->assertDontSee('Stampante rotta');
    }
}
