<?php

namespace App\Notifications;

use App\Models\TeamInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Enviado em fila -- ninguém espera o SMTP durante o request que grava o
 * convite. E-mail e PDF sempre passam pela fila neste projeto (PLANO.md,
 * comandos: `php artisan queue:work`).
 *
 * Enviado por Notification::route('mail', $email), não por Notifiable::
 * o convidado muitas vezes ainda não tem conta, então não há User pra
 * notificar.
 */
class TeamInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TeamInvite $invite)
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
        $team = $this->invite->team;
        $url = route('team-invites.accept', $this->invite->token);

        return (new MailMessage)
            ->subject("Convite para a equipe {$team->name}")
            ->greeting('Olá!')
            ->line("Você foi convidado para participar da equipe \"{$team->name}\" no {$team->event->name}.")
            ->action('Aceitar convite', $url)
            ->line('O convite expira em '.$this->invite->expires_at->timezone('America/Sao_Paulo')->format('d/m/Y \à\s H:i').'.')
            ->line('Se você não esperava este e-mail, pode ignorá-lo.');
    }
}
