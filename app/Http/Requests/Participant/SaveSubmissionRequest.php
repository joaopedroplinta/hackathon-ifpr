<?php

namespace App\Http\Requests\Participant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Rascunho. Nada é obrigatório aqui de propósito: a equipe anota o que já
 * sabe às 14h e volta depois. A exigência dos campos fica no envio de
 * verdade -- ver SubmitSubmissionRequest.
 */
class SaveSubmissionRequest extends FormRequest
{
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
            'title' => ['nullable', 'string', 'max:120'],
            'summary' => ['nullable', 'string', 'max:300'],
            'description' => ['nullable', 'string', 'max:5000'],
            'repo_url' => ['nullable', 'string', 'url:http,https', 'max:255'],
            'video_url' => ['nullable', 'string', 'url:http,https', 'max:255'],
            'deploy_url' => ['nullable', 'string', 'url:http,https', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'repo_url.url' => 'Informe um endereço completo, começando com https://',
            'video_url.url' => 'Informe um endereço completo, começando com https://',
            'deploy_url.url' => 'Informe um endereço completo, começando com https://',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'title' => 'título do projeto',
            'summary' => 'resumo',
            'description' => 'descrição',
            'repo_url' => 'link do repositório',
            'video_url' => 'link do vídeo',
            'deploy_url' => 'link do projeto no ar',
        ];
    }
}
