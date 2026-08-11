<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A primeira tela que qualquer visitante vê. Sem autenticação, sem Policy --
 * o que muda por evento é só a janela de inscrição, calculada no servidor
 * como o resto do sistema (.claude/rules/security.md: prazo nunca é decidido
 * pelo relógio do cliente).
 */
class LandingController extends Controller
{
    public function show(): Response
    {
        $event = Event::current();

        return Inertia::render('publico/inicio', [
            'evento' => $event ? [
                'nome' => $event->name,
                'descricao' => $event->description,
                'edicao' => $event->edition,
                'situacao' => $event->status->value,
                'situacao_label' => $event->status->label(),
                'inicia_em' => $event->starts_at?->toIso8601String(),
                'termina_em' => $event->ends_at?->toIso8601String(),
                'inscricoes_abrem_em' => $event->registration_opens_at?->toIso8601String(),
                'inscricoes_fecham_em' => $event->registration_closes_at?->toIso8601String(),
                'inscricoes_abertas' => $event->registrationIsOpen(),
            ] : null,
        ]);
    }
}
