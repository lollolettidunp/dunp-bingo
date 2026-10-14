<?php

namespace App\Livewire;

use App\Models\BoardSubmission;
use Livewire\Component;

class AdminBadge extends Component
{
    public function render()
    {
        return view('livewire.admin-badge', [
            'pending' => BoardSubmission::where('status', BoardSubmission::PENDING)->count(),
        ]);
    }
}
