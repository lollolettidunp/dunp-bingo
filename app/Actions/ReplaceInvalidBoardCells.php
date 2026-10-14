<?php

namespace App\Actions;

use App\Models\Board;
use App\Models\BoardCell;
use App\Models\Cell;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ReplaceInvalidBoardCells
{
    public function __invoke(Cell $changed, CarbonImmutable $date): void
    {
        DB::transaction(function () use ($changed, $date) {
            Board::with(['user', 'cells', 'submissions'])
                ->whereDate('played_on', $date)
                ->whereHas('cells', fn ($query) => $query->where('cell_id', $changed->id)->orWhere('covered_cell_id', $changed->id))
                ->get()
                ->each(fn (Board $board) => $this->replaceForBoard($board, $changed, $date));
        });
    }

    private function replaceForBoard(Board $board, Cell $changed, CarbonImmutable $date): void
    {
        if ($this->eligible($changed, $board->user, $date)) {
            return;
        }

        // Squares locked inside a bingo already sent in stay put: swapping one would
        // void a claim the player is waiting on, or one the admin already paid out.
        $frozen = $board->claimedPositions();

        // Hidden under a sticker: swap the snapshot silently so moving the sticker never restores it.
        foreach ($board->cells->where('covered_cell_id', $changed->id)->whereNotIn('position', $frozen) as $boardCell) {
            $replacement = $this->replacement($board, $date);
            $boardCell->update($replacement ? [
                'covered_cell_id' => $replacement->id,
                'covered_text' => $replacement->text,
                'covered_difficulty' => $replacement->difficulty,
                'covered_marked_at' => null,
            ] : ['covered_cell_id' => null]);
            $board->load('cells');
        }

        foreach ($board->cells->where('cell_id', $changed->id)->whereNotIn('position', $frozen) as $boardCell) {
            $replacement = $this->replacement($board, $date);
            if (! $replacement) {
                $board->update(['notice' => "La casella '{$boardCell->text}' non e' piu' valida per oggi, ma non ci sono celle sostitutive disponibili."]);

                return;
            }

            $oldText = $boardCell->text;
            $this->swap($boardCell, $replacement);
            $board->update(['notice' => "La casella '{$oldText}' e' stata sostituita perche' non e' piu' valida per oggi. Nuova casella: '{$replacement->text}'."]);
            $board->load('cells');
        }
    }

    private function replacement(Board $board, CarbonImmutable $date): ?Cell
    {
        $used = $board->cells->pluck('cell_id')->merge($board->cells->pluck('covered_cell_id'))->filter()->all();

        return Cell::query()
            ->where('is_active', true)
            ->whereNotIn('id', $used)
            ->whereNull('special_date')
            ->orderBy('id')
            ->get()
            ->first(fn (Cell $cell) => $this->eligible($cell, $board->user, $date));
    }

    private function eligible(Cell $cell, User $user, CarbonImmutable $date): bool
    {
        $weekday = ['domenica','lunedi','martedi','mercoledi','giovedi','venerdi','sabato'][$date->dayOfWeek];

        return $cell->is_active
            && $cell->special_date === null
            && ! in_array($weekday, $cell->excluded_weekdays ?? [], true)
            && ! $user->excludedCells()->whereKey($cell->id)->exists();
    }

    private function swap(BoardCell $boardCell, Cell $replacement): void
    {
        $boardCell->update([
            'cell_id' => $replacement->id,
            'text' => $replacement->text,
            'difficulty' => $replacement->difficulty,
            'marked_at' => null,
            'covered_cell_id' => null,
            'covered_text' => null,
            'covered_difficulty' => null,
            'covered_marked_at' => null,
        ]);
    }
}