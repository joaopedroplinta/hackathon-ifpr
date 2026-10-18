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
        if (is_string($this->input('cpf')) && $this->filled('cpf')) {
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
                'bail',
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],

            // Trocar o e-mail muda quem recebe o reset de senha, então vale o
            // mesmo que trocar a senha: exige a senha atual. Conta só-Google
            // não tem senha para conferir e fica de fora.
            'current_password' => [
                Rule::requiredIf($this->changesEmailWithPassword()),
                'nullable',
                'string',
                'current_password',
            ],

            // Nullable de propósito: ninguém é travado de editar nome/e-mail
            // só porque ainda não preencheu CPF. Vira obrigatório de fato só
            // na hora que precisa de verdade -- emitir certificado.
            'cpf' => [
                'bail',
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

    private function changesEmailWithPassword(): bool
    {
        $user = $this->user();

        return filled($user->password) && $this->input('email') !== $user->email;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'Informe sua senha atual para trocar o e-mail.',
            'current_password.current_password' => 'A senha atual está incorreta.',
            'cpf.unique' => 'Este CPF já está cadastrado em outra conta.',
            'matricula_suap.required' => 'Informe sua matrícula do SUAP -- obrigatória para quem é aluno do IFPR.',
            'matricula_siape.required' => 'Informe sua matrícula SIAPE -- obrigatória para quem é professor do IFPR.',
        ];
    }
}
