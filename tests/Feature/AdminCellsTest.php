<?php

namespace Tests\Feature;

use App\Livewire\Admin\Cells;
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
            ->set('specialDate', '2026-06-23')
            ->set('userIds', [$target->id])
            ->call('save');

        $cell = Cell::first();
        $this->assertSame('Evento raro', $cell->text);
        $this->assertTrue($cell->users->contains($target));
        Livewire::actingAs(User::factory()->create())->test(Cells::class)->assertStatus(403);
    }
}
