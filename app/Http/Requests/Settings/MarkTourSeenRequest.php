<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class MarkTourSeenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['tour' => $this->route('tour')]);
    }

    /**
     * Sem lista fixa no backend: o catálogo de tours vive no front. O formato
     * basta para ninguém gravar lixo (ou um payload grande) na coluna.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tour' => ['required', 'string', 'regex:/^[a-z0-9.\-]{1,64}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['tour.regex' => 'Identificador de tutorial inválido.'];
    }
}
