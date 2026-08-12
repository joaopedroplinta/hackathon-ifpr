<?php

namespace App\Console\Commands;

use App\Actions\Certificates\IssueEventCertificates;
use App\Models\Event;
use Illuminate\Console\Command;

class IssueCertificatesCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hackathon:issue-certificates {event : Slug do evento}';

    /**
     * @var string
     */
    protected $description = 'Emite certificados de participação, jurado, organizador e colocação de um evento';

    public function handle(): int
    {
        $slug = $this->argument('event');
        $event = Event::where('slug', $slug)->first();

        if (! $event) {
            $this->error("Nenhum evento encontrado com o slug \"{$slug}\".");

            return self::FAILURE;
        }

        $emitidos = app(IssueEventCertificates::class)->handle($event);

        $this->info("Certificados emitidos para \"{$event->name}\":");
        $this->line("  Participação: {$emitidos['participacao']}");
        $this->line("  Jurado: {$emitidos['jurado']}");
        $this->line("  Organizador: {$emitidos['organizador']}");
        $this->line("  Colocação: {$emitidos['colocacao']}");

        if (! $event->resultsArePublished()) {
            $this->comment('Resultado ainda não publicado -- colocação fica pra quando publicar.');
        }

        return self::SUCCESS;
    }
}
