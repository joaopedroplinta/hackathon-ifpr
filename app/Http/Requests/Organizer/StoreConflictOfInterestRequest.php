<?php

namespace App\Http\Requests\Organizer;

use App\Enums\Role;
use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreConflictOfInterestRequest extends FormRequest
{
    /** A autorização é da ConflictOfInterestPolicy, no controller. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $event = Event::current();

        return [
            'judge_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'team_id' => [
                'required',
                'integer',
                Rule::exists('teams', 'id')->where('event_id', $event?->id),
            ],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $judge = User::find($this->input('judge_id'));

            if ($judge && ! $judge->hasRole(Role::Jurado->value)) {
                $validator->errors()->add('judge_id', 'Este usuário não tem o papel de jurado.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'judge_id.required' => 'Escolha um jurado.',
            'team_id.required' => 'Escolha uma equipe.',
            'team_id.exists' => 'Equipe inválida para este evento.',
        ];
    }
}
