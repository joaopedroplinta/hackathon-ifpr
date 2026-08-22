<?php

namespace App\Actions\Certificates;

use App\Enums\CertificateType;
use App\Enums\EvaluationStatus;
use App\Enums\Role;
use App\Enums\TeamMemberStatus;
use App\Models\Event;
use App\Models\Result;
use App\Models\TeamMember;
use App\Models\User;

/**
 * Emissão em lote, rodada pelo organizador via
 * `hackathon:issue-certificates`. Cobre só o que dá pra derivar dos dados
 * do evento -- participação (presença registrada), jurado (avaliação
 * submetida) e colocação (resultado publicado). Certificado de mentor, ou
 * qualquer emissão fora dessas regras, é avulso pela tela /admin/certificados
 * (usuário confirmou essa divisão em 2026-08-12).
 */
class IssueEventCertificates
{
    public function __construct(private IssueCertificate $issue)
    {
        //
    }

    /**
     * @return array{participacao: int, jurado: int, organizador: int, colocacao: int}
     */
    public function handle(Event $event): array
    {
        $emitidos = ['participacao' => 0, 'jurado' => 0, 'organizador' => 0, 'colocacao' => 0];

        foreach ($event->registrations()->with('user')->get() as $registration) {
            if ($registration->user->attendances()->whereHas('checkpoint', fn ($q) => $q->where('event_id', $event->id))->exists()) {
                $teamMember = TeamMember::query()
                    ->where('event_id', $event->id)
                    ->where('user_id', $registration->user->id)
                    ->where('status', TeamMemberStatus::Active)
                    ->with('team.submission')
                    ->first();

                $this->issue->handle($event, $registration->user, CertificateType::Participacao, [
                    'equipe' => $teamMember?->team?->name,
                    'projeto' => $teamMember?->team?->submission?->title,
                ]);
                $emitidos['participacao']++;
            }
        }

        $jurados = User::role(Role::Jurado->value)
            ->whereHas('judgeAssignments', fn ($q) => $q->where('event_id', $event->id)
                ->whereHas('evaluation', fn ($e) => $e->where('status', EvaluationStatus::Submitted->value)))
            ->get();

        foreach ($jurados as $jurado) {
            $this->issue->handle($event, $jurado, CertificateType::Jurado);
            $emitidos['jurado']++;
        }

        foreach (User::role(Role::Organizador->value)->get() as $organizador) {
            $this->issue->handle($event, $organizador, CertificateType::Organizador);
            $emitidos['organizador']++;
        }

        if ($event->resultsArePublished()) {
            $emitidos['colocacao'] = $this->issueColocacao($event);
        }

        return $emitidos;
    }

    private function issueColocacao(Event $event): int
    {
        $resultados = Result::forEvent($event)
            ->where(fn ($q) => $q->where('rank_overall', '<=', 3)->orWhere('rank_track', '<=', 3))
            ->with(['submission.team.activeMemberships.user', 'submission.team.track'])
            ->get();

        /** @var array<int, User> $usuarios */
        $usuarios = [];
        /** @var array<int, array<int, string>> $colocacoesPorUsuario */
        $colocacoesPorUsuario = [];
        /** @var array<int, string> $equipePorUsuario */
        $equipePorUsuario = [];
        /** @var array<int, string|null> $projetoPorUsuario */
        $projetoPorUsuario = [];

        foreach ($resultados as $result) {
            $team = $result->submission->team;
            $colocacoes = [];

            if ($result->rank_overall !== null && $result->rank_overall <= 3) {
                $colocacoes[] = "{$result->rank_overall}º lugar geral";
            }

            if ($result->rank_track !== null && $result->rank_track <= 3 && $team->track) {
                $colocacoes[] = "{$result->rank_track}º lugar na trilha {$team->track->name}";
            }

            if ($colocacoes === []) {
                continue;
            }

            foreach ($team->activeMemberships as $membership) {
                $usuarios[$membership->user_id] = $membership->user;
                $colocacoesPorUsuario[$membership->user_id] = [
                    ...($colocacoesPorUsuario[$membership->user_id] ?? []),
                    ...$colocacoes,
                ];
                $equipePorUsuario[$membership->user_id] = $team->name;
                $projetoPorUsuario[$membership->user_id] = $result->submission->title;
            }
        }

        foreach ($usuarios as $userId => $usuario) {
            $this->issue->handle($event, $usuario, CertificateType::Colocacao, [
                'colocacao' => implode(', ', $colocacoesPorUsuario[$userId]),
                'equipe' => $equipePorUsuario[$userId],
                'projeto' => $projetoPorUsuario[$userId],
            ]);
        }

        return count($usuarios);
    }
}
