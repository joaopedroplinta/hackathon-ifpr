<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conflicts_of_interest', function (Blueprint $table) {
            $table->id();

            $table->foreignId('judge_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->text('reason')->nullable();

            $table->timestampsTz();

            $table->index('judge_id');
            $table->index('team_id');

            // Um conflito registrado por par jurado/equipe já basta pra
            // bloquear a atribuição pra sempre.
            $table->unique(['judge_id', 'team_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conflicts_of_interest');
    }
};
