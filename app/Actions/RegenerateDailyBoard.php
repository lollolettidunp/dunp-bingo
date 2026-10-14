<?php

namespace App\Actions;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class RegenerateDailyBoard
{
    /**
     * Throws the day's board away, marks and claims included. Nothing is dealt
     * here: the player finds a sealed pack again and deals the new board by opening it.
     */
    public function __invoke(User $user, CarbonImmutable $date): bool
    {
        $deleted = $user->boards()->whereDate('played_on', $date)->delete() > 0;

        if ($deleted) {
            Cache::put(self::noticeKey($user->id), $date->toDateString(), $date->endOfDay());
        }

        return $deleted;
    }

    /** Tells the player why the pack is back; cleared once they open it. */
    public static function noticeKey(int $userId): string
    {
        return "board-regenerated:{$userId}";
    }
}
