<?php

namespace App\Support;

use Carbon\CarbonInterface;

/** How a reviewed bingo turns into points. */
class Scoring
{
    /** The board's first approved bingo. */
    public const FIRST_BINGO = 1.0;

    /** Every further line, on the same board, whenever it lands. */
    public const EXTRA_BINGO = 0.5;

    /**
     * Speed bands on the submit time, in the app timezone: send the first bingo
     * in before 11:00 and it doubles, before 15:00 and it gains a half.
     * Ordered by hour — the first band the clock still fits in wins.
     */
    public const BANDS = [[11, 2.0], [15, 1.5]];

    public static function multiplierFor(CarbonInterface $submittedAt): float
    {
        $hour = $submittedAt->copy()->setTimezone(config('app.timezone'))->hour;

        foreach (self::BANDS as [$limit, $multiplier]) {
            if ($hour < $limit) {
                return $multiplier;
            }
        }

        return 1.0;
    }

    /**
     * What a submission of $lines lines is worth. The speed multiplier rides on
     * the first bingo only; extra lines are a flat half point each, so chasing
     * more lines never competes with sending the first one in early.
     *
     * @return array{points: float, base: float, multiplier: float}
     */
    public static function award(int $lines, bool $isFirstBingo, CarbonInterface $submittedAt): array
    {
        $multiplier = $isFirstBingo ? self::multiplierFor($submittedAt) : 1.0;
        $first = $isFirstBingo ? self::FIRST_BINGO : 0.0;
        $extras = ($isFirstBingo ? max($lines - 1, 0) : $lines) * self::EXTRA_BINGO;

        return [
            'base' => round($first + $extras, 2),
            'multiplier' => $multiplier,
            'points' => round($first * $multiplier + $extras, 2),
        ];
    }

    /** Italian decimals without dead zeroes: 2, 3,5, 0,5. */
    public static function format(float|int|string|null $points): string
    {
        $formatted = number_format((float) $points, 2, ',', '');

        return rtrim(rtrim($formatted, '0'), ',');
    }
}
