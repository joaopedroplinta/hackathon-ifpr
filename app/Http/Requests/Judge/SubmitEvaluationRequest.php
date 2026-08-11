<?php

namespace App\Http\Requests\Judge;

use App\Models\Rubric;
use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitEvaluationRequest extends FormRequest
{
    /** A autorização é da EvaluationPolicy, no controller. */
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
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.criterion_id' => ['required', 'integer'],
            'scores.*.score' => ['required', 'numeric', 'min:0'],
            'scores.*.comment' => ['nullable', 'string', 'max:1000'],
            'overall_comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Envio exige nota em todo critério da rubrica ativa -- não só nos que
     * vieram no payload (regras-avaliacao).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Submission $submission */
            $submission = $this->route('submission');

            $rubric = Rubric::forEvent($submission->event)->where('is_active', true)->with('criteria')->first();

            if (! $rubric) {
                $validator->errors()->add('scores', 'Não há rubrica ativa para este evento.');

                return;
            }

            $maxScores = $rubric->criteria->pluck('max_score', 'id');

            $enviados = collect($this->input('scores', []))->keyBy('criterion_id');

            foreach ($maxScores as $criterionId => $maxScore) {
                $linha = $enviados->get($criterionId);

                if (! $linha || ($linha['score'] ?? null) === null) {
                    $validator->errors()->add('scores', 'Dê uma nota em todos os critérios antes de enviar.');

                    break;
                }
            }

            foreach ($this->input('scores', []) as $i => $linha) {
                $criterionId = $linha['criterion_id'] ?? null;

                if (! $maxScores->has($criterionId)) {
                    $validator->errors()->add("scores.{$i}.criterion_id", 'Este critério não pertence à rubrica ativa.');

                    continue;
                }

                $score = $linha['score'] ?? null;

                if ($score !== null && $score > $maxScores[$criterionId]) {
                    $validator->errors()->add("scores.{$i}.score", 'A nota não pode passar do máximo do critério.');
                }
            }
        });
    }
}
