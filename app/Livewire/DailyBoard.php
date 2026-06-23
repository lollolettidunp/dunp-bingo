<?php

namespace App\Livewire;

use App\Actions\CreateDailyBoard;
use App\Models\Board;
use Carbon\CarbonImmutable;
use DomainException;
use Livewire\Component;

class DailyBoard extends Component
{
    public ?Board $board = null;
    public ?string $generationError = null;

    public function mount(CreateDailyBoard $create): void
    {
        try {
            $this->board = $create(auth()->user(), CarbonImmutable::today(config('app.timezone')));
        } catch (DomainException $exception) {
            report($exception);
            $this->generationError = $exception->getMessage();
        }
    }

    public function toggle(int $position): void
    {
        abort_unless($this->board?->user_id === auth()->id(), 403);
        abort_unless(in_array($this->board->status, [Board::PLAYING, Board::REJECTED], true), 409);
        abort_if($position === 12, 422);

        $this->board->load('cells');
        $hadBingo = $this->board->hasBingo();
        $cell = $this->board->cells()->where('position', $position)->firstOrFail();
        $marked = ! $cell->marked_at;
        $cell->update(['marked_at' => $marked ? now() : null]);
        $this->board->refresh()->load('cells');

        if ($marked) {
            $this->dispatch('cell-marked', position: $position);
        }
        if (! $hadBingo && $this->board->hasBingo()) {
            $this->dispatch('bingo-completed');
        }
    }

    public function submit(): void
    {
        abort_unless($this->board?->user_id === auth()->id(), 403);
        abort_unless(in_array($this->board->status, [Board::PLAYING, Board::REJECTED], true), 409);
        $this->board->refresh()->load('cells');
        abort_unless($this->board->hasBingo(), 422);
        $this->board->update(['status' => Board::PENDING, 'submitted_at' => now()]);
    }

    public function render()
    {
        $this->board?->load('cells');

        return view('livewire.daily-board');
    }
}
