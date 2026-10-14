<?php

namespace Tests\Feature;

use App\Livewire\PatchNotes;
use App\Models\User;
use App\Support\Scoring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class PatchNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_popup_waits_for_the_day_reset_after_the_release(): void
    {
        $user = User::factory()->create();

        Carbon::setTestNow('2026-10-06 23:30:00');
        Livewire::actingAs($user)->test(PatchNotes::class)->assertDontSee('patch-notes-title', false);

        Carbon::setTestNow('2026-10-07 00:05:00');
        Livewire::actingAs($user)->test(PatchNotes::class)->assertSee('patch-notes-title', false);
    }

    public function test_closing_it_hides_it_for_good_but_only_for_that_player(): void
    {
        Carbon::setTestNow('2026-10-08 09:00:00');
        $user = User::factory()->create();
        $colleague = User::factory()->create();

        Livewire::actingAs($user)->test(PatchNotes::class)
            ->assertSee('wire:click="dismiss"', false)
            ->call('dismiss')
            ->assertDontSee('wire:click="dismiss"', false);

        $this->assertSame(PatchNotes::VERSION, $user->fresh()->patch_notes_seen);
        Livewire::actingAs($user)->test(PatchNotes::class)->assertDontSee('patch-notes-title', false);
        Livewire::actingAs($colleague)->test(PatchNotes::class)->assertSee('patch-notes-title', false);
    }

    public function test_every_page_of_the_app_carries_the_popup(): void
    {
        Carbon::setTestNow('2026-10-07 09:00:00');
        $user = User::factory()->create();

        foreach (['board', 'colleagues', 'leaderboard'] as $route) {
            $this->actingAs($user)->get(route($route))->assertOk()->assertSee('patch-notes-title', false);
        }
    }

    public function test_the_notes_quote_the_scoring_actually_in_force(): void
    {
        Carbon::setTestNow('2026-10-07 09:00:00');
        [[$doubleUntil, $double], [$halfUntil, $half]] = Scoring::BANDS;

        $this->assertSame(2.0, $double, 'the notes say the first bingo doubles');
        Livewire::actingAs(User::factory()->create())->test(PatchNotes::class)
            ->assertSee(Scoring::format(Scoring::EXTRA_BINGO).' punti')
            ->assertSee("entro le {$doubleUntil}:00")
            ->assertSee('vale '.Scoring::format($half)." entro le {$halfUntil}:00");
    }
}
