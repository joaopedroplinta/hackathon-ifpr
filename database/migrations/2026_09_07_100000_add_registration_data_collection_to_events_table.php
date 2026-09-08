<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Cada edição escolhe o que realmente precisa coletar. Mantemos
            // desligado por padrão para não pedir dado sem finalidade.
            $table->boolean('collect_shirt_size')->default(false)->after('max_team_size');
            $table->boolean('collect_dietary_notes')->default(false)->after('collect_shirt_size');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['collect_shirt_size', 'collect_dietary_notes']);
        });
    }
};
