<?php

namespace App\Http\Requests\Organizer;

use App\Enums\Role;
use App\Models\ConflictOfInterest;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreJudgeAssignmentRequest extends FormRequest
{
    /** A autorização é da JudgeAssignmentPolicy, no controller. */
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
            'submission_id' => [
                'required',
                'integer',
                Rule::exists('submissions', 'id')->where('event_id', $event?->id),
            ],
        ];
    }

    /**
     * Duas regras que só dá pra checar depois que os dois IDs já existem:
     * o usuário precisa ter o papel de jurado, e o par jurado/equipe não
     * pode ter conflito de interesse registrado -- o conflito bloqueia a
     * atribuição, nunca só avisa (regras-avaliacao).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $judge = User::find($this->input('judge_id'));

            if ($judge && ! $judge->hasRole(Role::Jurado->value)) {
                $validator->errors()->add('judge_id', 'Este usuário não tem o papel de jurado.');
            }

            $submission = Submission::find($this->input('submission_id'));

            if ($judge && $submission) {
                $temConflito = ConflictOfInterest::query()
                    ->where('judge_id', $judge->id)
                    ->where('team_id', $submission->team_id)
                    ->exists();

                if ($temConflito) {
                    $validator->errors()->add('judge_id', 'Este jurado tem conflito de interesse registrado com a equipe desta submissão.');
                }

                $jaAtribuido = JudgeAssignment::query()
                    ->where('judge_id', $judge->id)
                    ->where('submission_id', $submission->id)
                    ->exists();

                if ($jaAtribuido) {
                    $validator->errors()->add('judge_id', 'Este jurado já está atribuído a esta submissão.');
                }
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
            'submission_id.required' => 'Escolha uma submissão.',
            'submission_id.exists' => 'Submissão inválida para este evento.',
        ];
    }
}
