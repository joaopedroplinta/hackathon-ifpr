<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();

            // Presença é comprovante de carga horária pro certificado --
            // registro histórico, igual submission.recorded_by. Apagar um
            // checkpoint ou usuário que já tem presença registrada é
            // bloqueado, não some com a prova (.claude/rules/database.md).
            $table->foreignId('checkpoint_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->timestampTz('checked_in_at');

            // Organizador que confirmou. Nulo só no lançamento manual em
            // lote (plano B) -- ver PLANO.md, Anexo A.
            $table->foreignId('checked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('method')->default('manual');

            $table->timestampsTz();

            $table->index('checkpoint_id');

            // Um check-in por pessoa por checkpoint -- reler o QR duas vezes
            // não pode duplicar a presença.
            $table->unique(['checkpoint_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
