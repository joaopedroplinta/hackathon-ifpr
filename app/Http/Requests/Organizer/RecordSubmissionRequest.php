<?php

namespace App\Http\Requests\Organizer;

use App\Enums\SubmissionSource;
use App\Models\Event;
use App\Models\Team;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordSubmissionRequest extends FormRequest
{
    /** A autorização é da SubmissionPolicy, no controller. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $event = Event::current();

        return [
            'team_id' => [
                'required',
                'integer',
                Rule::exists('teams', 'id')->where('event_id', $event?->id),
                function (string $attribute, mixed $value, \Closure $fail) use ($event) {
                    $team = $event ? Team::forEvent($event)->with('submission')->find($value) : null;

                    if ($team?->submission?->isSubmitted()) {
                        $fail('Esta equipe já tem uma submissão registrada -- não é possível lançar por cima.');
                    }
                },
            ],
            'title' => ['nullable', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:300'],
            'repo_url' => ['required', 'string', 'url:http,https', 'max:255'],
            'video_url' => ['nullable', 'string', 'url:http,https', 'max:255'],
            'original_submitted_at' => ['required', 'date'],
            'source' => ['required', Rule::in([SubmissionSource::Email->value, SubmissionSource::Manual->value])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'team_id.required' => 'Escolha a equipe.',
            'repo_url.required' => 'O link do repositório é obrigatório.',
            'repo_url.url' => 'Informe um endereço completo, começando com https://',
            'original_submitted_at.required' => 'Informe o horário em que a equipe entregou de fato.',
            'source.required' => 'Escolha por onde a equipe entregou (e-mail ou papel).',
        ];
    }
}
