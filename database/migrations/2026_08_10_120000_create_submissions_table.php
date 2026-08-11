<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            // Apagar a equipe apaga a submissao junto -- .claude/rules/database.md.
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();

            $table->string('title', 120)->nullable();
            $table->string('summary', 300)->nullable();
            $table->text('description')->nullable();

            $table->string('repo_url')->nullable();
            $table->string('video_url')->nullable();
            $table->string('deploy_url')->nullable();

            $table->string('status')->default('draft');
            $table->timestampTz('submitted_at')->nullable();

            // Zero enquanto e rascunho: versao so existe a partir do 1o envio.
            $table->unsignedInteger('current_version')->default(0);

            // Por onde a submissao entrou. Tudo que nao e 'web' fica marcado
            // no painel do organizador ate ser conferido -- PLANO.md, Anexo A.
            $table->string('source')->default('web');

            // Organizador que lancou a submissao no lugar da equipe (degraus 3
            // e 4 do plano B). Nota e registro historico: apagar o usuario nao
            // pode apagar a submissao junto.
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();

            // Horario real comprovado quando o envio nao passou pelo sistema
            // (e-mail, papel, formulario externo).
            $table->timestampTz('original_submitted_at')->nullable();

            $table->timestampsTz();

            // Organizador que apaga por engano as 23h precisa de desfazer.
            $table->softDeletesTz();

            $table->index('event_id');
            $table->index('status');
            $table->index(['event_id', 'status']);
        });

        // Uma submissao por equipe. Indice parcial porque submissao apagada
        // com soft delete nao pode impedir a equipe de comecar de novo --
        // mesmo padrao de team_invites_one_pending_per_team_email.
        DB::statement(
            'CREATE UNIQUE INDEX submissions_one_per_team
             ON submissions (team_id)
             WHERE deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
