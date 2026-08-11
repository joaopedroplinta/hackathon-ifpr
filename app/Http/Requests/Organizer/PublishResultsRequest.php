<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;

class PublishResultsRequest extends FormRequest
{
    /** A autorização é da ResultPolicy, no controller. */
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
            'confirmar_pendencias' => ['sometimes', 'boolean'],
        ];
    }
}
