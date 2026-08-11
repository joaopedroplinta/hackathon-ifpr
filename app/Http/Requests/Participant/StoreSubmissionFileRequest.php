<?php

namespace App\Http\Requests\Participant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Allowlist em três eixos, nunca denylist (.claude/rules/security.md):
 *
 * - `extensions` olha a extensão que veio no nome do arquivo;
 * - `mimes` casa o conteúdo com a extensão esperada;
 * - `mimetypes` fixa a lista de tipos aceitos pelo conteúdo real.
 *
 * Um `.exe` renomeado para `.pdf` passa pela primeira e morre nas outras
 * duas. Uma regra só não bastaria.
 */
class StoreSubmissionFileRequest extends FormRequest
{
    /** 25 MB, em kilobytes -- o que a regra `max` do Laravel espera. */
    public const MAX_KILOBYTES = 25 * 1024;

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
            'arquivo' => [
                'required',
                'file',
                'max:'.self::MAX_KILOBYTES,
                'extensions:pdf,zip,png,jpg,jpeg',
                'mimes:pdf,zip,png,jpg,jpeg',
                'mimetypes:application/pdf,application/zip,application/x-zip-compressed,image/png,image/jpeg',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $tipos = 'Aceitamos PDF, ZIP, PNG e JPG.';

        return [
            'arquivo.required' => 'Escolha um arquivo para enviar.',
            'arquivo.max' => 'O arquivo passa de 25 MB. Compacte ou suba em partes.',
            'arquivo.extensions' => $tipos,
            'arquivo.mimes' => $tipos,
            'arquivo.mimetypes' => "O conteúdo do arquivo não bate com a extensão. {$tipos}",
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['arquivo' => 'arquivo'];
    }
}
