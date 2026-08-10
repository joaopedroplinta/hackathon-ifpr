<?php

namespace App\Http\Requests\Participant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Envio de verdade. O que o jurado precisa para avaliar vira obrigatório
 * aqui: título, resumo e o link do repositório -- que é o que sustenta o
 * degrau 0 do plano B, onde o horário do último commit vale como prova.
 */
class SubmitSubmissionRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:3', 'max:120'],
            'summary' => ['required', 'string', 'min:20', 'max:300'],
            'description' => ['nullable', 'string', 'max:5000'],
            'repo_url' => ['required', 'string', 'url:http,https', 'max:255'],
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
            'title.required' => 'Dê um título ao projeto antes de enviar.',
            'summary.required' => 'Escreva um resumo do projeto antes de enviar.',
            'summary.min' => 'O resumo precisa de pelo menos 20 caracteres — é o que o jurado lê primeiro.',
            'repo_url.required' => 'O link do repositório é obrigatório: é ele que comprova o horário do trabalho de vocês.',
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
