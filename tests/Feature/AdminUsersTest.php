<?php

namespace Tests\Feature;

use App\Actions\RegenerateDailyBoard;
use App\Livewire\Admin\Users;
use App\Livewire\DailyBoard;
use App\Models\Board;
use App\Models\BoardSubmission;
use App\Models\Cell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_precreate_disable_and_validate_users(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);
        $admin = User::factory()->create(['email' => 'admin@azienda.it']);

        Livewire::actingAs($admin)->test(Users::class)
            ->set('email', 'USER@azienda.it')
            ->set('name', 'User')
            ->set('startingScore', 2)
            ->set('isEnabled', false)
            ->call('save');

        $this->assertDatabaseHas('users', ['email' => 'user@azienda.it', 'is_enabled' => false, 'starting_score' => 2]);
        Livewire::actingAs(User::factory()->create())->test(Users::class)->assertStatus(403);
    }

    public function test_admin_regenerates_a_board_and_the_player_opens_a_new_pack(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);
        $admin = User::factory()->create(['email' => 'admin@azienda.it']);
        $player = User::factory()->create();
        Cell::factory()->count(30)->create();

        $page = Livewire::actingAs($player)->test(DailyBoard::class)->call('openPack')->call('toggle', 0);
        $old = Board::first();
        BoardSubmission::factory()->for($old, 'board')->create();
        $yesterday = Board::factory()->for($player)->create(['played_on' => today()->subDay()]);
        $colleague = Board::factory()->create();

        Livewire::actingAs($admin)->test(Users::class)
            ->assertSee('wire:click="regenerateBoard('.$player->id.')"', false)
            ->assertDontSee('wire:click="regenerateBoard('.$admin->id.')"', false)
            ->call('regenerateBoard', $player->id)
            ->assertDispatched('admin-saved')
            ->assertDontSee('wire:click="regenerateBoard('.$player->id.')"', false);

        $this->assertModelMissing($old);
        $this->assertDatabaseCount('board_cells', 0);
        $this->assertDatabaseCount('board_submissions', 0);
        $this->assertModelExists($yesterday);
        $this->assertModelExists($colleague);

        // The player's page was still showing the old board: a stale tap must not blow up.
        $this->actingAs($player);
        $page->call('toggle', 1)
            ->assertStatus(200)
            ->assertSee('Apri la bustina')
            ->assertViewHas('regenerated', true)
            ->assertDontSee('wire:click="toggle(0)', false);

        $page->call('openPack')
            ->assertDispatched('board-unpacked')
            ->assertSee('wire:click="toggle(0)', false);

        $fresh = Board::where('user_id', $player->id)->whereDate('played_on', today())->sole();
        $this->assertNotSame($old->id, $fresh->id);
        $this->assertSame(0, $fresh->markedCount(), 'the new board starts clean');
        $this->assertTrue(Cache::missing(RegenerateDailyBoard::noticeKey($player->id)));
    }

    public function test_regenerating_a_still_sealed_pack_changes_nothing(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);
        $admin = User::factory()->create(['email' => 'admin@azienda.it']);
        $player = User::factory()->create();

        Livewire::actingAs($admin)->test(Users::class)
            ->assertDontSee('Rigenera scheda')
            ->call('regenerateBoard', $player->id)
            ->assertDispatched('admin-saved');

        $this->assertTrue(Cache::missing(RegenerateDailyBoard::noticeKey($player->id)));
        Livewire::actingAs($player)->test(DailyBoard::class)->assertViewHas('regenerated', false);
    }
}
