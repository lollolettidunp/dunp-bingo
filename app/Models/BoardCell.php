<?php

namespace App\Models;

use Database\Factories\BoardCellFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardCell extends Model
{
    /** @use HasFactory<BoardCellFactory> */
    use HasFactory;

    protected $fillable = ['board_id', 'cell_id', 'position', 'text', 'difficulty', 'marked_at'];

    protected function casts(): array
    {
        return [
            'difficulty' => 'integer',
            'position' => 'integer',
            'marked_at' => 'datetime',
        ];
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }
}
