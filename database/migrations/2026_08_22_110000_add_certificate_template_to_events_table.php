<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Ambos nulos por padrão: sem preenchido, o certificado sai com
            // a cor e o layout padrão do template (issue #122).
            $table->string('certificate_logo_path')->nullable();
            $table->string('certificate_accent_color', 7)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['certificate_logo_path', 'certificate_accent_color']);
        });
    }
};
