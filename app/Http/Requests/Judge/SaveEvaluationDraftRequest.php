<?php

namespace App\Http\Requests\Judge;

use App\Models\Rubric;
use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveEvaluationDraftRequest extends FormRequest
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
            'scores.*.score' => ['nullable', 'numeric', 'min:0'],
            'scores.*.comment' => ['nullable', 'string', 'max:1000'],
            'overall_comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Rascunho aceita nota parcial -- só confere que cada nota enviada
     * pertence à rubrica ativa do evento e não estoura o máximo do critério.
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
