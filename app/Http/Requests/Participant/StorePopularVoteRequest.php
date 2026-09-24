<?php

namespace App\Http\Requests\Participant;

use App\Enums\SubmissionStatus;
use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                    ->whereIn('status', [SubmissionStatus::Submitted, SubmissionStatus::Late]),
            ],
        ];
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
