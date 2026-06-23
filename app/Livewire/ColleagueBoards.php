<?php

namespace App\Livewire;

use App\Models\Board;
use Livewire\Component;

class ColleagueBoards extends Component
{
    public ?int $selectedBoardId = null;

    public function select(int $boardId): void
    {
        $this->selectedBoardId = $boardId;
    }

    public function render()
    {
        $boards = Board::query()
            ->with(['user:id,name,avatar_url', 'cells:id,board_id,position,text,marked_at'])
            ->whereDate('played_on', today(config('app.timezone')))
            ->orderBy('user_id')
            ->get();

        $this->selectedBoardId ??= $boards->first()?->id;

        return view('livewire.colleague-boards', [
            'boards' => $boards,
            'selected' => $boards->firstWhere('id', $this->selectedBoardId) ?? $boards->first(),
        ]);
    }
}
