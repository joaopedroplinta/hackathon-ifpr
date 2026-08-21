<?php

namespace Tests\Unit\Notifications;

use App\Notifications\VerifyEmailQueued;
use Illuminate\Contracts\Queue\ShouldQueue;
use Tests\TestCase;

class VerifyEmailQueuedTest extends TestCase
{
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
}
