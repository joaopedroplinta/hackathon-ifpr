<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();

            // Apagar o evento apaga o resultado materializado junto -- não é
            // registro histórico independente, é derivado das avaliações.
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();

            // decimal(5,2), nunca float -- .claude/rules/database.md. Nulo
            // quando nenhum jurado submeteu: ausência de nota, não zero.
            $table->decimal('final_score', 5, 2)->nullable();
            $table->json('criteria_breakdown')->nullable();

            $table->unsignedInteger('rank_overall')->nullable();
            $table->unsignedInteger('rank_track')->nullable();
            $table->unsignedInteger('popular_votes_count')->default(0);

            $table->timestampTz('computed_at');
            $table->timestampsTz();

            $table->unique(['event_id', 'submission_id']);
            $table->index('event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
