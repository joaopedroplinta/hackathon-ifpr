<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Página estática -- sem Policy, sem dado de evento. Existe só para o link
 * "Saiba quais" do aviso de cookies ter destino (issue #73).
 */
class CookiePolicyController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('publico/cookies');
    }
}
