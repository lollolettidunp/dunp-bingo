<?php

namespace Tests\Feature;

use App\Livewire\ColleagueBoards;
use App\Models\Board;
use App\Models\BoardCell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ColleagueBoardsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_today_and_is_read_only(): void
    {
        $viewer = User::factory()->create();
        $today = Board::factory()->for(User::factory()->create(['name' => 'Today']))->create(['played_on' => today()]);
        BoardCell::factory()->for($today)->create(['position' => 0, 'text' => 'Marked', 'marked_at' => now()]);
        Board::factory()->for(User::factory()->create(['name' => 'Old']))->create(['played_on' => today()->subDay()]);

        Livewire::actingAs($viewer)->test(ColleagueBoards::class)
            ->assertSee('Today')
            ->assertSee('Istruzioni')
            ->assertSee('Apri un collega per spiare la sua scheda.')
            ->assertSee('Marked')
            ->assertDontSee('Old')
            ->assertDontSee('wire:click="toggle');
    }
}
