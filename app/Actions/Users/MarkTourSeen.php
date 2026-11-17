<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class MarkTourSeen
{
    /** Column cap: the tour catalog is small, this only stops unbounded growth. */
    public const LIMIT = 200;

    /**
     * Idempotent. Past the limit, the oldest ids are dropped. The row is locked
     * so two concurrent marks (two tabs) cannot overwrite each other.
     */
    public function handle(User $user, string $tour): void
    {
        DB::transaction(function () use ($user, $tour) {
            $locked = User::query()->lockForUpdate()->findOrFail($user->id);
            $seen = $locked->tours_seen ?? [];

            if (! in_array($tour, $seen, true)) {
                $seen[] = $tour;

                // tours_seen stays out of $fillable: only this Action writes it.
                $locked->tours_seen = array_slice($seen, -self::LIMIT);
                $locked->save();
            }

            $user->tours_seen = $locked->tours_seen;
            $user->syncOriginal();
        });
    }
}
