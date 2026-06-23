<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Board> */
class BoardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'played_on' => today(config('app.timezone')),
            'status' => Board::PLAYING,
        ];
    }
}
