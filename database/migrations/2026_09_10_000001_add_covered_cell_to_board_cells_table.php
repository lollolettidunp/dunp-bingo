<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot of the ordinary cell hidden under a daily sticker, so the sticker
     * can be moved elsewhere and the original cell restored.
     */
    public function up(): void
    {
        Schema::table('board_cells', function (Blueprint $table) {
            $table->foreignId('covered_cell_id')->nullable()->after('marked_at')->constrained('cells')->nullOnDelete();
            $table->text('covered_text')->nullable()->after('covered_cell_id');
            $table->unsignedTinyInteger('covered_difficulty')->nullable()->after('covered_text');
            $table->timestamp('covered_marked_at')->nullable()->after('covered_difficulty');
        });
    }

    public function down(): void
    {
        Schema::table('board_cells', function (Blueprint $table) {
            $table->dropConstrainedForeignId('covered_cell_id');
            $table->dropColumn(['covered_text', 'covered_difficulty', 'covered_marked_at']);
        });
    }
};
