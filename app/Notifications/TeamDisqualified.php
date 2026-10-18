<?php

namespace App\Notifications;

use App\Models\Team;
use App\Support\MarkdownText;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Avisa cada integrante ativo que a equipe saiu da disputa, com o motivo registrado. */
class TeamDisqualified extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Team $team, public string $motivo) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Sua equipe {$this->team->name} foi desclassificada")
            ->greeting('Olá!')
            ->line("A organização do {$this->team->event->name} desclassificou a equipe \"".MarkdownText::escape($this->team->name).'".')
            ->line("Motivo registrado: {$this->motivo}")
            ->line('Com isso, a equipe sai da avaliação, do ranking e da votação e não pode mais enviar o projeto.')
            ->line('Se você discorda da decisão, procure a organização do evento.');
    }
}
