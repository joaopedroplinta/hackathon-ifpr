<?php

namespace App\Http\Requests\Participant;

use App\Enums\ShirtSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRegistrationRequest extends FormRequest
{
    /**
     * Autorização fica na Policy, chamada no controller. Aqui só entrada.
     */
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
            'shirt_size' => ['nullable', Rule::enum(ShirtSize::class)],
            'dietary_notes' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'course' => ['nullable', 'string', 'max:120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'shirt_size' => 'tamanho da camiseta',
            'dietary_notes' => 'restrições alimentares',
            'phone' => 'telefone',
            'course' => 'curso',
        ];
    }
}
