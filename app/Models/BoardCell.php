<?php

namespace App\Models;

use Database\Factories\BoardCellFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardCell extends Model
{
    /** @use HasFactory<BoardCellFactory> */
    use HasFactory;

    protected $fillable = [
        'board_id', 'cell_id', 'position', 'text', 'difficulty', 'marked_at',
        'covered_cell_id', 'covered_text', 'covered_difficulty', 'covered_marked_at',
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => 'integer',
            'position' => 'integer',
            'marked_at' => 'datetime',
            'covered_difficulty' => 'integer',
            'covered_marked_at' => 'datetime',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /** True when a daily sticker sits on top of an ordinary cell we can restore. */
    public function isCovering(): bool
    {
        return $this->covered_text !== null;
    }

    /**
     * Cells a colleague marked today that the given user has not marked yet —
     * the "missed" feed. Only the first colleague to mark each cell is kept,
     * newest first. Powers both the notification drawer and the board highlight.
     *
     * @return Collection<int, BoardCell>
     */
    public static function missedEntriesFor(int $userId): Collection
    {
        $tz = config('app.timezone');

        $myMarked = static::query()
            ->whereNotNull('marked_at')
            ->whereNotNull('cell_id')
            ->whereHas('board', fn ($q) => $q
                ->where('user_id', $userId)
                ->whereDate('played_on', today($tz)))
            ->pluck('cell_id')
            ->all();

        return static::query()
            ->whereNotNull('marked_at')
            ->whereNotNull('cell_id')
            ->where('position', '!=', Board::BONUS_POSITION)
            ->whereHas('board', fn ($q) => $q
                ->whereDate('played_on', today($tz))
                ->where('user_id', '!=', $userId))
            ->with('board.user:id,name,avatar_url')
            ->orderBy('marked_at')
            ->get()
            ->groupBy('cell_id')
            ->map(fn ($group) => $group->first())
            ->reject(fn (BoardCell $cell) => in_array($cell->cell_id, $myMarked, true))
            ->sortByDesc('marked_at')
            ->values();
    }
}
