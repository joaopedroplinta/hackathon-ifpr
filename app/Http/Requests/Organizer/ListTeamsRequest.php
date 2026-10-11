<?php

namespace App\Http\Requests\Organizer;

use App\Enums\TeamStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTeamsRequest extends FormRequest
{
    /** A autorização é da TeamPolicy::viewAny, no controller. */
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
            'status' => ['nullable', Rule::enum(TeamStatus::class)],
            'busca' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.Illuminate\Validation\Rules\Enum' => 'Situação inválida.',
            'busca.max' => 'A busca deve ter no máximo 60 caracteres.',
        ];
    }

    /**
     * @return array{status: TeamStatus|null, busca: string|null}
     */
    public function filtros(): array
    {
        $busca = trim((string) $this->validated('busca', ''));

        return [
            'status' => ($status = $this->validated('status')) ? TeamStatus::from($status) : null,
            'busca' => $busca === '' ? null : $busca,
        ];
    }
}
