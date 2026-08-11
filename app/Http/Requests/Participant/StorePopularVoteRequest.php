<?php

namespace App\Http\Requests\Participant;

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
                Rule::exists('submissions', 'id')->where('event_id', $event?->id),
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
