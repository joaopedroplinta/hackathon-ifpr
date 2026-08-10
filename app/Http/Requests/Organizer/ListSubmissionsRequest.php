<?php

namespace App\Http\Requests\Organizer;

use App\Enums\SubmissionStatus;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtro de listagem também é entrada do usuário: `status` vem da query string
 * e cai direto em `where`. Sem validação, um valor inventado devolve lista
 * vazia sem explicação -- e `track_id` de outra edição vaza equipe do evento
 * passado (.claude/rules/database.md).
 */
class ListSubmissionsRequest extends FormRequest
{
    /** A autorização é da Policy, na rota. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(SubmissionStatus::class)],
            'track_id' => [
                'nullable',
                'integer',
                Rule::exists('tracks', 'id')->where('event_id', $this->event()?->id),
            ],
            'busca' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.Illuminate\Validation\Rules\Enum' => 'Situação inválida.',
            'track_id.exists' => 'Trilha inválida para este evento.',
            'busca.max' => 'A busca deve ter no máximo 60 caracteres.',
        ];
    }

    /**
     * Filtros já normalizados, sem os vazios — assim o controller não repete
     * `filled()` a cada `when()` e a tela recebe de volta só o que vale.
     *
     * @return array{status: SubmissionStatus|null, track_id: int|null, busca: string|null}
     */
    public function filtros(): array
    {
        $busca = trim((string) $this->validated('busca', ''));

        return [
            'status' => ($status = $this->validated('status')) ? SubmissionStatus::from($status) : null,
            'track_id' => ($track = $this->validated('track_id')) ? (int) $track : null,
            'busca' => $busca === '' ? null : $busca,
        ];
    }

    private function event(): ?Event
    {
        return Event::current();
    }
}
