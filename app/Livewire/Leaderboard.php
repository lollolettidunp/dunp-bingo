<?php

namespace App\Livewire;

use App\Models\Board;
use App\Models\User;
use Livewire\Component;

class Leaderboard extends Component
{
    public function render()
    {
        $users = User::query()
            ->where('is_enabled', true)
            ->withCount(['boards as approved_count' => fn ($query) => $query->where('status', Board::APPROVED)])
            ->withMax(['boards as last_approved_at' => fn ($query) => $query->where('status', Board::APPROVED)], 'reviewed_at')
            ->get()
            ->map(fn (User $user) => tap($user)->setAttribute('score', $user->starting_score + $user->approved_count))
            ->sortBy([['score', 'desc'], ['name', 'asc']]);

        return view('livewire.leaderboard', ['users' => $users]);
    }
}
