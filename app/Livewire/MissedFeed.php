<?php

namespace App\Livewire;

use App\Models\Board;
use App\Models\BoardCell;
use Livewire\Component;

class MissedFeed extends Component
{
    public function render()
    {
        $tz = config('app.timezone');
        $userId = auth()->id();

        // Caselle che ho già segnato oggi: quelle non le ho "perse".
        $myMarked = BoardCell::query()
            ->whereNotNull('marked_at')
            ->whereNotNull('cell_id')
            ->whereHas('board', fn ($q) => $q
                ->where('user_id', $userId)
                ->whereDate('played_on', today($tz)))
            ->pluck('cell_id')
            ->all();

        // Caselle segnate oggi dai colleghi, in ordine cronologico:
        // solo il primo che ha segnato ogni casella finisce in lista.
        $entries = BoardCell::query()
            ->whereNotNull('marked_at')
            ->whereNotNull('cell_id')
            ->where('position', '!=', Board::BONUS_POSITION)
            ->whereHas('board', fn ($q) => $q
                ->whereDate('played_on', today($tz))
                ->where('user_id', '!=', $userId))
            ->with('board.user:id,name,avatar_url')
            ->orderBy('marked_at')
            ->get()
            ->groupBy('cell_id')
            ->map(fn ($group) => $group->first())
            ->reject(fn (BoardCell $cell) => in_array($cell->cell_id, $myMarked, true))
            ->sortByDesc('marked_at')
            ->values();

        return view('livewire.missed-feed', [
            'entries' => $entries,
        ]);
    }
}
