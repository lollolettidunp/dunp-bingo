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

    public function hasBingo(): bool
    {
        $marked = $this->cells->whereNotNull('marked_at')->pluck('position')->all();
        $lines = [
            [0,1,2,3,4], [5,6,7,8,9], [10,11,12,13,14], [15,16,17,18,19], [20,21,22,23,24],
            [0,5,10,15,20], [1,6,11,16,21], [2,7,12,17,22], [3,8,13,18,23], [4,9,14,19,24],
            [0,6,12,18,24], [4,8,12,16,20],
        ];

        return collect($lines)->contains(fn (array $line) => count(array_intersect($line, $marked)) === 5);
    }
}
