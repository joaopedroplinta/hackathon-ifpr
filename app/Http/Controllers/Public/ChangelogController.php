<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\AppVersion;
use App\Support\Changelog;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Notas de versão lidas do CHANGELOG.md -- sem Policy, sem dado de evento,
 * sem entrada do usuário. O repositório já é público.
 */
class ChangelogController extends Controller
{
    public function index(Changelog $changelog): Response
    {
        return Inertia::render('publico/versoes', [
            'versoes' => $changelog->fromFile(),
            'atual' => (new AppVersion)->numero(),
        ]);
    }
}
