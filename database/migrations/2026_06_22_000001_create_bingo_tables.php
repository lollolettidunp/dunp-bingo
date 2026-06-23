<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cells', function (Blueprint $table) {
            $table->id();
            $table->text('text')->unique();
            $table->unsignedTinyInteger('difficulty')->default(2);
            $table->boolean('is_active')->default(true);
            $table->date('special_date')->nullable()->index();
            $table->json('excluded_weekdays')->nullable();
            $table->timestamps();
        });

        Schema::create('cell_user', function (Blueprint $table) {
            $table->foreignId('cell_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['cell_id', 'user_id']);
        });

        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('played_on');
            $table->string('status')->default('playing');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'played_on']);
        });

        Schema::create('board_cells', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cell_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('position');
            $table->text('text');
            $table->unsignedTinyInteger('difficulty');
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();
            $table->unique(['board_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_cells');
        Schema::dropIfExists('boards');
        Schema::dropIfExists('cell_user');
        Schema::dropIfExists('cells');
    }
};
