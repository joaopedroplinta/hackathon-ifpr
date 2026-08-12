<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->timestampTz('started_at');
            $table->timestampTz('ended_at')->nullable();
            $table->text('description');

            // Extensão vale pra TODAS as equipes -- Event::effectiveSubmissionDeadline()
            // soma isto de todo incidente do evento, nunca por equipe
            // (PLANO.md, Anexo A.3 e .claude/rules/security.md).
            $table->unsignedInteger('deadline_extension_minutes')->default(0);

            // Registro histórico -- apagar quem declarou não pode apagar o
            // motivo de um prazo estendido (.claude/rules/database.md).
            $table->foreignId('declared_by')->constrained('users')->restrictOnDelete();

            $table->timestampsTz();

            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
