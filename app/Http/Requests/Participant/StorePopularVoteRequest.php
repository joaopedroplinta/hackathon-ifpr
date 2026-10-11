<?php

namespace App\Http\Requests\Participant;

use App\Enums\SubmissionStatus;
use App\Models\Event;
use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePopularVoteRequest extends FormRequest
{
    /** A autorização é da PopularVotePolicy, no controller. */
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
            'submission_id' => [
                'required',
                'integer',
                // Mesmo filtro da vitrine pública (SubmissionShowcaseController)
                // -- sem isso dá pra votar em rascunho ou submissão
                // desclassificada só adivinhando o id, que é sequencial.
                Rule::exists('submissions', 'id')
                    ->where('event_id', $event?->id)
                    ->whereIn('status', array_filter(SubmissionStatus::cases(), fn (SubmissionStatus $status) => $status->countsForEvaluation())),
            ],
        ];
    }

    /**
     * Ninguém vota na própria equipe: cada integrante somaria um voto pro
     * prêmio popular sem contrapartida. Só dá pra checar depois que o id já
     * passou pela regra `exists`.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('submission_id')) {
                return;
            }

            $submission = Submission::with('team')->find($this->input('submission_id'));

            if ($submission?->team->hasMember($this->user())) {
                $validator->errors()->add('submission_id', 'Você não pode votar no projeto da sua própria equipe.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'submission_id.required' => 'Escolha um projeto para votar.',
            'submission_id.exists' => 'Projeto inválido para este evento.',
        ];
    }
}
