<?php

namespace App\Console\Commands;

use App\Actions\Users\StripIncompatibleParticipanteRole;
use Illuminate\Console\Command;

class FixParticipantRoleConflictsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hackathon:fix-participant-role-conflicts
        {--force : Aplica a remoção -- sem esta flag só mostra o relatório}';

    /**
     * @var string
     */
    protected $description = 'Remove participante de quem também tem jurado/organizador/admin (nunca apaga inscrição/equipe/submissão/avaliação/certificado)';

    public function handle(StripIncompatibleParticipanteRole $action): int
    {
        $conflitos = $action->find();

        if ($conflitos->isEmpty()) {
            $this->info('Nenhum usuário com participante + papel privilegiado. Nada a fazer.');

            return self::SUCCESS;
        }

        $this->warn("Encontrados {$conflitos->count()} usuário(s) com participante + papel privilegiado:");
        foreach ($conflitos as $user) {
            $this->line("  - {$user->name} <{$user->email}> ({$user->getRoleNames()->implode(', ')})");
        }

        if (! $this->option('force')) {
            $this->comment('Simulação (dry-run). Rode de novo com --force para remover participante desses usuários.');

            return self::SUCCESS;
        }

        foreach ($conflitos as $user) {
            $action->handle($user);
        }

        $this->info('Papel participante removido. Inscrição, equipe, submissão, avaliação e certificado não foram tocados.');

        return self::SUCCESS;
    }
}
