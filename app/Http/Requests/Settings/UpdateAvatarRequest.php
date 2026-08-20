<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Mesma allowlist em três eixos de StoreSubmissionFileRequest
 * (.claude/rules/security.md) -- extensão, mime por conteúdo e mimetype,
 * nunca denylist.
 */
class UpdateAvatarRequest extends FormRequest
{
    /** 3 MB -- foto de perfil, não anexo de submissão. */
    public const MAX_KILOBYTES = 3 * 1024;

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
            'foto' => [
                'required',
                'image',
                'max:'.self::MAX_KILOBYTES,
                'extensions:png,jpg,jpeg,webp',
                'mimes:png,jpg,jpeg,webp',
                'mimetypes:image/png,image/jpeg,image/webp',
                'dimensions:min_width=64,min_height=64',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        $tipos = 'Aceitamos PNG, JPG ou WEBP.';

        return [
            'foto.required' => 'Escolha uma foto para enviar.',
            'foto.image' => $tipos,
            'foto.max' => 'A foto passa de 3 MB. Escolha uma menor.',
            'foto.extensions' => $tipos,
            'foto.mimes' => $tipos,
            'foto.mimetypes' => "O conteúdo do arquivo não bate com a extensão. {$tipos}",
            'foto.dimensions' => 'A foto precisa ter pelo menos 64x64 pixels.',
        ];
    }
}
