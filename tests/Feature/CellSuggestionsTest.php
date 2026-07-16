<?php

namespace Tests\Feature;

use App\Livewire\Admin\Suggestions;
use App\Livewire\SuggestCell;
use App\Models\Cell;
use App\Models\CellSuggestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CellSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_suggest_a_cell_from_their_board(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(SuggestCell::class)
            ->call('openModal')
            ->assertSet('open', true)
            ->set('text', 'Qualcuno arriva in ritardo')
            ->set('difficulty', 3)
            ->call('submit')
            ->assertSet('open', false)
            ->assertDispatched('cell-suggested');

        $suggestion = CellSuggestion::first();
        $this->assertSame('Qualcuno arriva in ritardo', $suggestion->text);
        $this->assertSame(3, $suggestion->difficulty);
        $this->assertSame($user->id, $suggestion->user_id);
    }

    public function test_admin_can_publish_a_suggestion_as_a_cell(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);
        $admin = User::factory()->create(['email' => 'admin@azienda.it']);
        $suggestion = CellSuggestion::create([
            'user_id' => User::factory()->create()->id,
            'text' => 'Riunione che poteva essere una mail',
            'difficulty' => 2,
        ]);

        Livewire::actingAs($admin)->test(Suggestions::class)
            ->call('publish', $suggestion->id)
            ->assertDispatched('admin-saved');

        $cell = Cell::firstWhere('text', 'Riunione che poteva essere una mail');
        $this->assertNotNull($cell);
        $this->assertSame(2, $cell->difficulty);
        $this->assertTrue($cell->is_active);
        $this->assertNull($cell->special_date);
        $this->assertDatabaseMissing('cell_suggestions', ['id' => $suggestion->id]);
    }

    public function test_admin_can_delete_a_suggestion(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);
        $admin = User::factory()->create(['email' => 'admin@azienda.it']);
        $suggestion = CellSuggestion::create([
            'user_id' => User::factory()->create()->id,
            'text' => 'Consiglio da scartare',
            'difficulty' => 1,
        ]);

        Livewire::actingAs($admin)->test(Suggestions::class)
            ->call('delete', $suggestion->id);

        $this->assertDatabaseMissing('cell_suggestions', ['id' => $suggestion->id]);
    }

    public function test_non_admin_cannot_access_the_suggestions_panel(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);

        Livewire::actingAs(User::factory()->create())->test(Suggestions::class)->assertStatus(403);
    }
}
