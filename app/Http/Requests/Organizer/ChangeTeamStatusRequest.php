<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;

/** Desclassificar e reabilitar pedem a mesma coisa: um motivo com substância. */
class ChangeTeamStatusRequest extends FormRequest
{
    /** A autorização é da TeamPolicy, no controller. */
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
            'motivo' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'Registre o motivo da decisão.',
            'motivo.min' => 'Descreva o motivo com pelo menos 10 caracteres.',
            'motivo.max' => 'O motivo pode ter até 500 caracteres.',
        ];
    }
}
