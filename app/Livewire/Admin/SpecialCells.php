<?php

namespace App\Livewire\Admin;

use App\Models\Cell;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SpecialCells extends Component
{
    public ?int $cellId = null;
    public string $text = '';
    public int $difficulty = 2;
    public bool $isActive = true;
    public ?string $specialDate = null;
    public array $userIds = [];

    public function mount(): void
    {
        $this->authorizeAdmin();
        $this->specialDate = today(config('app.timezone'))->toDateString();
    }

    public function edit(int $id): void
    {
        $this->authorizeAdmin();
        $cell = Cell::with('users')->whereNotNull('special_date')->findOrFail($id);
        $this->cellId = $cell->id;
        $this->text = $cell->text;
        $this->difficulty = $cell->difficulty;
        $this->isActive = $cell->is_active;
        $this->specialDate = $cell->special_date?->toDateString();
        $this->userIds = $cell->users->pluck('id')->all();
    }

    public function save(): void
    {
        $this->authorizeAdmin();
        $data = $this->validate([
            'text' => ['required', 'string', 'max:255', Rule::unique('cells')->ignore($this->cellId)],
            'difficulty' => ['required', 'integer', 'between:1,3'],
            'isActive' => ['boolean'],
            'specialDate' => ['required', 'date'],
            'userIds' => ['array'],
            'userIds.*' => ['integer', 'exists:users,id'],
        ]);

        $cell = Cell::updateOrCreate(['id' => $this->cellId], [
            'text' => $data['text'],
            'difficulty' => $data['difficulty'],
            'is_active' => $data['isActive'],
            'special_date' => $data['specialDate'],
            'excluded_weekdays' => [],
        ]);
        $cell->users()->sync($data['userIds']);

        $this->resetForm();
        $this->dispatch('admin-saved', message: 'Adesivo salvato');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['cellId', 'text', 'userIds']);
        $this->difficulty = 2;
        $this->isActive = true;
        $this->specialDate = today(config('app.timezone'))->toDateString();
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.special-cells', [
            'cells' => Cell::with('users')->whereNotNull('special_date')->latest('special_date')->latest()->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(strtolower((string) auth()->user()?->email) === strtolower((string) config('services.google.admin_email')), 403);
    }
}
