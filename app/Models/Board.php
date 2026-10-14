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

    /** No claim sent in yet. Every other state lives on the submissions. */
    public const PLAYING = 'playing';

    protected $fillable = ['user_id', 'played_on', 'notice'];

    protected function casts(): array
    {
        return [
            'played_on' => 'date',
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

    public function submissions(): HasMany
    {
        return $this->hasMany(BoardSubmission::class)->orderBy('submitted_at')->orderBy('id');
    }

    /** playing | pending | approved | rejected — what the board looks like right now. */
    public function state(): string
    {
        return match (true) {
            $this->submissions->contains('status', BoardSubmission::PENDING) => BoardSubmission::PENDING,
            $this->submissions->contains('status', BoardSubmission::APPROVED) => BoardSubmission::APPROVED,
            $this->submissions->contains('status', BoardSubmission::REJECTED) => BoardSubmission::REJECTED,
            default => self::PLAYING,
        };
    }

    public function statusLabel(): string
    {
        return match ($this->state()) {
            BoardSubmission::PENDING => 'In Revisione',
            BoardSubmission::APPROVED => 'Completata!',
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

    /** Indexes into self::LINES whose five squares are all marked right now. */
    public function completedLines(): array
    {
        $marked = $this->cells->whereNotNull('marked_at')->pluck('position')->all();

        return collect(self::LINES)
            ->filter(fn (array $line) => count(array_intersect($line, $marked)) === 5)
            ->keys()
            ->all();
    }

    public function hasBingo(): bool
    {
        return $this->completedLines() !== [];
    }

    /** Lines already sent in and not rejected: they can't be claimed a second time. */
    public function claimedLines(): array
    {
        return $this->submissions
            ->where('status', '!=', BoardSubmission::REJECTED)
            ->pluck('lines')
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    /** Completed lines still worth sending in — what the submit button offers. */
    public function submittableLines(): array
    {
        return array_values(array_diff($this->completedLines(), $this->claimedLines()));
    }

    /** Squares locked inside a claim: they must not be swapped from under the player. */
    public function claimedPositions(): array
    {
        return collect($this->claimedLines())
            ->flatMap(fn (int $line) => self::LINES[$line])
            ->unique()
            ->values()
            ->all();
    }

    public function approvedLineCount(): int
    {
        return $this->linesWithStatus(BoardSubmission::APPROVED);
    }

    public function pendingLineCount(): int
    {
        return $this->linesWithStatus(BoardSubmission::PENDING);
    }

    /** Points the board has actually banked, frozen at review time. */
    public function points(): float
    {
        return (float) $this->submissions->where('status', BoardSubmission::APPROVED)->sum('points');
    }

    /** The admin note to show back, only while the last thing that happened was a rejection. */
    public function lastRejection(): ?BoardSubmission
    {
        $last = $this->submissions->last();

        return $last?->status === BoardSubmission::REJECTED ? $last : null;
    }

    /** Every position that belongs to a completed line — for highlighting the win. */
    public function winningPositions(): array
    {
        return collect($this->completedLines())
            ->flatMap(fn (int $line) => self::LINES[$line])
            ->unique()
            ->values()
            ->all();
    }

    /** Squares the player has marked, excluding the free center, out of self::MARKABLE. */
    public function markedCount(): int
    {
        return $this->cells
            ->whereNotNull('marked_at')
            ->where('position', '!=', self::BONUS_POSITION)
            ->count();
    }

    /**
     * Fewest squares still needed to complete any line that isn't claimed yet.
     * Returns 0 when there's a bingo ready to send. Drives the "ti manca N" nudge.
     */
    public function squaresFromBingo(): int
    {
        $marked = $this->cells->whereNotNull('marked_at')->pluck('position')->all();
        $claimed = $this->claimedLines();

        return collect(self::LINES)
            ->reject(fn (array $line, int $index) => in_array($index, $claimed, true))
            ->map(fn (array $line) => 5 - count(array_intersect($line, $marked)))
            ->min() ?? 5;
    }

    private function linesWithStatus(string $status): int
    {
        return $this->submissions->where('status', $status)->pluck('lines')->flatten()->count();
    }
}
