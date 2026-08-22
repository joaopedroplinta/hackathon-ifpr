<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Ambos nulos por padrão: sem isso preenchido, o certificado
            // sai sem bloco de assinatura -- nunca inventa um nome.
            $table->string('certificate_signer_name')->nullable();
            $table->string('certificate_signer_role')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['certificate_signer_name', 'certificate_signer_role']);
        });
    }
};
