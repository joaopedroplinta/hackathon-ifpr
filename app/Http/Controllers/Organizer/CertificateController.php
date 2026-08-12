<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Certificates\IssueCertificate;
use App\Enums\CertificateType;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\IssueCertificateRequest;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CertificateController extends Controller
{
    use ResolvesParticipation;

    public function index(): Response
    {
        $this->authorize('viewAny', Certificate::class);

        $event = $this->currentEventOrFail();

        $certificados = Certificate::forEvent($event)
            ->with('user:id,name')
            ->orderByDesc('issued_at')
            ->get()
            ->map(fn (Certificate $c) => [
                'id' => $c->id,
                'nome' => $c->user->name,
                'tipo' => $c->type->value,
                'tipo_label' => $c->type->label(),
                'pronto' => $c->isReady(),
                'emitido_em' => $c->issued_at->timezone('America/Sao_Paulo')->format('d/m/Y H:i'),
                'codigo' => $c->code,
            ])
            ->all();

        // Todo mundo, não só quem já se inscreveu neste evento: mentor não
        // tem papel nem inscrição no sistema, só conta -- emissão avulsa
        // precisa alcançar qualquer usuário cadastrado.
        $pessoas = User::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => ['id' => $u->id, 'nome' => $u->name])
            ->all();

        return Inertia::render('admin/certificados/index', [
            'certificados' => $certificados,
            'pessoas' => $pessoas,
            'tipos' => array_map(
                fn (CertificateType $t) => ['value' => $t->value, 'label' => $t->label()],
                CertificateType::cases(),
            ),
        ]);
    }

    public function store(IssueCertificateRequest $request, IssueCertificate $issue): RedirectResponse
    {
        $this->authorize('issue', Certificate::class);

        $event = $this->currentEventOrFail();
        $user = User::findOrFail($request->integer('user_id'));
        $type = CertificateType::from($request->string('type')->value());

        $payload = $request->filled('colocacao') ? ['colocacao' => $request->string('colocacao')->value()] : [];

        $issue->handle($event, $user, $type, $payload);

        return to_route('admin.certificados.index')->with('sucesso', "Certificado de {$user->name} emitido.");
    }
}
