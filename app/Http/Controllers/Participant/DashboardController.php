<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Início de quem está logado. Para participante é a trilha de progresso
 * (inscrição → equipe → crachá → submissão → resultado) -- cada passo diz
 * se já está feito, se dá pra fazer agora, ou por que ainda não dá.
 * Organizador, admin e jurado nunca chegam a renderizar esta tela: são
 * redirecionados pro próprio painel (/painel, /jurado) antes de qualquer
 * outra coisa, porque não podem atuar como participante (PLANO.md §3).
 */
class DashboardController extends Controller
{
    use ResolvesParticipation;

    public function index(): Response|RedirectResponse
    {
        $user = request()->user();

        // Prioridade: staff (organizador/admin) antes de jurado -- um
        // usuário Organizador+Jurado cai no painel de organização, não na
        // fila de avaliação. Documentado em PLANO.md §3.
        if ($user->isStaff()) {
            return to_route('painel.dashboard');
        }

        if ($user->isJudge()) {
            return to_route('jurado.index');
        }

        $event = Event::current();
        $perfilCertificado = $this->perfilCertificado($user);

        if (! $event) {
            return Inertia::render('dashboard', [
                'trilha' => null,
                'perfil_certificado' => $perfilCertificado,
            ]);
        }

        $inscrito = $event->isRegistered($user);
        $team = $inscrito ? $this->teamOf($user, $event) : null;
        $submission = $team?->submission;

        return Inertia::render('dashboard', [
            'trilha' => [
                $this->passoInscricao($event, $inscrito),
                $this->passoEquipe($inscrito, $team),
                $this->passoCredencial($inscrito),
                $this->passoSubmissao($event, $team, $submission),
                $this->passoResultado($event),
            ],
            'perfil_certificado' => $perfilCertificado,
        ]);
    }

    /**
     * Dados exibidos no certificado. A pendência é calculada aqui para que a
     * dashboard não replique a regra de vínculo/matrícula no navegador.
     *
     * @return array{pendente: bool, campos: list<string>}
     */
    private function perfilCertificado(User $user): array
    {
        $campos = [];

        if (blank($user->cpf)) {
            $campos[] = 'CPF';
        }

        if ($user->tipo_vinculo === null) {
            $campos[] = 'vínculo institucional';
        } elseif (($campoMatricula = $user->tipo_vinculo->exigeMatricula()) && blank($user->{$campoMatricula})) {
            $campos[] = $campoMatricula === 'matricula_suap' ? 'matrícula SUAP' : 'matrícula SIAPE';
        }

        return [
            'pendente' => $campos !== [],
            'campos' => $campos,
        ];
    }

    /** @return array{chave: string, titulo: string, descricao: string, status: string, href: string|null} */
    private function passoInscricao(Event $event, bool $inscrito): array
    {
        if ($inscrito) {
            return $this->passo('inscricao', 'Inscrição', "Você está inscrito em {$event->name}.", 'concluido');
        }

        if ($event->registrationIsOpen()) {
            return $this->passo('inscricao', 'Inscrição', 'Garanta sua vaga nesta edição.', 'disponivel', route('registration.create'));
        }

        return $this->passo('inscricao', 'Inscrição', 'As inscrições não estão abertas no momento.', 'bloqueado');
    }

    /** @return array{chave: string, titulo: string, descricao: string, status: string, href: string|null} */
    private function passoEquipe(bool $inscrito, ?Team $team): array
    {
        if (! $inscrito) {
            return $this->passo('equipe', 'Equipe', 'Finalize a inscrição para formar ou entrar em uma equipe.', 'bloqueado');
        }

        if ($team) {
            return $this->passo('equipe', 'Equipe', "Você faz parte de {$team->name}.", 'concluido', route('teams.show'));
        }

        return $this->passo('equipe', 'Equipe', 'Crie uma equipe ou entre em uma pelo código de convite.', 'disponivel', route('teams.show'));
    }

    /** @return array{chave: string, titulo: string, descricao: string, status: string, href: string|null} */
    private function passoCredencial(bool $inscrito): array
    {
        if (! $inscrito) {
            return $this->passo('credencial', 'Crachá', 'Disponível assim que você se inscrever.', 'bloqueado');
        }

        return $this->passo('credencial', 'Crachá', 'Seu QR de check-in para o dia do evento.', 'disponivel', route('credencial.show'));
    }

    /** @return array{chave: string, titulo: string, descricao: string, status: string, href: string|null} */
    private function passoSubmissao(Event $event, ?Team $team, mixed $submission): array
    {
        if (! $team) {
            return $this->passo('submissao', 'Submissão', 'Entre em uma equipe antes de enviar um projeto.', 'bloqueado');
        }

        if ($submission?->isSubmitted()) {
            return $this->passo('submissao', 'Submissão', "Projeto enviado (versão {$submission->current_version}).", 'concluido', route('submissions.show'));
        }

        if ($event->submissionIsOpen()) {
            return $this->passo('submissao', 'Submissão', 'Envie o projeto da equipe até o prazo.', 'disponivel', route('submissions.show'));
        }

        return $this->passo('submissao', 'Submissão', 'O prazo de envio encerrou.', 'bloqueado');
    }

    /** @return array{chave: string, titulo: string, descricao: string, status: string, href: string|null} */
    private function passoResultado(Event $event): array
    {
        if (! $event->resultsArePublished()) {
            return $this->passo('resultado', 'Resultado', 'Publicado quando a organização fechar a apuração.', 'bloqueado');
        }

        return $this->passo('resultado', 'Resultado', 'O resultado desta edição já foi publicado.', 'disponivel', route('resultados.show'));
    }

    /** @return array{chave: string, titulo: string, descricao: string, status: string, href: string|null} */
    private function passo(string $chave, string $titulo, string $descricao, string $status, ?string $href = null): array
    {
        return compact('chave', 'titulo', 'descricao', 'status', 'href');
    }
}
