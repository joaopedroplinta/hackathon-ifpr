<?php

namespace Database\Factories;

use App\Models\Submission;
use App\Models\SubmissionFile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubmissionFile>
 */
class SubmissionFileFactory extends Factory
{
    protected $model = SubmissionFile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Caminho com nome gerado, como o servidor grava de verdade -- o
        // nome original nunca aparece aqui.
        $hash = Str::random(40);

        return [
            'submission_id' => Submission::factory(),
            'uploaded_by' => User::factory(),
            'version' => 1,
            'path' => "submissions/1/{$hash}.pdf",
            'original_name' => 'pitch.pdf',
            'mime' => 'application/pdf',
            'size' => 1024 * 512,
        ];
    }
}
