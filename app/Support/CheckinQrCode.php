<?php

namespace App\Support;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * O crachá digital do check-in. SVG puro -- PLANO.md escolheu
 * bacon/bacon-qr-code exatamente por isto, sem depender de imagick.
 */
class CheckinQrCode
{
    /**
     * A URL é fixa por pessoa: o mesmo QR vale pra entrada, pras oficinas,
     * pra qualquer checkpoint do evento -- quem decide qual checkpoint é a
     * tela de confirmação do organizador, não o código em si.
     */
    public function svgFor(User $user): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle(240),
            new SvgImageBackEnd
        );

        $writer = new Writer($renderer);

        return $writer->writeString(url("/checkin/{$user->qr_token}"));
    }
}
