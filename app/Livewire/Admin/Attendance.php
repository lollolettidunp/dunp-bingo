<?php

namespace App\Livewire\Admin;

use App\Actions\ReplaceInvalidBoardCells;
use App\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Component;

class Attendance extends Component
{
    public function mount(): void
    {
        $this->authorizeAdmin();
    }

    public function togglePresence(int $userId, ReplaceInvalidBoardCells $replaceInvalidBoardCells): void
    {
        $this->authorizeAdmin();
        $user = User::findOrFail($userId);
        $user->update(['is_present' => ! $user->is_present]);

        $today = CarbonImmutable::today(config('app.timezone'));

        // Le "celle collegate" sono quelle in cui l'utente compare in "Escludi per".
        // Una cella resta disattivata finche' almeno un collegato e' assente.
        foreach ($user->excludedCells()->with('users:id,is_present')->get() as $cell) {
            $shouldBeActive = $cell->users->every(fn (User $u) => $u->is_present);
            if ($cell->is_active !== $shouldBeActive) {
                $cell->update(['is_active' => $shouldBeActive]);
                $replaceInvalidBoardCells($cell->fresh(), $today);
            }
        }

        $this->dispatch('admin-saved', message: $user->is_present
            ? "{$user->name} e' di nuovo in ufficio"
            : "{$user->name} segnato assente");
    }

    public function render()
    {
        return view('livewire.admin.attendance', [
            'users' => User::withCount('excludedCells')->orderBy('name')->get(),
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(strtolower((string) auth()->user()?->email) === strtolower((string) config('services.google.admin_email')), 403);
    }
}
