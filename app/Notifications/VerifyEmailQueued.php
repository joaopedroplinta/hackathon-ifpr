<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * A notificação padrão do Laravel (`Illuminate\Auth\Notifications\VerifyEmail`)
 * não é `ShouldQueue` -- ela mandava o e-mail na hora, dentro do próprio
 * request de cadastro. Se o Resend falhar por qualquer motivo (fora do ar,
 * chave errada, domínio de teste rejeitado), a exceção subia até o
 * controller e o cadastro inteiro quebrava com 500 -- mesmo com o usuário
 * já criado no banco.
 *
 * E-mail sempre passa pela fila neste projeto (ver TeamInviteNotification):
 * ninguém espera o SMTP durante o request, e uma falha de envio vira falha
 * de job, não falha de cadastro.
 */
class VerifyEmailQueued extends VerifyEmailBase implements ShouldQueue
{
    use Queueable;

    /**
     * O texto padrão do Illuminate vem de `Lang::get('Verify Email Address')`
     * e afins -- chaves do próprio framework, sem tradução pt_BR embutida.
     * Escrever a MailMessage direto, como as outras notificações do projeto
     * (ver TeamInviteNotification), evita depender de string traduzida por
     * fora e dá controle total sobre o texto em português.
     */
    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('Confirme seu e-mail — Hackathon IFPR')
            ->greeting('Olá!')
            ->line('Clique no botão abaixo para confirmar seu endereço de e-mail e ativar sua conta.')
            ->action('Confirmar e-mail', $url)
            ->line('O link expira em 60 minutos.')
            ->line('Se você não criou uma conta no Hackathon IFPR, pode ignorar este e-mail.');
    }
}
