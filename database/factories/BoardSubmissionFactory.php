<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\BoardSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BoardSubmission> */
class BoardSubmissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'lines' => [0],
            'status' => BoardSubmission::PENDING,
            'submitted_at' => now(),
        ];
    }

    public function approved(float $points = 1.0): static
    {
        return $this->state(fn () => [
            'status' => BoardSubmission::APPROVED,
            'reviewed_at' => now(),
            'points' => $points,
            'base_points' => $points,
            'multiplier' => 1,
        ]);
    }

    public function rejected(?string $note = null): static
    {
        return $this->state(fn () => [
            'status' => BoardSubmission::REJECTED,
            'reviewed_at' => now(),
            'review_note' => $note,
        ]);
    }
}
