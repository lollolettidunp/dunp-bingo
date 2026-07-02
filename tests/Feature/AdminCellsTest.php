<?php

namespace Tests\Feature;

use App\Livewire\Admin\Cells;
use App\Livewire\Admin\SpecialCells;
use App\Models\Cell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCellsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_edit_and_link_a_cell(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);
        $admin = User::factory()->create(['email' => 'admin@azienda.it']);
        $target = User::factory()->create();

        Livewire::actingAs($admin)->test(Cells::class)
            ->set('text', 'Evento raro')
            ->set('difficulty', 3)
            ->set('userIds', [$target->id])
            ->call('save');

        $cell = Cell::first();
        $this->assertSame('Evento raro', $cell->text);
        $this->assertNull($cell->special_date);
        $this->assertTrue($cell->users->contains($target));
        Livewire::actingAs(User::factory()->create())->test(Cells::class)->assertStatus(403);
    }

    public function test_admin_creates_daily_stickers_in_a_dedicated_panel(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);
        $admin = User::factory()->create(['email' => 'admin@azienda.it']);

        Livewire::actingAs($admin)->test(Cells::class)
            ->assertDontSee('Data speciale');

        Livewire::actingAs($admin)->test(SpecialCells::class)
            ->assertSee('Adesivi del giorno')
            ->set('text', 'One-time facile')
            ->set('difficulty', 1)
            ->set('specialDate', '2026-06-23')
            ->call('save');

        $cell = Cell::firstWhere('text', 'One-time facile');
        $this->assertSame('2026-06-23', $cell->special_date->toDateString());
    }

    public function test_editing_a_cell_opens_the_edit_form_in_a_modal(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);
        $admin = User::factory()->create(['email' => 'admin@azienda.it']);
        $cell = Cell::factory()->create(['text' => 'Evento da modificare']);

        Livewire::actingAs($admin)->test(Cells::class)
            ->call('edit', $cell->id)
            ->assertSee('role="dialog"', false)
            ->assertSee('Modifica cella')
            ->assertSee('Evento da modificare')
            ->call('cancel')
            ->assertDontSee('role="dialog"', false);
    }
}
