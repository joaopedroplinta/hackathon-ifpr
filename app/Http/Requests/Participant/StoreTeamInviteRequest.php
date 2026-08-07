<?php

namespace App\Http\Requests\Participant;

use App\Enums\TeamMemberStatus;
use App\Models\Event;
use App\Models\Team;
use App\Models\TeamMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreTeamInviteRequest extends FormRequest
{
    /**
     * A autorização de verdade (líder, prazo, equipe cheia, destinatário
     * elegível) mora na TeamPolicy::invite e roda no controller -- aqui é
     * só validação de formato e do que é validação pura de campo.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * E-mail sempre em minúsculas: evita que "Fulano@X.com" e
     * "fulano@x.com" driblem a checagem de convite duplicado.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $team = $this->currentTeam();

        return [
            'email' => [
                'required', 'string', 'email', 'max:255',
                // Não deixa reenviar convite pro mesmo e-mail nesta equipe
                // enquanto o anterior segue pendente -- espelha o índice
                // parcial team_invites_one_pending_per_team_email.
                Rule::unique('team_invites', 'email')->where(
                    fn ($query) => $query->where('team_id', $team?->id ?? 0)->whereNull('accepted_at')
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Informe o e-mail de quem você quer convidar.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Já existe um convite pendente para este e-mail nesta equipe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['email' => 'e-mail'];
    }

    private function currentTeam(): ?Team
    {
        $event = Event::current();

        if (! $event || ! $this->user()) {
            return null;
        }

        return TeamMember::query()
            ->where('event_id', $event->id)
            ->where('user_id', $this->user()->id)
            ->where('status', TeamMemberStatus::Active)
            ->first()?->team;
    }
}
