<?php

namespace Database\Factories;

use App\Models\Cell;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Cell> */
class CellFactory extends Factory
{
    public function definition(): array
    {
        return [
            'text' => fake()->unique()->sentence(),
            'difficulty' => fake()->numberBetween(1, 3),
            'is_active' => true,
            'special_date' => null,
            'excluded_weekdays' => [],
        ];
    }
}
