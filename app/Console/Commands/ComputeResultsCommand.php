<?php

namespace App\Console\Commands;

use App\Actions\Results\ComputeResults;
use App\Models\Event;
use App\Models\Result;
use Illuminate\Console\Command;

class ComputeResultsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hackathon:compute-results {event : Slug do evento}';

    /**
     * @var string
     */
    protected $description = 'Recalcula os resultados materializados de um evento (nota final, ranking geral e por trilha)';

    public function handle(): int
    {
        $slug = $this->argument('event');
        $event = Event::where('slug', $slug)->first();

        if (! $event) {
            $this->error("Nenhum evento encontrado com o slug \"{$slug}\".");

            return self::FAILURE;
        }

        app(ComputeResults::class)->handle($event);

        $total = Result::forEvent($event)->count();
        $this->info("Resultados recalculados para \"{$event->name}\": {$total} submissões processadas.");

        return self::SUCCESS;
    }
}
