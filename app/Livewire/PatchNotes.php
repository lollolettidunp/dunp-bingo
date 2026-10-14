<?php

namespace App\Livewire;

use Carbon\CarbonImmutable;
use Livewire\Component;

class PatchNotes extends Component
{
    /** Bump it together with the notes in the view: every release is shown to each player once. */
    public const VERSION = '2026-10-bustina';

    /** First day the popup may appear: it greets players at a day reset, never mid-game. */
    public const SHOW_FROM = '2026-10-07';

    public function dismiss(): void
    {
        auth()->user()->update(['patch_notes_seen' => self::VERSION]);
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.patch-notes', [
            'due' => $user
                && $user->patch_notes_seen !== self::VERSION
                && CarbonImmutable::today(config('app.timezone'))->toDateString() >= self::SHOW_FROM,
        ]);
    }
}
