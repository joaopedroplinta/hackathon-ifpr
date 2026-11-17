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
     * No fixed list on the backend: the tour catalog lives in the frontend.
     * The format alone keeps junk (or a large payload) out of the column.
     * \z, not $: $ would accept a trailing newline.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tour' => ['required', 'string', 'regex:/^[a-z0-9.\-]{1,64}\z/'],
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
