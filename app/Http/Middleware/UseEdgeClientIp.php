<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Atrás do proxy da Railway, `trustProxies('*')` faz o Symfony confiar em
 * todos os IPs da cadeia e cair no REMOTE_ADDR, que é o IP do edge: todo
 * mundo "vinha" de dois endereços e o throttle de login/convite/cadastro
 * não distinguia ninguém. A Railway define X-Real-IP com o IP do cliente
 * (docs.railway.com/networking/public-networking/specs-and-limits); aqui ele
 * vira o REMOTE_ADDR, que é o que `$request->ip()` e o throttle leem.
 *
 * Roda ANTES do TrustProxies. X-Forwarded-For fica fora dos cabeçalhos
 * confiáveis (bootstrap/app.php): com ele, um valor forjado pelo cliente
 * poderia ser escolhido como IP. Sem X-Real-IP (dev local, testes) nada muda.
 */
class UseEdgeClientIp
{
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->headers->get('X-Real-IP');

        if ($ip !== null && filter_var($ip, FILTER_VALIDATE_IP)) {
            $request->server->set('REMOTE_ADDR', $ip);
        }

        return $next($request);
    }
}
