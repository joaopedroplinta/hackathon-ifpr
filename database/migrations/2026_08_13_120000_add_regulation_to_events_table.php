<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Gerado pelo Laravel no disco `local`, nunca a partir do nome
            // original -- .claude/rules/security.md.
            $table->string('regulation_path')->nullable();
            // Só metadado, exibido na tela. Nunca usado no caminho do disco.
            $table->string('regulation_original_name')->nullable();
            $table->timestampTz('regulation_updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['regulation_path', 'regulation_original_name', 'regulation_updated_at']);
        });
    }
};
