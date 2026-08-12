<?php

namespace App\Console\Commands;

use App\Actions\Submissions\ImportSubmissionsFromCsv;
use App\Enums\SubmissionSource;
use App\Models\Event;
use Illuminate\Console\Command;

class ImportSubmissionsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hackathon:import-submissions
        {csv : Caminho do arquivo CSV}
        {event : Slug do evento}
        {--source=form : form, email ou manual -- ver App\Enums\SubmissionSource}';

    /**
     * @var string
     */
    protected $description = 'Importa submissões do formulário externo de emergência (plano B, Anexo A.2)';

    public function handle(ImportSubmissionsFromCsv $import): int
    {
        $slug = $this->argument('event');
        $event = Event::where('slug', $slug)->first();

        if (! $event) {
            $this->error("Nenhum evento encontrado com o slug \"{$slug}\".");

            return self::FAILURE;
        }

        $csv = $this->argument('csv');

        if (! is_string($csv) || ! is_file($csv)) {
            $this->error("Arquivo não encontrado: {$csv}");

            return self::FAILURE;
        }

        $source = SubmissionSource::tryFrom((string) $this->option('source'));

        if ($source === null || $source === SubmissionSource::Web) {
            $this->error('--source precisa ser form, email ou manual.');

            return self::FAILURE;
        }

        $resultado = $import->handle($event, $csv, $source);

        $this->info("Importadas: {$resultado['importadas']}");

        if ($resultado['conflitos'] !== []) {
            $this->warn('Conflito -- equipe já tinha submissão, nada foi sobrescrito:');
            foreach ($resultado['conflitos'] as $nome) {
                $this->line("  - {$nome}");
            }
        }

        if ($resultado['nao_encontrados'] !== []) {
            $this->warn('E-mail de líder não encontrado neste evento:');
            foreach ($resultado['nao_encontrados'] as $email) {
                $this->line("  - {$email}");
            }
        }

        return self::SUCCESS;
    }
}
