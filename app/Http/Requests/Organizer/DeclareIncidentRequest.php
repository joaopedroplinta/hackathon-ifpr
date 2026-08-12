<?php

namespace App\Http\Requests\Organizer;

use App\Enums\IncidentKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeclareIncidentRequest extends FormRequest
{
    /** A autorização é da IncidentPolicy, no controller. */
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
            'kind' => ['required', Rule::enum(IncidentKind::class)],
            'description' => ['required', 'string', 'min:10', 'max:1000'],
            'deadline_extension_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kind.required' => 'Escolha o tipo do incidente.',
            'description.required' => 'Descreva o que aconteceu -- fica registrado no histórico.',
            'description.min' => 'Descreva com um pouco mais de detalhe -- pelo menos 10 caracteres.',
            'deadline_extension_minutes.max' => 'Extensão de mais de 24h (1440 min) precisa ser mais de um incidente -- confirme o valor.',
        ];
    }
}
