<?php

namespace App\Http\Requests\Organizer;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConfirmAttendanceRequest extends FormRequest
{
    /** A autorização é da AttendancePolicy, no controller. */
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
            'checkpoint_id' => [
                'required',
                'integer',
                Rule::exists('checkpoints', 'id')->where('event_id', $event?->id),
            ],
            // Só existe quando a confirmação vem da busca manual -- ver
            // CheckinController::show().
            'via' => ['nullable', Rule::in(['busca'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'checkpoint_id.required' => 'Escolha um checkpoint antes de confirmar.',
            'checkpoint_id.exists' => 'Checkpoint inválido para este evento.',
        ];
    }
}
