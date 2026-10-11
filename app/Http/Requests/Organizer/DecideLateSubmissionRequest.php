<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;

class DecideLateSubmissionRequest extends FormRequest
{
    /** A autorização é da SubmissionPolicy::decideLate, no controller. */
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
            'decisao' => ['required', 'in:aceitar,recusar'],
            'motivo' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'decisao.required' => 'Escolha aceitar ou recusar.',
            'decisao.in' => 'Escolha aceitar ou recusar.',
            'motivo.required' => 'Registre o motivo da decisão.',
            'motivo.min' => 'Descreva o motivo com pelo menos 10 caracteres.',
            'motivo.max' => 'O motivo pode ter até 500 caracteres.',
        ];
    }
}
