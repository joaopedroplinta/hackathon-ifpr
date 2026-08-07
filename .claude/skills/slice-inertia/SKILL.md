---
name: slice-inertia
description: Construir uma feature vertical completa neste projeto — rota, Form Request, Policy, controller com Inertia::render, página React tipada e teste Pest. Use ao criar ou alterar qualquer tela do sistema do hackathon (equipes, submissão, avaliação, agenda, check-in, resultados), ou quando precisar do padrão de formulário, upload, autorização ou paginação com Inertia.
---

# Slice vertical com Inertia

Ordem de construção. Não pule etapa — teste escrito depois de tudo pronto
costuma testar o que foi feito, não o que era pra ser feito.

**1. Migration + model → 2. Policy → 3. Form Request → 4. Controller →
5. Rota → 6. Tipo TS → 7. Página React → 8. Teste Pest**

## 1. Model

```php
class Team extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'description', 'track_id'];
    // status, event_id e leader_id ficam de fora: quem define é o sistema

    protected function casts(): array
    {
        return [
            'status' => TeamStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    public function members(): HasMany { ... }
    public function submission(): HasOne { ... }

    public function scopeForEvent(Builder $q, Event $event): Builder
    {
        return $q->where('event_id', $event->id);
    }
}
```

## 2. Policy

```php
class TeamPolicy
{
    public function update(User $user, Team $team): bool
    {
        return $team->leader_id === $user->id
            && $team->event->registrationIsOpen();
    }

    public function manageAnyway(User $user): bool
    {
        return $user->hasRole('organizador');
    }
}
```

Prazo faz parte da autorização, não é um `if` no controller. Organizador passa
por cima via `Gate::before` ou policy separada.

## 3. Form Request

```php
class StoreTeamRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:60',
                Rule::unique('teams')->where('event_id', $this->event->id),
            ],
            'track_id' => ['required', Rule::exists('tracks', 'id')
                ->where('event_id', $this->event->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Já existe uma equipe com esse nome neste evento.',
            'name.max' => 'O nome da equipe deve ter no máximo 60 caracteres.',
        ];
    }
}
```

Mensagem em português vive aqui. O React só exibe `errors.name`.

## 4. Controller

```php
public function index(Event $event): Response
{
    $this->authorize('viewAny', [Team::class, $event]);

    return Inertia::render('teams/index', [
        'teams' => TeamResource::collection(
            Team::forEvent($event)
                ->with(['members.user:id,name,avatar_url', 'track:id,name'])
                ->withCount('members')
                ->paginate(20)
        ),
        'filtros' => request()->only('busca', 'track_id'),
    ]);
}

public function store(StoreTeamRequest $request, Event $event): RedirectResponse
{
    $team = DB::transaction(fn () => $this->teams->create($request, $event));

    return to_route('teams.show', $team)
        ->with('sucesso', 'Equipe criada. Compartilhe o código de convite.');
}
```

- `with()` sempre — sem eager loading, uma lista de 30 equipes vira 90 queries
- Escrita que toca mais de uma tabela vai em transação
- Redirect após POST, nunca render direto — evita reenvio no refresh

## 5. Rota

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/equipes', [TeamController::class, 'index'])->name('teams.index');
    Route::post('/equipes', [TeamController::class, 'store'])->name('teams.store');
});
```

URL em português, nome da rota em inglês.

## 6. Tipo

```ts
// resources/js/types/team.ts  (minúsculo com hífen, como o starter kit)
// `type`, não `interface`: useForm do Inertia v2 exige index signature
export type Team = {
  id: number
  name: string
  status: 'draft' | 'confirmed' | 'disqualified'
  members_count: number
  track: { id: number; name: string } | null
}
```

## 7. Página

```tsx
export default function Index({ teams }: { teams: Paginated<Team> }) {
  if (teams.data.length === 0) {
    return <EstadoVazio
      titulo="Nenhuma equipe ainda"
      acao={{ href: route('teams.create'), texto: 'Criar equipe' }}
    />
  }

  return (
    <div className="overflow-x-auto">
      <table className="w-full min-w-[36rem]">...</table>
    </div>
  )
}
```

Formulário:

```tsx
type EquipeForm = { name: string; track_id: string; description: string }

const { data, setData, post, processing, errors } = useForm<EquipeForm>({
  name: '', track_id: '', description: '',
})

<form onSubmit={(e) => { e.preventDefault(); post(route('teams.store')) }}>
  <label htmlFor="name">Nome da equipe</label>
  <input id="name" value={data.name}
         onChange={(e) => setData('name', e.target.value)}
         aria-describedby={errors.name ? 'name-erro' : undefined} />
  {errors.name && <p id="name-erro" className="text-red-600">{errors.name}</p>}

  <button disabled={processing}>
    {processing ? 'Criando…' : 'Criar equipe'}
  </button>
</form>
```

`disabled={processing}` não é detalhe visual — é o que impede duas equipes
criadas por duplo clique.

## 8. Teste

Caminho feliz **e** pelo menos um erro:

```php
it('cria equipe e define o criador como líder', function () {
    $user = User::factory()->inscrito($event = Event::factory()->aberto()->create())->create();

    $this->actingAs($user)
        ->post(route('teams.store'), ['name' => 'Os Devs', 'track_id' => $track->id])
        ->assertRedirect();

    expect($user->fresh()->team->leader_id)->toBe($user->id);
});

it('bloqueia criação depois que as inscrições fecham', function () {
    $event = Event::factory()->inscricoesFechadas()->create();

    $this->actingAs($user)
        ->post(route('teams.store'), ['name' => 'Atrasados'])
        ->assertForbidden();

    expect(Team::count())->toBe(0);
});
```

Testes que **sempre** valem a pena neste projeto: prazo vencido, usuário sem
permissão, jurado tentando ver submissão que não é dele, resultado antes da
publicação.

## Upload

```php
'arquivo' => ['required', 'file', 'max:25600', 'mimes:pdf,zip'],
```

```php
// O disco 'local' do Laravel 12 já aponta para storage/app/private.
// Não existe disco chamado 'private' — usar 'local'.
$path = $request->file('arquivo')->store("submissions/{$submission->id}", 'local');
// nome no disco é gerado pelo Laravel; o original vira metadado
```

Download só por rota com `authorize()`. Nunca link direto pro storage.
