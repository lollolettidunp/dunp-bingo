<?php

namespace App\Models;

use Database\Factories\CellFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cell extends Model
{
    /** @use HasFactory<CellFactory> */
    use HasFactory;

    protected $fillable = ['text', 'difficulty', 'is_active', 'special_date', 'excluded_weekdays'];

    protected function casts(): array
    {
        return [
            'difficulty' => 'integer',
            'is_active' => 'boolean',
            'special_date' => 'date',
            'excluded_weekdays' => 'array',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
