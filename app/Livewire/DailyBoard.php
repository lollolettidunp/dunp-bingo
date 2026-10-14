<?php

namespace App\Livewire;

use App\Actions\CreateDailyBoard;
use App\Actions\RegenerateDailyBoard;
use App\Models\Board;
use App\Models\BoardCell;
use App\Models\BoardSubmission;
use App\Models\Cell;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DailyBoard extends Component
{
    public ?string $generationError = null;

    /**
     * Today's board, null while the pack is still sealed. Looked up on every
     * request rather than kept in the component state: the admin can regenerate
     * it under an open page (or it gets opened on another device).
     */
    #[Computed]
    public function board(): ?Board
    {
        return Board::query()
            ->where('user_id', auth()->id())
            ->whereDate('played_on', CarbonImmutable::today(config('app.timezone')))
            ->first();
    }

    /**
     * The day's board is only dealt when the player tears the pack open: until
     * then the admin can still fix attendance or cells and have them count.
     */
    public function openPack(CreateDailyBoard $create): void
    {
        $this->generationError = null;

        try {
            $create(auth()->user(), CarbonImmutable::today(config('app.timezone')));
            unset($this->board);
            Cache::forget(RegenerateDailyBoard::noticeKey(auth()->id()));
            $this->dispatch('board-unpacked');
        } catch (DomainException $exception) {
            report($exception);
            $this->generationError = $exception->getMessage();
            $this->dispatch('board-pack-failed');
        }
    }

    public function toggle(int $position): void
    {
        // Regenerated under a page still showing the old board: the re-render brings the pack back.
        if (! $this->board) {
            return;
        }
        abort_if($position === 12, 422);

        $this->board->load('cells');
        // Every new line is worth celebrating, not just the first one of the day.
        $linesBefore = count($this->board->completedLines());
        $cell = $this->board->cells()->where('position', $position)->firstOrFail();
        $marked = ! $cell->marked_at;
        $cell->update(['marked_at' => $marked ? now() : null]);
        $this->board->refresh()->load('cells');

        if ($marked) {
            $this->dispatch('cell-marked', position: $position);
        }
        if (count($this->board->completedLines()) > $linesBefore) {
            $this->dispatch('bingo-completed');
        }
    }

    /**
     * Puts a daily sticker on a cell. If the sticker is already on the board it
     * is moved instead: its old cell gets the covered cell back (fixes misclicks).
     */
    public function applySpecialCell(int $cellId, int $position): void
    {
        if (! $this->board) {
            return;
        }
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

    /**
     * Sends in every completed line not claimed yet — the first bingo, or an
     * extra one found later in the day. The lock keeps a double tap from
     * claiming the same line twice.
     */
    public function submit(): void
    {
        if (! $this->board) {
            return;
        }

        $lines = DB::transaction(function () {
            $board = Board::with(['cells', 'submissions'])->whereKey($this->board->id)->lockForUpdate()->firstOrFail();
            $lines = $board->submittableLines();

            if ($lines !== []) {
                $board->submissions()->create([
                    'lines' => $lines,
                    'status' => BoardSubmission::PENDING,
                    'submitted_at' => now(),
                ]);
            }

            return $lines;
        });

        abort_if($lines === [], 422);
        $this->board->refresh()->load(['cells', 'submissions']);
    }

    public function render()
    {
        $board = $this->board?->load(['cells', 'submissions']);
        $specialAppliedCellIds = $board ? $this->specialAppliedCellIds() : [];

        return view('livewire.daily-board', [
            'board' => $board,
            'isAdmin' => strtolower((string) auth()->user()?->email) === strtolower((string) config('services.google.admin_email')),
            'regenerated' => ! $board && $this->regeneratedToday(),
            'specialAppliedCellIds' => $specialAppliedCellIds,
            'specialCells' => $board ? $this->specialCells() : collect(),
            'placedSpecials' => $board?->cells
                ->filter(fn (BoardCell $cell) => $cell->isCovering() && in_array($cell->cell_id, $specialAppliedCellIds, true))
                ->values() ?? collect(),
            'missedCellIds' => $board ? $this->missedCellIds() : [],
        ]);
    }

    /** The pack is sealed again because the admin threw today's board away. */
    private function regeneratedToday(): bool
    {
        return Cache::get(RegenerateDailyBoard::noticeKey(auth()->id())) === CarbonImmutable::today(config('app.timezone'))->toDateString();
    }

    /**
     * Cell ids a colleague already checked today that I still haven't — the same
     * set shown in the "missed" feed. Used to flag them on my own board so I can
     * spot what to mark without scrolling the notifications.
     */
    private function missedCellIds(): array
    {
        return BoardCell::missedEntriesFor(auth()->id())
            ->pluck('cell_id')
            ->all();
    }

    private function specialAppliedCellIds(): array
    {
        $used = $this->board->cells()->whereNotNull('cell_id')->pluck('cell_id')->all();

        return Cell::query()
            ->whereIn('id', $used)
            ->whereDate('special_date', $this->board->played_on)
            ->pluck('id')
            ->all();
    }
    private function specialCells(): Collection
    {
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
