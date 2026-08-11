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
            'ends_at' => ['required', 'date', 'after:starts_at'],
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
}
