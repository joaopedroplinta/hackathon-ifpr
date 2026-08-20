<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * `avatar_url` guarda tanto uma URL externa (foto do Google) quanto o
 * caminho do nosso disco `public` (upload manual) -- só apaga do disco
 * quando é claramente nosso, nunca tenta mexer numa URL de fora.
 */
class AvatarStorage
{
    public static function caminhoLocal(?string $avatarUrl): ?string
    {
        $prefixo = Storage::disk('public')->url('');

        if (! $avatarUrl || ! str_starts_with($avatarUrl, $prefixo)) {
            return null;
        }

        return str_replace($prefixo, '', $avatarUrl);
    }

    public static function apagarSeLocal(?string $avatarUrl): void
    {
        $caminho = self::caminhoLocal($avatarUrl);

        if ($caminho) {
            Storage::disk('public')->delete($caminho);
        }
    }
}
