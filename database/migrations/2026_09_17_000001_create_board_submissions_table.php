<?php

use App\Models\Board;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A board is no longer worth a single point: every bingo line is sent in on its
 * own, scored on its own submit time, and reviewed on its own. The review state
 * therefore moves off `boards` and onto one row per claim.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->json('lines');
            $table->string('status')->default('pending')->index();
            $table->timestamp('submitted_at');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->decimal('points', 6, 2)->nullable();
            $table->decimal('base_points', 6, 2)->nullable();
            $table->decimal('multiplier', 4, 2)->nullable();
            $table->timestamps();
        });

        $this->migrateExistingBoards();

        Schema::table('users', function (Blueprint $table) {
            $table->decimal('starting_score', 8, 2)->default(0)->change();
        });

        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn(['status', 'submitted_at', 'reviewed_at', 'review_note']);
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->string('status')->default('playing');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
        });

        DB::table('board_submissions')->orderBy('id')->each(function ($submission) {
            DB::table('boards')->where('id', $submission->board_id)->update([
                'status' => $submission->status,
                'submitted_at' => $submission->submitted_at,
                'reviewed_at' => $submission->reviewed_at,
                'review_note' => $submission->review_note,
            ]);
        });

        Schema::dropIfExists('board_submissions');

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('starting_score')->default(0)->change();
        });
    }

    /**
     * Every board already reviewed becomes one submission holding the lines it
     * had won. Old bingos keep their historical 1 point: the new multipliers
     * are not applied backwards, or today's migration would rewrite the past.
     */
    private function migrateExistingBoards(): void
    {
        $now = now();

        DB::table('boards')->where('status', '!=', 'playing')->orderBy('id')->each(function ($board) use ($now) {
            $marked = DB::table('board_cells')
                ->where('board_id', $board->id)
                ->whereNotNull('marked_at')
                ->pluck('position')
                ->all();

            $lines = collect(Board::LINES)
                ->filter(fn (array $line) => count(array_intersect($line, $marked)) === 5)
                ->keys()
                ->all();

            $approved = $board->status === 'approved';

            DB::table('board_submissions')->insert([
                'board_id' => $board->id,
                'lines' => json_encode($lines),
                'status' => $board->status,
                'submitted_at' => $board->submitted_at ?? $board->created_at ?? $now,
                'reviewed_at' => $board->reviewed_at,
                'review_note' => $board->review_note,
                'points' => $approved ? 1 : null,
                'base_points' => $approved ? 1 : null,
                'multiplier' => $approved ? 1 : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }
};
