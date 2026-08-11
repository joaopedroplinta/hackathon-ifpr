<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // "N jurados por submissão, configurável no evento (padrão 3)" --
            // regras-avaliacao. Sem tela de configurações de evento ainda,
            // então este campo é editado direto em /admin/jurados.
            $table->unsignedTinyInteger('judges_per_submission')->default(3)->after('max_team_size');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('judges_per_submission');
        });
    }
};
