<?php

namespace App\Http\Requests\Participant;

use App\Models\Event;
use App\Models\Team;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class JoinTeamRequest extends FormRequest
{
    /**
     * Autorização fica na Policy, chamada no controller com a equipe já
     * resolvida. Aqui só entrada.
     */
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
            'invite_code' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'invite_code.required' => 'Informe o código de convite.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'invite_code' => 'código de convite',
        ];
    }

    /**
     * Código que não existe e código de uma edição anterior caem na mesma
     * mensagem -- distinguir os dois deixaria alguém descobrir por
     * tentativa e erro que um código pertence a outro evento.
     */
    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            if ($validator->errors()->has('invite_code')) {
                return;
            }

            $event = Event::current();
            $code = $this->string('invite_code')->toString();

            if (! $event || ! Team::forEvent($event)->withInviteCode($code)->exists()) {
                $validator->errors()->add('invite_code', 'Código de convite inválido.');
            }
        });
    }
}
