<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->string('token', 64)->unique();

            // Quem convidou e registro historico do convite: apagar o
            // usuario nao pode apagar o convite junto -- mesma razao do
            // leader_id em teams (.claude/rules/database.md).
            $table->foreignId('invited_by')->constrained('users')->restrictOnDelete();

            $table->timestampTz('expires_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->timestampsTz();

            $table->index(['team_id', 'email']);
            $table->index('event_id');
        });

        // Nao deixa dois convites pendentes pro mesmo e-mail na mesma
        // equipe. Indice parcial (accepted_at IS NULL) segue o mesmo padrao
        // de team_members_one_active_per_event: convite ja aceito nao conta
        // mais como pendente, entao nao trava um reenvio depois de outro
        // convite antigo ter sido aceito e a pessoa ter saido.
        DB::statement(
            'CREATE UNIQUE INDEX team_invites_one_pending_per_team_email
             ON team_invites (team_id, email)
             WHERE accepted_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invites');
    }
};
