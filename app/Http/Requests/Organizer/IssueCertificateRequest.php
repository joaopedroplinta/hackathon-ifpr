<?php

namespace App\Http\Requests\Organizer;

use App\Enums\CertificateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IssueCertificateRequest extends FormRequest
{
    /** A autorização é da CertificatePolicy, no controller. */
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
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'type' => ['required', 'string', Rule::enum(CertificateType::class)],
            'colocacao' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Escolha a pessoa que vai receber o certificado.',
            'user_id.exists' => 'Essa pessoa não foi encontrada.',
            'type.required' => 'Escolha o tipo do certificado.',
        ];
    }
}
