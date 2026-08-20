<?php

namespace App\Http\Requests\Settings;

use App\Enums\TipoVinculo;
use App\Models\User;
use App\Rules\CpfValido;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Guarda só dígito -- "123.456.789-00" e "12345678900" viram a mesma
     * coisa antes da regra de unicidade rodar, senão duas máscaras diferentes
     * do mesmo CPF passariam batido como "únicas".
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('cpf')) {
            $this->merge(['cpf' => preg_replace('/\D/', '', (string) $this->input('cpf'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],

            // Nullable de propósito: ninguém é travado de editar nome/e-mail
            // só porque ainda não preencheu CPF. Vira obrigatório de fato só
            // na hora que precisa de verdade -- emitir certificado.
            'cpf' => [
                'nullable',
                'string',
                new CpfValido,
                Rule::unique(User::class, 'cpf')->ignore($this->user()->id),
            ],
            'tipo_vinculo' => ['nullable', Rule::enum(TipoVinculo::class)],
            'matricula_suap' => [
                Rule::requiredIf($this->input('tipo_vinculo') === TipoVinculo::AlunoIfpr->value),
                'nullable',
                'string',
                'max:30',
            ],
            'matricula_siape' => [
                Rule::requiredIf($this->input('tipo_vinculo') === TipoVinculo::ProfessorIfpr->value),
                'nullable',
                'string',
                'max:30',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cpf.unique' => 'Este CPF já está cadastrado em outra conta.',
            'matricula_suap.required' => 'Informe sua matrícula do SUAP -- obrigatória para quem é aluno do IFPR.',
            'matricula_siape.required' => 'Informe sua matrícula SIAPE -- obrigatória para quem é professor do IFPR.',
        ];
    }
}
