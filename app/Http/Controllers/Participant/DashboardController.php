<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Início de quem está logado. Para participante é a trilha de progresso
 * (inscrição → equipe → crachá → submissão → resultado) -- cada passo diz
 * se já está feito, se dá pra fazer agora, ou por que ainda não dá.
 * Organizador e jurado também passam por aqui (mesma rota /dashboard para
 * todo mundo, ver app-sidebar.tsx), mas têm o próprio painel dedicado
 * (/admin, /jurado) linkado na navegação -- esta tela não duplica aquilo.
 */
class DashboardController extends Controller
{
    use ResolvesParticipation;

    public function index(): Response
    {
        $user = request()->user();
        $event = Event::current();

        if (! $event) {
            return Inertia::render('dashboard', ['trilha' => null]);
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
        ]);
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
