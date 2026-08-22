<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Praticamente toda tela do organizador pressupõe que já existe uma edição
 * do hackathon em cartaz (ver ResolvesParticipation::currentEventOrFail()).
 * Sem isto, a rota lançava 404 cru -- estado vazio precisa dizer qual é o
 * próximo passo, não devolver uma tela de erro muda
 * (.claude/rules/frontend.md).
 *
 * Só intercepta quem já é staff: a autorização de cada tela continua sendo
 * feita pela Policy dentro do controller (`$this->authorize(...)`), que
 * roda depois desta middleware -- rodar a checagem de evento antes da
 * autorização vazaria "não existe evento" pra quem nem deveria acessar a
 * rota. Sem evento, um participante comum ainda cai no 403 normal.
 */
class EnsureEventExists
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isStaff() || Event::current()) {
            return $next($request);
        }

        return Inertia::render('admin/sem-evento')->toResponse($request);
    }
}
