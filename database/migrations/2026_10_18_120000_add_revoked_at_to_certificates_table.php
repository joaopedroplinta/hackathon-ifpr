<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            // Nulo = válido. Desclassificar a equipe revoga o certificado de
            // colocação já emitido; a linha continua como registro histórico.
            $table->timestampTz('revoked_at')->nullable()->after('issued_at');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('revoked_at');
        });
    }
};
