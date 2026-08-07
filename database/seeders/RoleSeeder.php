<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Idempotente: rodar de novo não duplica papel nem quebra vínculo
     * existente. Papéis acumulam — um monitor pode ser participante e
     * organizador ao mesmo tempo.
     */
    public function run(): void
    {
        foreach (RoleEnum::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }
}
