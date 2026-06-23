<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'google_id', 'avatar_url', 'is_enabled', 'starting_score'];

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    public function excludedCells(): BelongsToMany
    {
        return $this->belongsToMany(Cell::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'is_enabled' => 'boolean',
            'starting_score' => 'integer',
            'password' => 'hashed',
        ];
    }
}
