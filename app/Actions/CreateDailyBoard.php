<?php

namespace App\Actions;

use App\Models\Board;
use App\Models\Cell;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CreateDailyBoard
{
    public function __invoke(User $user, CarbonImmutable $date): Board
    {
        return DB::transaction(function () use ($user, $date) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $existing = Board::with('cells')->where('user_id', $user->id)->whereDate('played_on', $date)->first();
            if ($existing) {
                return $existing;
            }

            $weekday = ['domenica','lunedi','martedi','mercoledi','giovedi','venerdi','sabato'][$date->dayOfWeek];
            $excludedIds = $user->excludedCells()->pluck('cells.id');
            $eligible = Cell::query()
                ->where('is_active', true)
                ->whereNotIn('id', $excludedIds)
                ->where(fn ($query) => $query->whereNull('special_date')->orWhereDate('special_date', $date))
                ->get()
                ->reject(fn (Cell $cell) => in_array($weekday, $cell->excluded_weekdays ?? [], true));

            $special = $eligible->whereNotNull('special_date')->values();
            $ordinary = $eligible->whereNull('special_date')->values();

            throw_if($special->count() > 24, DomainException::class, 'Troppe celle speciali per questa data.');
            throw_if($ordinary->count() + $special->count() < 24, DomainException::class, 'Servono almeno 24 celle eleggibili.');

            $cells = $this->bestCells($special, $ordinary);
            $board = Board::create(['user_id' => $user->id, 'played_on' => $date->toDateString()]);
            foreach ($cells as $position => $cell) {
                $board->cells()->create($cell ? [
                    'cell_id' => $cell->id,
                    'position' => $position,
                    'text' => $cell->text,
                    'difficulty' => $cell->difficulty,
                ] : [
                    'position' => $position,
                    'text' => 'BONUS',
                    'difficulty' => 0,
                    'marked_at' => now(),
                ]);
            }

            return $board->load('cells');
        });
    }

    private function bestCells(Collection $special, Collection $ordinary): Collection
    {
        $best = null;
        $bestSpread = PHP_INT_MAX;

        // ponytail: 100 tentativi bastano per 24 celle; usare un solver solo se lo sbilanciamento diventa misurabile.
        for ($i = 0; $i < 100; $i++) {
            $picked = $ordinary->shuffle()->take(24 - $special->count())->merge($special)->shuffle()->values();
            $picked->splice(12, 0, [null]);
            $spread = $this->spread($picked);
            if ($spread < $bestSpread) {
                $best = $picked;
                $bestSpread = $spread;
            }
            if ($spread <= 1) {
                break;
            }
        }

        return $best;
    }

    private function spread(Collection $cells): int
    {
        $weights = $cells->map(fn ($cell) => $cell?->difficulty ?? 0)->all();
        $lines = [[0,1,2,3,4], [5,6,7,8,9], [10,11,12,13,14], [15,16,17,18,19], [20,21,22,23,24], [0,5,10,15,20], [1,6,11,16,21], [2,7,12,17,22], [3,8,13,18,23], [4,9,14,19,24]];
        $totals = array_map(fn ($line) => array_sum(array_map(fn ($i) => $weights[$i], $line)), $lines);

        return max($totals) - min($totals);
    }
}
