<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Allowlist em três eixos, nunca denylist -- mesmo padrão de
 * StoreSubmissionFileRequest (.claude/rules/security.md).
 */
class UploadRegulationRequest extends FormRequest
{
    /** 10 MB, em kilobytes -- um edital em PDF não passa disso. */
    public const MAX_KILOBYTES = 10 * 1024;

    /** A autorização é da EventPolicy, no controller. */
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
            'regulamento' => [
                'required',
                'file',
                'max:'.self::MAX_KILOBYTES,
                'extensions:pdf',
                'mimes:pdf',
                'mimetypes:application/pdf',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'regulamento.required' => 'Escolha o PDF do regulamento.',
            'regulamento.max' => 'O arquivo passa de 10 MB.',
            'regulamento.extensions' => 'Só aceitamos PDF.',
            'regulamento.mimes' => 'Só aceitamos PDF.',
            'regulamento.mimetypes' => 'O conteúdo do arquivo não bate com um PDF.',
        ];
    }
}
