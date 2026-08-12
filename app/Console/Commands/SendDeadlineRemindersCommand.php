<?php

namespace App\Console\Commands;

use App\Actions\Notifications\SendDeadlineReminders;
use App\Models\Event;
use Illuminate\Console\Command;

/**
 * Sem argumento de evento, ao contrário de compute-results/issue-certificates:
 * este roda sozinho, agendado (routes/console.php), então não tem como
 * receber um slug de fora -- percorre todo evento com prazo definido, e o
 * flag reminder_Xh_sent_at em cada um evita reenvio.
 */
class SendDeadlineRemindersCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hackathon:send-deadline-reminders';

    /**
     * @var string
     */
    protected $description = 'Envia lembrete por e-mail às equipes sem submissão quando faltam 24h e 1h para o prazo';

    public function handle(SendDeadlineReminders $sendReminders): int
    {
        $eventos = Event::query()->whereNotNull('submission_deadline')->get();

        foreach ($eventos as $event) {
            $enviados = $sendReminders->handle($event);

            if ($enviados['enviados_24h'] > 0 || $enviados['enviados_1h'] > 0) {
                $this->info("\"{$event->name}\": {$enviados['enviados_24h']} lembrete(s) de 24h, {$enviados['enviados_1h']} de 1h.");
            }
        }

        return self::SUCCESS;
    }
}
