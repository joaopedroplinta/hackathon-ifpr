<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Support\CheckinQrCode;
use Inertia\Inertia;
use Inertia\Response;

/**
 * O crachá digital de quem está logado -- participante, jurado ou
 * organizador, qualquer um que precise passar por um checkpoint no dia do
 * evento. Sem Policy: cada um só pode pedir o próprio QR, nunca o de outra
 * pessoa, porque a rota não recebe id nenhum -- é sempre o usuário
 * autenticado.
 */
class CredentialController extends Controller
{
    public function show(CheckinQrCode $qr): Response
    {
        $user = request()->user();

        return Inertia::render('credencial/mostrar', [
            'nome' => $user->name,
            'qr_svg' => $qr->svgFor($user),
            'token' => $user->qr_token,
        ]);
    }
}
