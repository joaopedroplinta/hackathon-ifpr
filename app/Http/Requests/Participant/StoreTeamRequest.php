<?php

namespace App\Http\Requests\Participant;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTeamRequest extends FormRequest
{
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
        $eventId = $event?->id ?? 0;

        return [
            'name' => [
                'required', 'string', 'min:3', 'max:60',
                // Nome único POR EVENTO. Ignora as apagadas com soft delete:
                // equipe desfeita não pode reservar o nome para sempre.
                Rule::unique('teams', 'name')
                    ->where(fn ($query) => $query->where('event_id', $eventId)->whereNull('deleted_at')),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'track_id' => [
                'nullable',
                Rule::exists('tracks', 'id')->where('event_id', $eventId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Já existe uma equipe com esse nome neste evento.',
            'name.min' => 'O nome da equipe precisa de pelo menos 3 caracteres.',
            'track_id.exists' => 'Selecione uma trilha válida.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nome da equipe',
            'description' => 'descrição',
            'track_id' => 'trilha',
        ];
    }
}
