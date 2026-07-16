<?php

namespace App\Livewire;

use App\Models\Board;
use Livewire\Component;

class AdminBadge extends Component
{
    public function render()
    {
        return view('livewire.admin-badge', [
            'pending' => Board::where('status', Board::PENDING)->count(),
        ]);
    }
}
