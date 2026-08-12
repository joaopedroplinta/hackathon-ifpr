<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            // Certificado é registro histórico -- apagar o usuário não pode
            // apagar a prova de que ele participou/avaliou/organizou
            // (.claude/rules/database.md, mesmo padrão de evaluations).
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->string('type');
            $table->uuid('code')->unique();
            $table->json('payload')->nullable();

            // Nulo enquanto o Job de PDF não terminou -- fila, não síncrono.
            $table->string('path')->nullable();

            $table->timestampTz('issued_at');
            $table->timestampsTz();

            // Um certificado de cada tipo por pessoa por evento. Reemissão
            // avulsa (task /admin/certificados) apaga o anterior antes de
            // criar outro, em vez de burlar isto.
            $table->unique(['event_id', 'user_id', 'type']);
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
