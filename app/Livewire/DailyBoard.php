<?php

namespace App\Livewire;

use App\Actions\CreateDailyBoard;
use App\Models\Board;
use App\Models\BoardCell;
use App\Models\Cell;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

    /**
     * Puts a daily sticker on a cell. If the sticker is already on the board it
     * is moved instead: its old cell gets the covered cell back (fixes misclicks).
     */
    public function applySpecialCell(int $cellId, int $position): void
    {
        abort_unless($this->board?->user_id === auth()->id(), 403);
        abort_if($position === Board::BONUS_POSITION, 422);

        $target = $this->board->cells()->where('position', $position)->firstOrFail();
        $source = $this->board->cells()->where('cell_id', $cellId)->first();

        if ($source) {
            abort_unless($source->isCovering(), 409);
            if ($source->is($target)) {
                return;
            }
            DB::transaction(fn () => $this->moveSticker($source, $target));
        } else {
            $special = $this->specialCells()->firstWhere('id', $cellId);
            abort_unless($special, 404);

            $target->update([
                ...($target->isCovering() ? [] : $this->coveredSnapshot($target)),
                'cell_id' => $special->id,
                'text' => $special->text,
                'difficulty' => $special->difficulty,
                'marked_at' => null,
            ]);
        }

        $this->board->refresh()->load('cells');
        $this->dispatch('special-cell-applied', position: $position);
    }

    /** Stickers carry their mark along; landing on another sticker swaps the two. */
    private function moveSticker(BoardCell $source, BoardCell $target): void
    {
        $sticker = $source->only(['cell_id', 'text', 'difficulty', 'marked_at']);

        if ($target->isCovering()) {
            $source->update($target->only(['cell_id', 'text', 'difficulty', 'marked_at']));
            $target->update($sticker);

            return;
        }

        $target->update([...$this->coveredSnapshot($target), ...$sticker]);
        $source->update([
            'cell_id' => $source->covered_cell_id,
            'text' => $source->covered_text,
            'difficulty' => $source->covered_difficulty,
            'marked_at' => $source->covered_marked_at,
            'covered_cell_id' => null,
            'covered_text' => null,
            'covered_difficulty' => null,
            'covered_marked_at' => null,
        ]);
    }

    private function coveredSnapshot(BoardCell $cell): array
    {
        return [
            'covered_cell_id' => $cell->cell_id,
            'covered_text' => $cell->text,
            'covered_difficulty' => $cell->difficulty,
            'covered_marked_at' => $cell->marked_at,
        ];
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
        $specialAppliedCellIds = $this->specialAppliedCellIds();

        return view('livewire.daily-board', [
            'specialAppliedCellIds' => $specialAppliedCellIds,
            'specialCells' => $this->specialCells(),
            'placedSpecials' => $this->board?->cells
                ->filter(fn (BoardCell $cell) => $cell->isCovering() && in_array($cell->cell_id, $specialAppliedCellIds, true))
                ->values() ?? collect(),
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
