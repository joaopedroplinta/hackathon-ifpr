<?php

namespace App\Actions\Users;

use App\Models\User;

class MarkTourSeen
{
    /** Teto da coluna: o catálogo de tours é pequeno, isto só impede crescimento sem fim. */
    public const LIMIT = 200;

    /**
     * Idempotente. Passando do limite, descarta os ids mais antigos.
     */
    public function handle(User $user, string $tour): void
    {
        $seen = $user->tours_seen ?? [];

        if (in_array($tour, $seen, true)) {
            return;
        }

        $seen[] = $tour;

        // tours_seen fica fora do $fillable: só esta Action grava.
        $user->tours_seen = array_slice($seen, -self::LIMIT);
        $user->save();
    }
}
