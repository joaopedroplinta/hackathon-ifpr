<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Notifications\VerifyEmailQueued;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerifyEmailQueuedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regressão: a notificação padrão do Laravel não é ShouldQueue, e sem
     * isso o e-mail de verificação era enviado dentro do próprio request de
     * cadastro -- uma falha do Resend (fora do ar, chave errada, domínio
     * rejeitado) derrubava o cadastro inteiro com 500, mesmo com o usuário
     * já criado no banco.
     */
    public function test_it_is_dispatched_through_the_queue(): void
    {
        $this->assertInstanceOf(ShouldQueue::class, new VerifyEmailQueued);
    }

    /**
     * Regressão: a MailMessage padrão do Illuminate monta o e-mail com
     * strings do próprio framework (`Lang::get('Verify Email Address')`
     * etc.), sem tradução pt_BR embutida -- o e-mail chegava com assunto e
     * corpo em inglês misturado com o restante em português.
     */
    public function test_mail_message_is_entirely_in_portuguese(): void
    {
        $user = User::factory()->create();

        $mail = (new VerifyEmailQueued)->toMail($user);

        $this->assertSame('Confirme seu e-mail — Hackathon IFPR', $mail->subject);
        $this->assertSame('Confirmar e-mail', $mail->actionText);
        $this->assertStringNotContainsString('Verify Email', implode(' ', $mail->introLines));
        $this->assertStringNotContainsString('verify your email', implode(' ', $mail->introLines));
    }
}
