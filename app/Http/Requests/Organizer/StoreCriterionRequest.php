<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Serve pra criar e pra editar critério -- mesma razão de
 * StoreScheduleItemRequest, as regras não mudam entre as duas ações.
 */
class StoreCriterionRequest extends FormRequest
{
    /** A autorização é da RubricPolicy do critério pai, no controller. */
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
            'description' => ['nullable', 'string', 'max:1000'],
            // decimal(5,2) no banco: até 999,99. peso 0 não faz sentido --
            // um critério com peso zero não deveria existir na rubrica.
            'weight' => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'max_score' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Dê um nome pro critério.',
            'weight.required' => 'Informe o peso do critério.',
            'weight.min' => 'O peso precisa ser maior que zero.',
            'max_score.required' => 'Informe a nota máxima do critério.',
        ];
    }
}
