<?php

namespace Tests\Unit;

use App\Enums\SubmissionStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SubmissionStatusTest extends TestCase
{
    /**
     * @return array<string, array{0: SubmissionStatus, 1: bool, 2: bool, 3: bool}>
     */
    public static function matriz(): array
    {
        // status, concorre, já enviou, chegou atrasado
        return [
            'rascunho' => [SubmissionStatus::Draft, false, false, false],
            'enviado' => [SubmissionStatus::Submitted, true, true, false],
            'atrasado aguardando' => [SubmissionStatus::Late, false, true, true],
            'atrasado aceito' => [SubmissionStatus::LateAccepted, true, true, true],
            'atrasado recusado' => [SubmissionStatus::LateRejected, false, true, true],
            'desclassificado' => [SubmissionStatus::Disqualified, false, false, false],
        ];
    }

    #[DataProvider('matriz')]
    public function test_each_status_has_the_expected_meaning(SubmissionStatus $status, bool $concorre, bool $enviou, bool $atrasado): void
    {
        $this->assertSame($concorre, $status->countsForEvaluation());
        $this->assertSame($enviou, $status->isSent());
        $this->assertSame($atrasado, $status->isLate());
    }

    /** Quem concorre sempre já enviou: nunca um rascunho no ranking. */
    public function test_competing_implies_sent(): void
    {
        foreach (SubmissionStatus::cases() as $status) {
            if ($status->countsForEvaluation()) {
                $this->assertTrue($status->isSent());
            }
        }
    }
}
