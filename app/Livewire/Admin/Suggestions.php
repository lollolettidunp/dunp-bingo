<?php

namespace App\Livewire\Admin;

use App\Models\Cell;
use App\Models\CellSuggestion;
use Livewire\Component;

class Suggestions extends Component
{
    public function mount(): void
    {
        $this->authorizeAdmin();
    }

    public function publish(int $id): void
    {
        $this->authorizeAdmin();
        $suggestion = CellSuggestion::findOrFail($id);

        if (Cell::whereRaw('LOWER(text) = ?', [mb_strtolower($suggestion->text)])->exists()) {
            $this->dispatch('admin-saved', message: 'Esiste già una cella con questo testo');

            return;
        }

        Cell::create([
            'text' => $suggestion->text,
            'difficulty' => $suggestion->difficulty,
            'is_active' => true,
            'special_date' => null,
            'excluded_weekdays' => [],
        ]);
        $suggestion->delete();

        $this->dispatch('admin-saved', message: 'Consiglio pubblicato');
    }

    public function delete(int $id): void
    {
        $this->authorizeAdmin();
        CellSuggestion::whereKey($id)->delete();

        $this->dispatch('admin-saved', message: 'Consiglio eliminato');
    }

    public function render()
    {
        return view('livewire.admin.suggestions', [
            'suggestions' => CellSuggestion::with('user')->latest()->get(),
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(strtolower((string) auth()->user()?->email) === strtolower((string) config('services.google.admin_email')), 403);
    }
}
