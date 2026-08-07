<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();

            // event_id e redundante (da para chegar nele via team), mas e o
            // que permite garantir "uma equipe por pessoa por evento" no
            // banco, e nao so na aplicacao.
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            $table->string('role')->default('member');
            $table->string('status')->default('active');
            $table->timestampTz('joined_at');
            $table->timestampTz('left_at')->nullable();
            $table->timestampsTz();

            $table->unique(['team_id', 'user_id']);
            $table->index(['team_id', 'status']);
        });

        // Uma participacao ATIVA por pessoa por evento. Indice parcial do
        // Postgres: quem saiu de uma equipe pode entrar em outra, mas
        // ninguem fica ativo em duas ao mesmo tempo -- nem por corrida de
        // duplo clique, que a checagem da aplicacao nao pega.
        DB::statement(
            'CREATE UNIQUE INDEX team_members_one_active_per_event
             ON team_members (event_id, user_id)
             WHERE status = \'active\''
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
