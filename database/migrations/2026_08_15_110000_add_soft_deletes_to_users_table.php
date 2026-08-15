<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sem isso, excluir a própria conta quebra com violação de foreign key
     * pra qualquer jurado, líder de equipe ou dono de certificado -- essas
     * tabelas usam restrictOnDelete() de propósito, pra manter o registro
     * histórico (.claude/rules/database.md). Soft delete resolve sem abrir
     * mão da integridade: a linha continua existindo pra quem referencia
     * user_id, só some das consultas normais.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletesTz();
        });
    }
};
