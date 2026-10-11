<?php

namespace App\Console\Commands;

use App\Actions\Demo\PrepareDemoStage;
use Illuminate\Console\Command;
use Throwable;

class DemoCommand extends Command
{
    protected $signature = 'hackathon:demo
        {estagio : inicio, submissoes, avaliacao ou resultado}
        {--senha= : Senha das contas de demonstração (ou a variável DEMO_PASSWORD)}';

    protected $description = 'Prepara a edição de demonstração num estágio do fluxo (só homologação)';

    public function handle(PrepareDemoStage $demo): int
    {
        $senha = (string) ($this->option('senha') ?: config('hackathon.demo_password'));

        if (strlen($senha) < 12) {
            $this->error('Informe uma senha de pelo menos 12 caracteres: --senha=... ou a variável DEMO_PASSWORD.');

            return self::FAILURE;
        }

        try {
            $resumo = $demo->handle((string) $this->argument('estagio'), $senha);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("{$resumo['evento']}: estágio \"{$resumo['estagio']}\" pronto.");
        $this->line("Equipes: {$resumo['equipes']} · Submissões: {$resumo['submissoes']} · Avaliações enviadas: {$resumo['avaliacoes_enviadas']}");
        $this->table(['Papel', 'E-mail'], collect($resumo['contas'])->map(fn ($email, $papel) => [$papel, $email])->values()->all());

        return self::SUCCESS;
    }
}
