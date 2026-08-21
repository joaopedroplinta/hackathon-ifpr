<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Users\UpdateUserRoles;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\UpdateUserRolesRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gerenciar papel de usuário -- exclusivo de admin, ver UserPolicy.
 * Papel é global (spatie/laravel-permission), não por evento -- PLANO.md §3.
 */
class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $busca = trim((string) $request->query('busca', ''));

        $usuarios = User::query()
            ->when($busca !== '', fn ($query) => $query->where(
                fn ($query) => $query->where('name', 'like', "%{$busca}%")->orWhere('email', 'like', "%{$busca}%")
            ))
            ->with('roles:id,name')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('admin/usuarios/index', [
            'usuarios' => $usuarios->through(fn (User $usuario) => [
                'id' => $usuario->id,
                'nome' => $usuario->name,
                'email' => $usuario->email,
                'papeis' => $usuario->roles->pluck('name')->all(),
                'sou_eu' => $usuario->is($request->user()),
            ]),
            'filtros' => ['busca' => $busca !== '' ? $busca : null],
            'opcoes_papeis' => array_map(
                fn (Role $papel) => ['value' => $papel->value, 'label' => $papel->label()],
                Role::cases(),
            ),
        ]);
    }

    public function update(UpdateUserRolesRequest $request, User $usuario, UpdateUserRoles $action): RedirectResponse
    {
        $this->authorize('updateRoles', $usuario);

        $action->handle($request->user(), $usuario, $request->validated('roles'));

        return to_route('admin.usuarios.index')->with('sucesso', "Papéis de {$usuario->name} atualizados.");
    }
}
