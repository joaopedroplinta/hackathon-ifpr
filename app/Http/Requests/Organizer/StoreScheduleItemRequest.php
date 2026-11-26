<?php

namespace App\Http\Requests\Organizer;

use App\Enums\ScheduleItemType;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Serve pra criar e pra editar -- as duas ações validam exatamente as
 * mesmas regras, diferente de submissão (rascunho x envio), então não há
 * ganho em duas classes.
 */
class StoreScheduleItemRequest extends FormRequest
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
        $event = Event::current();

        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::enum(ScheduleItemType::class)],
            'starts_at' => ['required', 'date'],
            // Sem início válido não há com o que comparar: "término antes do
            // início" com os dois campos vazios só confunde quem preenche.
            'ends_at' => ['required', 'date', Rule::when($this->filled('starts_at') && strtotime((string) $this->input('starts_at')) !== false, 'after:starts_at')],
            'location' => ['nullable', 'string', 'max:120'],
            'speaker_name' => ['nullable', 'string', 'max:120'],
            'speaker_bio' => ['nullable', 'string', 'max:2000'],
            'track_id' => [
                'nullable',
                'integer',
                Rule::exists('tracks', 'id')->where('event_id', $event?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'O título é obrigatório.',
            'ends_at.after' => 'O horário de término precisa vir depois do início.',
            'track_id.exists' => 'Trilha inválida para este evento.',
        ];
    }

    /**
     * Nome do campo nas mensagens padrão do Laravel -- sem isto aparece
     * "O campo type é obrigatório.".
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'título',
            'description' => 'descrição',
            'type' => 'tipo',
            'starts_at' => 'início',
            'ends_at' => 'término',
            'location' => 'local',
            'speaker_name' => 'palestrante',
            'speaker_bio' => 'bio do palestrante',
            'track_id' => 'trilha',
        ];
    }
}
