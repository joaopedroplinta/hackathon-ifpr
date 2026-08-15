<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Página estática -- sem Policy, sem dado de evento. LGPD exige acesso
 * fácil e sem login (issue #78 / achado ao revisar #71).
 */
class PrivacyPolicyController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('publico/privacidade');
    }
}
