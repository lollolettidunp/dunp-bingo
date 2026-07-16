<?php

namespace App\Livewire;

use App\Actions\CreateDailyBoard;
use App\Models\Board;
use App\Models\BoardCell;
use App\Models\Cell;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Collection;
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

    public function applySpecialCell(int $cellId, int $position): void
    {
        abort_unless($this->board?->user_id === auth()->id(), 403);
        abort_if($position === Board::BONUS_POSITION, 422);

        $special = $this->specialCells()->firstWhere('id', $cellId);
        abort_unless($special, 404);

        $this->board->cells()->where('position', $position)->firstOrFail()->update([
            'cell_id' => $special->id,
            'text' => $special->text,
            'difficulty' => $special->difficulty,
            'marked_at' => null,
        ]);
        $this->board->refresh()->load('cells');
        $this->dispatch('special-cell-applied', position: $position);
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

        return view('livewire.daily-board', [
            'specialAppliedCellIds' => $this->specialAppliedCellIds(),
            'specialCells' => $this->specialCells(),
            'missedCellIds' => $this->missedCellIds(),
        ]);
    }

    /**
     * Cell ids a colleague already checked today that I still haven't — the same
     * set shown in the "missed" feed. Used to flag them on my own board so I can
     * spot what to mark without scrolling the notifications.
     */
    private function missedCellIds(): array
    {
        if (! $this->board) {
            return [];
        }

        return BoardCell::missedEntriesFor(auth()->id())
            ->pluck('cell_id')
            ->all();
    }

    private function specialAppliedCellIds(): array
    {
        if (! $this->board) {
            return [];
        }

        $used = $this->board->cells()->whereNotNull('cell_id')->pluck('cell_id')->all();

        return Cell::query()
            ->whereIn('id', $used)
            ->whereDate('special_date', $this->board->played_on)
            ->pluck('id')
            ->all();
    }
    private function specialCells(): Collection
    {
        if (! $this->board) {
            return collect();
        }

        $used = $this->board->cells()->whereNotNull('cell_id')->pluck('cell_id')->all();

        return Cell::query()
            ->where('is_active', true)
            ->whereDate('special_date', today(config('app.timezone')))
            ->whereNotIn('id', $used)
            ->where(fn ($query) => $query
                ->whereDoesntHave('users')
                ->orWhereHas('users', fn ($users) => $users->where('users.id', auth()->id())))
            ->orderBy('difficulty')
            ->orderBy('text')
            ->get();
    }
}
