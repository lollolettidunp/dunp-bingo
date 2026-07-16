<?php

namespace App\Livewire;

use App\Models\CellSuggestion;
use Livewire\Component;

class SuggestCell extends Component
{
    public bool $open = false;
    public string $text = '';
    public int $difficulty = 2;

    public function openModal(): void
    {
        $this->reset(['text', 'difficulty']);
        $this->resetValidation();
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function submit(): void
    {
        $data = $this->validate([
            'text' => ['required', 'string', 'max:255'],
            'difficulty' => ['required', 'integer', 'between:1,3'],
        ]);

        CellSuggestion::create([
            'user_id' => auth()->id(),
            'text' => $data['text'],
            'difficulty' => $data['difficulty'],
        ]);

        $this->reset(['text', 'difficulty']);
        $this->open = false;
        $this->dispatch('cell-suggested');
    }

    public function render()
    {
        return view('livewire.suggest-cell');
    }
}
