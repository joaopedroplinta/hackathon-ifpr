<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            // Inscricao e registro historico: apagar usuario nao apaga a
            // inscricao em silencio -- ver .claude/rules/database.md.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->timestampTz('registered_at');
            $table->string('shirt_size')->nullable();
            $table->text('dietary_notes')->nullable();
            $table->string('phone')->nullable();
            $table->string('course')->nullable();
            $table->timestampsTz();

            // Uma inscricao por pessoa por evento, garantido pelo banco.
            // Validacao so na aplicacao perde a corrida com duplo clique.
            $table->unique(['event_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
