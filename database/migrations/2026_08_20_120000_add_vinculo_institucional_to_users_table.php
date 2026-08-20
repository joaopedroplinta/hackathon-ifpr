<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable: ninguém é obrigado a preencher isso só pra editar
            // nome/e-mail. Vira obrigatório só na hora que importa de
            // verdade -- emitir um certificado com validade legal.
            $table->string('cpf', 11)->nullable()->unique()->after('avatar_url');
            $table->string('tipo_vinculo')->nullable()->after('cpf');
            $table->string('matricula_suap')->nullable()->after('tipo_vinculo');
            $table->string('matricula_siape')->nullable()->after('matricula_suap');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cpf', 'tipo_vinculo', 'matricula_suap', 'matricula_siape']);
        });
    }
};
