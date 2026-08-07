<?php

namespace App\Http\Middleware;

use App\Models\Event;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $event = Event::current();

        // parent::share era chamado duas vezes no starter kit (array_merge
        // mais spread do mesmo resultado). Uma basta.
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => [
                'user' => $request->user(),
            ],
            // Evento em foco: usado pela navegação e pelo dashboard em toda
            // tela autenticada, então vive aqui e não em cada controller.
            'evento' => $event ? [
                'nome' => $event->name,
                'slug' => $event->slug,
                'inscricoes_abertas' => $event->registrationIsOpen(),
                'inscrito' => $event->isRegistered($request->user()),
            ] : null,
            'flash' => [
                'sucesso' => $request->session()->get('sucesso'),
                'erro' => $request->session()->get('erro'),
            ],
        ];
    }
}
