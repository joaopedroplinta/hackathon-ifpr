<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained()->cascadeOnDelete();

            // Em qual envio este arquivo passou a contar. Serve de auditoria:
            // o retrato do que foi enviado vive no payload da versao.
            $table->unsignedInteger('version');

            // Caminho no disco, gerado pelo sistema. O nome original nunca
            // entra aqui -- .claude/rules/security.md.
            $table->string('path');

            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');

            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();

            $table->timestampsTz();

            // Arquivo removido pela equipe some da tela, mas continua no
            // registro: se o resultado for contestado, o que foi enviado e
            // quando precisa ser reconstituivel.
            $table->softDeletesTz();

            $table->index('submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_files');
    }
};
