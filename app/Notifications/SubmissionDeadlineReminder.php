<?php

namespace App\Notifications;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail e PDF sempre em fila neste projeto (PLANO.md) -- ninguém espera o
 * SMTP durante o comando agendado.
 */
class SubmissionDeadlineReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Team $team, public int $horasRestantes)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $prazo = $this->team->event->submission_deadline->timezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i');
        $urgente = $this->horasRestantes <= 1;

        return (new MailMessage)
            ->subject($urgente
                ? "Falta 1 hora para o prazo da equipe {$this->team->name}"
                : "Faltam 24 horas para o prazo da equipe {$this->team->name}")
            ->greeting('Olá!')
            ->line("A equipe \"{$this->team->name}\" ainda não enviou o projeto no {$this->team->event->name}.")
            ->line("O prazo de submissão termina em {$prazo}.")
            ->action('Enviar projeto', route('submissions.show'))
            ->line('Se o projeto já foi enviado, pode ignorar este e-mail.');
    }
}
