<?php

namespace App\Notifications;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Avisa cada integrante ativo que a desclassificação foi desfeita, com o motivo registrado. */
class TeamReinstated extends Notification implements ShouldQueue
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
            ->subject("Sua equipe {$this->team->name} foi reabilitada")
            ->greeting('Boa notícia!')
            ->line("A organização do {$this->team->event->name} desfez a desclassificação da equipe \"{$this->team->name}\".")
            ->line("Motivo registrado: {$this->motivo}")
            ->line('A equipe volta à avaliação, ao ranking e à votação.')
            ->action('Ver minha equipe', route('teams.show'));
    }
}
