<?php

namespace App\Livewire\Admin;

use App\Models\Board;
use Livewire\Component;

class Reviews extends Component
{
    public string $note = '';

    public function mount(): void
    {
        $this->authorizeAdmin();
    }

    public function approve(int $boardId): void
    {
        $this->authorizeAdmin();
        $board = Board::with('cells')->findOrFail($boardId);
        abort_unless($board->status === Board::PENDING && $board->hasBingo(), 409);
        Board::whereKey($boardId)->where('status', Board::PENDING)->update([
            'status' => Board::APPROVED,
            'reviewed_at' => now(),
            'review_note' => null,
        ]);
    }

    public function reject(int $boardId): void
    {
        $this->authorizeAdmin();
        $this->validate(['note' => ['nullable', 'string', 'max:1000']]);
        $updated = Board::whereKey($boardId)->where('status', Board::PENDING)->update([
            'status' => Board::REJECTED,
            'reviewed_at' => now(),
            'review_note' => $this->note ?: null,
        ]);
        abort_unless($updated === 1, 409);
        $this->note = '';
    }

    public function render()
    {
        return view('livewire.admin.reviews', [
            'boards' => Board::with(['user', 'cells'])->where('status', Board::PENDING)->oldest('submitted_at')->get(),
        ]);
    }

    private function authorizeAdmin(): void
    {
        abort_unless(strtolower((string) auth()->user()?->email) === strtolower((string) config('services.google.admin_email')), 403);
    }
}
