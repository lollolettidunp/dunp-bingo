<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CellSuggestion extends Model
{
    protected $fillable = ['user_id', 'text', 'difficulty'];

    protected function casts(): array
    {
        return [
            'difficulty' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
