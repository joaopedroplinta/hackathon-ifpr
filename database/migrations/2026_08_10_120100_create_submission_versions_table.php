<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');

            // Retrato dos campos no momento do envio. Nada aqui e sobrescrito:
            // e o que prova o que a equipe mandou e quando -- PLANO.md secao 4.
            $table->jsonb('payload');

            // Quem enviou esta versao. Registro historico: apagar o usuario
            // nao pode apagar a prova do envio.
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();

            $table->timestampsTz();

            $table->unique(['submission_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_versions');
    }
};
