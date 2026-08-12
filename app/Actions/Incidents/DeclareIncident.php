<?php

namespace App\Actions\Incidents;

use App\Enums\IncidentKind;
use App\Models\Event;
use App\Models\Incident;
use App\Models\User;

/**
 * Extensão de prazo vale pra TODAS as equipes, nunca caso a caso -- prazo
 * esticado só pra quem reclamou é o tipo de coisa que gera contestação
 * legítima (PLANO.md, Anexo A.3). A soma acontece em
 * Event::effectiveSubmissionDeadline(), não aqui: este incidente é só mais
 * uma linha somada.
 */
class DeclareIncident
{
    public function handle(Event $event, User $autor, IncidentKind $kind, string $description, int $extensaoMinutos = 0): Incident
    {
        $incident = new Incident;
        $incident->event_id = $event->id;
        $incident->kind = $kind;
        $incident->started_at = now();
        $incident->description = $description;
        $incident->deadline_extension_minutes = $extensaoMinutos;
        $incident->declared_by = $autor->id;
        $incident->save();

        activity()
            ->causedBy($autor)
            ->performedOn($incident)
            ->withProperties([
                'motivo' => $description,
                'extensao_minutos' => $extensaoMinutos,
            ])
            ->log('Incidente declarado');

        return $incident;
    }
}
