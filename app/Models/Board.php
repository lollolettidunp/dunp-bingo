<?php

namespace App\Models;

use Database\Factories\BoardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    /** @use HasFactory<BoardFactory> */
    use HasFactory;

    public const PLAYING = 'playing';
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $fillable = ['user_id', 'played_on', 'status', 'submitted_at', 'reviewed_at', 'review_note'];

    protected function casts(): array
    {
        return [
            'played_on' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cells(): HasMany
    {
        return $this->hasMany(BoardCell::class)->orderBy('position');
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::PENDING => 'In Revisione',
            self::APPROVED => 'Completata!',
            default => 'In gioco',
        };
    }
    /** The 12 winning lines: 5 rows, 5 columns, 2 diagonals. */
    public const LINES = [
        [0,1,2,3,4], [5,6,7,8,9], [10,11,12,13,14], [15,16,17,18,19], [20,21,22,23,24],
        [0,5,10,15,20], [1,6,11,16,21], [2,7,12,17,22], [3,8,13,18,23], [4,9,14,19,24],
        [0,6,12,18,24], [4,8,12,16,20],
    ];

    /** Center (position 12) is the auto-marked free space, so it's excluded from player progress. */
    public const BONUS_POSITION = 12;

    public const MARKABLE = 24;

    public function hasBingo(): bool
    {
        $marked = $this->cells->whereNotNull('marked_at')->pluck('position')->all();

        return collect(self::LINES)->contains(fn (array $line) => count(array_intersect($line, $marked)) === 5);
    }

    /** Squares the player has marked, excluding the free center, out of self::MARKABLE. */
    public function markedCount(): int
    {
        return $this->cells
            ->whereNotNull('marked_at')
            ->where('position', '!=', self::BONUS_POSITION)
            ->count();
    }

    /** Every position that belongs to a completed line — for highlighting the win. */
    public function winningPositions(): array
    {
        $marked = $this->cells->whereNotNull('marked_at')->pluck('position')->all();

        return collect(self::LINES)
            ->filter(fn (array $line) => count(array_intersect($line, $marked)) === 5)
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Fewest squares still needed to complete any single line (1, 2, …).
     * Returns 0 when a bingo already exists. Drives the "ti manca N" nudge.
     */
    public function squaresFromBingo(): int
    {
        $marked = $this->cells->whereNotNull('marked_at')->pluck('position')->all();

        return collect(self::LINES)
            ->map(fn (array $line) => 5 - count(array_intersect($line, $marked)))
            ->min() ?? 5;
    }
}
