<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            $table->string('name', 120);

            // Só uma rubrica ativa por evento -- é ela que o jurado vê e que
            // conta pro cálculo. As outras são rascunho ou edição anterior.
            $table->boolean('is_active')->default(false);

            $table->timestampsTz();

            $table->index('event_id');
            $table->index(['event_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rubrics');
    }
};
