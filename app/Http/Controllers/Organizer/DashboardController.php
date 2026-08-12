<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\JudgeAssignmentStatus;
use App\Http\Controllers\Concerns\ResolvesParticipation;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\JudgeAssignment;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use ResolvesParticipation;

    public function index(): Response
    {
        $this->authorize('viewAny', Event::class);

        $event = $this->currentEventOrFail();

        $equipesSemSubmissao = Team::forEvent($event)
            ->with('submission')
            ->get()
            ->filter(fn (Team $team) => ! $team->submission || $team->submission->isDraft())
            ->count();

        $presencaHoje = Attendance::query()
            ->whereHas('checkpoint', fn ($q) => $q->where('event_id', $event->id))
            ->whereDate('checked_in_at', now()->timezone('America/Sao_Paulo')->toDateString())
            ->count();

        return Inertia::render('admin/index', [
            'evento' => ['nome' => $event->name],
            'inscritos' => EventRegistration::query()->where('event_id', $event->id)->count(),
            'equipes_sem_submissao' => $equipesSemSubmissao,
            'atribuicoes_em_aberto' => JudgeAssignment::forEvent($event)
                ->where('status', '!=', JudgeAssignmentStatus::Done->value)
                ->count(),
            'presenca_hoje' => $presencaHoje,
        ]);
    }
}
