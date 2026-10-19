<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\UseEdgeClientIp;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Global, not in the web group: redirects and 404s need the headers too.
        $middleware->append(SecurityHeaders::class);

        // Sem isto uma sessão roubada sobrevive à troca/reset de senha: o
        // middleware compara o hash da senha guardado na sessão com o atual.
        $middleware->web(append: [
            AuthenticateSession::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // A Render (e qualquer PaaS atrás de proxy reverso) termina o HTTPS na
        // borda e encaminha HTTP puro pro container com X-Forwarded-Proto.
        // Sem confiar nesse header, o Laravel gera link de asset e URL
        // absoluta em http://, causando mixed content no navegador. O
        // container só é alcançável através do proxy da plataforma, então
        // confiar em qualquer IP aqui é seguro.
        //
        // X-Forwarded-For fica de fora: com '*' o Symfony devolveria o IP do
        // proxy como cliente (ver UseEdgeClientIp, que lê o X-Real-IP).
        $middleware->prepend(UseEdgeClientIp::class);
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Sem isto o participante que erra a URL cai na página padrão do
        // Laravel, em inglês -- .claude/rules/frontend.md exige português em
        // tudo que o usuário lê, e "This action is unauthorized." é o tipo de
        // string técnica que a regra proíbe.
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if ($response->getStatusCode() === 419) {
                return back()->with('erro', 'Sua sessão expirou. Tente novamente.');
            }

            if (in_array($response->getStatusCode(), [403, 404, 500, 503], true)) {
                return Inertia::render('errors/erro', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });
    })->create();
