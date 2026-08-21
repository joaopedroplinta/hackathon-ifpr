<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as VerifyEmailBase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

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
}
