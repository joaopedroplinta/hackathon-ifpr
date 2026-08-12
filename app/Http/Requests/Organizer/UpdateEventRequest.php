<?php

namespace App\Http\Requests\Organizer;

use App\Enums\EventStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventRequest extends FormRequest
{
    /** A autorização é da EventPolicy, no controller. */
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
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'submission_deadline' => ['nullable', 'date'],
            'voting_opens_at' => ['nullable', 'date'],
            'voting_closes_at' => ['nullable', 'date', 'after_or_equal:voting_opens_at'],
            'min_team_size' => ['required', 'integer', 'min:1'],
            'max_team_size' => ['required', 'integer', 'min:1', 'gte:min_team_size'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do evento é obrigatório.',
            'description.max' => 'O tema/desafio pode ter no máximo 2000 caracteres.',
            'registration_closes_at.after_or_equal' => 'O fechamento das inscrições precisa vir depois da abertura.',
            'ends_at.after_or_equal' => 'O fim do evento precisa vir depois do início.',
            'voting_closes_at.after_or_equal' => 'O fechamento da votação precisa vir depois da abertura.',
            'max_team_size.gte' => 'O tamanho máximo da equipe não pode ser menor que o mínimo.',
        ];
    }
}
