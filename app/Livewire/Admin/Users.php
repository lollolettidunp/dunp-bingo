<?php

namespace App\Livewire\Admin;

use App\Actions\RegenerateDailyBoard;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Users extends Component
{
    public ?int $userId = null;
    public string $email = '';
    public string $name = '';
    public int $startingScore = 0;
    public bool $isEnabled = true;

    public function mount(): void
    {
        $this->authorizeAdmin();
    }

    public function edit(int $id): void
    {
        $this->authorizeAdmin();
        $user = User::findOrFail($id);
        $this->userId = $user->id;
        $this->email = $user->email;
        $this->name = $user->name;
        $this->startingScore = $user->starting_score;
        $this->isEnabled = $user->is_enabled;
    }

    public function save(): void
    {
        $this->authorizeAdmin();
        $data = $this->validate([
            'email' => ['required', 'email', Rule::unique('users')->ignore($this->userId)],
            'name' => ['required', 'string', 'max:255'],
            'startingScore' => ['required', 'integer', 'min:0'],
            'isEnabled' => ['boolean'],
        ]);

        User::updateOrCreate(['id' => $this->userId], [
            'email' => strtolower($data['email']),
            'name' => $data['name'],
            'starting_score' => $data['startingScore'],
            'is_enabled' => $data['isEnabled'],
        ]);

        $this->resetForm();
        $this->dispatch('admin-saved', message: 'Utente salvato');
    }

    public function cancel(): void
    {
        $this->resetForm();
    }

    public function regenerateBoard(int $id, RegenerateDailyBoard $regenerate): void
    {
        $this->authorizeAdmin();
        $user = User::findOrFail($id);

        $this->dispatch('admin-saved', message: $regenerate($user, CarbonImmutable::today(config('app.timezone')))
            ? "Scheda di {$user->name} rigenerata: riparte dalla bustina"
            : "{$user->name} non ha ancora aperto la bustina di oggi");
    }

    private function resetForm(): void
    {
        $this->reset(['userId', 'email', 'name']);
        $this->startingScore = 0;
        $this->isEnabled = true;
        $this->resetValidation();
    }

    public function render()
    {
        $today = CarbonImmutable::today(config('app.timezone'));

        return view('livewire.admin.users', [
            // Today's board (if the pack was opened) decides whether a row offers "Rigenera scheda".
            'users' => User::with(['boards' => fn ($boards) => $boards->whereDate('played_on', $today)->withCount('submissions')])
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(strtolower((string) auth()->user()?->email) === strtolower((string) config('services.google.admin_email')), 403);
    }
}
