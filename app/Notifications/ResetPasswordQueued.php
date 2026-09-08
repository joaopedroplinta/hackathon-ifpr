<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordBase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Keeps password-reset delivery asynchronous and avoids Laravel's default
 * English message in an otherwise Portuguese user journey.
 */
class ResetPasswordQueued extends ResetPasswordBase implements ShouldQueue
{
    use Queueable;

    protected function buildMailMessage($url): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Redefina sua senha — Hackathon IFPR')
            ->greeting('Olá!')
            ->line('Recebemos uma solicitação para redefinir a senha da sua conta no Hackathon IFPR.')
            ->action('Redefinir senha', $url)
            ->line("Este link expira em {$minutes} minutos.")
            ->line('Se você não solicitou a redefinição, pode ignorar esta mensagem com segurança.')
            ->salutation("Até já,  \nEquipe Hackathon IFPR");
    }
}
