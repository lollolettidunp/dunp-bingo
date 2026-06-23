<?php

namespace App\Livewire\Admin;

use App\Models\Cell;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Cells extends Component
{
    public ?int $cellId = null;
    public string $text = '';
    public int $difficulty = 2;
    public bool $isActive = true;
    public ?string $specialDate = null;
    public array $excludedWeekdays = [];
    public array $userIds = [];

    public function mount(): void
    {
        $this->authorizeAdmin();
    }

    public function edit(int $id): void
    {
        $this->authorizeAdmin();
        $cell = Cell::with('users')->findOrFail($id);
        $this->cellId = $cell->id;
        $this->text = $cell->text;
        $this->difficulty = $cell->difficulty;
        $this->isActive = $cell->is_active;
        $this->specialDate = $cell->special_date?->toDateString();
        $this->excludedWeekdays = $cell->excluded_weekdays ?? [];
        $this->userIds = $cell->users->pluck('id')->all();
    }

    public function save(): void
    {
        $this->authorizeAdmin();
        $data = $this->validate([
            'text' => ['required', 'string', 'max:255', Rule::unique('cells')->ignore($this->cellId)],
            'difficulty' => ['required', 'integer', 'between:1,3'],
            'isActive' => ['boolean'],
            'specialDate' => ['nullable', 'date'],
            'excludedWeekdays' => ['array'],
            'excludedWeekdays.*' => [Rule::in(['lunedi','martedi','mercoledi','giovedi','venerdi','sabato','domenica'])],
            'userIds' => ['array'],
            'userIds.*' => ['integer', 'exists:users,id'],
        ]);

        $cell = Cell::updateOrCreate(['id' => $this->cellId], [
            'text' => $data['text'],
            'difficulty' => $data['difficulty'],
            'is_active' => $data['isActive'],
            'special_date' => $data['specialDate'],
            'excluded_weekdays' => $data['excludedWeekdays'],
        ]);
        $cell->users()->sync($data['userIds']);

        $this->resetForm();
        $this->dispatch('admin-saved', message: 'Cella salvata');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['cellId', 'text', 'specialDate', 'excludedWeekdays', 'userIds']);
        $this->difficulty = 2;
        $this->isActive = true;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.cells', [
            'cells' => Cell::with('users')->latest()->get(),
            'users' => User::orderBy('name')->get(),
            'weekdays' => ['lunedi','martedi','mercoledi','giovedi','venerdi','sabato','domenica'],
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(strtolower((string) auth()->user()?->email) === strtolower((string) config('services.google.admin_email')), 403);
    }
}
