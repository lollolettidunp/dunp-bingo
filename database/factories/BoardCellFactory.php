<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\BoardCell;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BoardCell> */
class BoardCellFactory extends Factory
{
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'position' => fake()->unique()->numberBetween(0, 24),
            'text' => fake()->sentence(),
            'difficulty' => 2,
            'marked_at' => null,
        ];
    }
}
