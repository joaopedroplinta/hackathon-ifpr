<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;

class ReopenEvaluationRequest extends FormRequest
{
    /** A autorização é da JudgeAssignmentPolicy, no controller. */
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
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reason.required' => 'Explique o motivo da correção.',
            'reason.min' => 'Descreva o motivo com um pouco mais de detalhe (mínimo 10 caracteres).',
        ];
    }
}
