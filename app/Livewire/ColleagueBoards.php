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
            ->with(['user:id,name,avatar_url', 'cells:id,board_id,position,text,marked_at', 'submissions'])
            ->whereDate('played_on', today(config('app.timezone')))
            ->get()
            // Furthest-along players first: bingos sent in, then a bingo on the board,
            // then most squares marked. Friendly competition.
            ->sortByDesc(fn (Board $board) => [count($board->claimedLines()), $board->hasBingo() ? 1 : 0, $board->markedCount()])
            ->values();

        $this->selectedBoardId ??= $boards->first()?->id;

        return view('livewire.colleague-boards', [
            'boards' => $boards,
            'selected' => $boards->firstWhere('id', $this->selectedBoardId) ?? $boards->first(),
        ]);
    }
}