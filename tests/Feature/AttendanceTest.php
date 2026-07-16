<?php

namespace Tests\Feature;

use App\Livewire\Admin\Attendance;
use App\Models\Cell;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);

        return User::factory()->create(['email' => 'admin@azienda.it']);
    }

    public function test_marking_a_user_absent_disables_their_linked_cells(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create();
        $linked = Cell::factory()->create(['is_active' => true]);
        $other = Cell::factory()->create(['is_active' => true]);
        $user->excludedCells()->attach($linked);

        Livewire::actingAs($admin)->test(Attendance::class)
            ->call('togglePresence', $user->id)
            ->assertDispatched('admin-saved');

        $this->assertFalse($user->fresh()->is_present);
        $this->assertFalse($linked->fresh()->is_active);
        $this->assertTrue($other->fresh()->is_active);
    }

    public function test_marking_a_user_present_again_re_enables_their_linked_cells(): void
    {
        $admin = $this->admin();
        $user = User::factory()->create(['is_present' => false]);
        $linked = Cell::factory()->create(['is_active' => false]);
        $user->excludedCells()->attach($linked);

        Livewire::actingAs($admin)->test(Attendance::class)
            ->call('togglePresence', $user->id);

        $this->assertTrue($user->fresh()->is_present);
        $this->assertTrue($linked->fresh()->is_active);
    }

    public function test_a_cell_stays_disabled_while_another_linked_user_is_absent(): void
    {
        $admin = $this->admin();
        $absent = User::factory()->create(['is_present' => false]);
        $returning = User::factory()->create(['is_present' => false]);
        $shared = Cell::factory()->create(['is_active' => false]);
        $absent->excludedCells()->attach($shared);
        $returning->excludedCells()->attach($shared);

        Livewire::actingAs($admin)->test(Attendance::class)
            ->call('togglePresence', $returning->id);

        $this->assertFalse($shared->fresh()->is_active);
    }

    public function test_non_admin_cannot_access_the_attendance_panel(): void
    {
        config(['services.google.admin_email' => 'admin@azienda.it']);

        Livewire::actingAs(User::factory()->create())->test(Attendance::class)->assertStatus(403);
    }
}
