<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained()->cascadeOnDelete();

            $table->string('name', 120);
            $table->text('description')->nullable();

            // decimal, nunca float -- diferença de arredondamento entre
            // jurados quebra a conta na frente da equipe que perdeu
            // (.claude/rules/database.md).
            $table->decimal('weight', 5, 2);
            $table->unsignedSmallInteger('max_score');

            // Ordem de exibição na tela do jurado e na rubrica pública.
            $table->unsignedSmallInteger('position')->default(0);

            $table->timestampsTz();

            $table->index('rubric_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criteria');
    }
};
