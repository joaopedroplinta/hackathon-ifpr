<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Marca que a fila de lembrete já disparou pra este limiar --
            // sem isto, rodar o comando agendado de novo manda o e-mail de
            // novo pra quem já recebeu.
            $table->timestampTz('reminder_24h_sent_at')->nullable();
            $table->timestampTz('reminder_1h_sent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['reminder_24h_sent_at', 'reminder_1h_sent_at']);
        });
    }
};
