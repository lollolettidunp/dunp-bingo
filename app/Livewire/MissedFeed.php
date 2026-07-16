<?php

namespace App\Livewire;

use App\Models\BoardCell;
use Livewire\Component;

class MissedFeed extends Component
{
    public function render()
    {
        return view('livewire.missed-feed', [
            'entries' => BoardCell::missedEntriesFor(auth()->id()),
        ]);
    }
}