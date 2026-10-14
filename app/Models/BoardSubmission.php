<?php

namespace App\Models;

use App\Support\Scoring;
use Database\Factories\BoardSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One claim: the bingo lines a player sent in for review, and what they earned. */
class BoardSubmission extends Model
{
    /** @use HasFactory<BoardSubmissionFactory> */
    use HasFactory;

    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $fillable = [
        'board_id', 'lines', 'status', 'submitted_at', 'reviewed_at', 'review_note',
        'points', 'base_points', 'multiplier',
    ];

    protected function casts(): array
    {
        return [
            'lines' => 'array',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'points' => 'float',
            'base_points' => 'float',
            'multiplier' => 'float',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function lineCount(): int
    {
        return count($this->lines ?? []);
    }

    /**
     * What approving this claim right now would pay. Also drives the admin
     * preview, so the reviewer sees the number before committing to it.
     *
     * @return array{points: float, base: float, multiplier: float}
     */
    public function award(): array
    {
        $approvedElsewhere = $this->board->submissions
            ->where('status', self::APPROVED)
            ->where('id', '!=', $this->id)
            ->pluck('lines')
            ->flatten()
            ->count();

        return Scoring::award($this->lineCount(), $approvedElsewhere === 0, $this->submitted_at);
    }

    /** "1 × 2" — how the points were reached, for the breakdown in the UI. */
    public function breakdown(): string
    {
        $base = Scoring::format($this->base_points);

        return $this->multiplier > 1
            ? $base.' × '.Scoring::format($this->multiplier)
            : $base;
    }
}
