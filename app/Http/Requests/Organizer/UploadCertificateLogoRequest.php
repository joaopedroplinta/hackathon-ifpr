<?php

namespace App\Http\Requests\Organizer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Allowlist em três eixos, nunca denylist -- mesmo padrão de
 * UploadRegulationRequest (.claude/rules/security.md). SVG fica de fora:
 * o dompdf tem suporte inconsistente e SVG pode embutir script.
 */
class UploadCertificateLogoRequest extends FormRequest
{
    /** 2 MB, em kilobytes -- logo é ícone, não banner. */
    public const MAX_KILOBYTES = 2 * 1024;

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
            'logo' => [
                'required',
                'file',
                'max:'.self::MAX_KILOBYTES,
                'extensions:png,jpg,jpeg',
                'mimes:png,jpg,jpeg',
                'mimetypes:image/png,image/jpeg',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'logo.required' => 'Escolha a imagem do logo.',
            'logo.max' => 'O arquivo passa de 2 MB.',
            'logo.extensions' => 'Só aceitamos PNG ou JPG.',
            'logo.mimes' => 'Só aceitamos PNG ou JPG.',
            'logo.mimetypes' => 'O conteúdo do arquivo não bate com uma imagem PNG ou JPG.',
        ];
    }
}
