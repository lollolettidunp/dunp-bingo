<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'google_id', 'avatar_url', 'is_enabled', 'is_present', 'starting_score', 'patch_notes_seen'];

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    /** Every bingo claim the player ever sent in — what the leaderboard sums. */
    public function submissions(): HasManyThrough
    {
        return $this->hasManyThrough(BoardSubmission::class, Board::class);
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
            'is_present' => 'boolean',
            'starting_score' => 'float',
            'password' => 'hashed',
        ];
    }
}
