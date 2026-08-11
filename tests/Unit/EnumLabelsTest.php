<?php

namespace Tests\Unit;

use App\Enums\AttendanceMethod;
use App\Enums\CertificateType;
use App\Enums\CheckpointType;
use App\Enums\EvaluationStatus;
use App\Enums\EventStatus;
use App\Enums\IncidentKind;
use App\Enums\JudgeAssignmentStatus;
use App\Enums\Role;
use App\Enums\ScheduleItemType;
use App\Enums\SubmissionSource;
use App\Enums\SubmissionStatus;
use App\Enums\TeamMemberRole;
use App\Enums\TeamMemberStatus;
use App\Enums\TeamStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * match() sem o case correspondente lança UnhandledMatchError em runtime —
 * ou seja, na tela do usuário. Este teste transforma isso em falha de CI:
 * adicionar um case sem traduzir o label quebra aqui, não em produção.
 */
class EnumLabelsTest extends TestCase
{
    /**
     * @return array<string, array{class-string}>
     */
    public static function enums(): array
    {
        return [
            'TeamStatus' => [TeamStatus::class],
            'SubmissionStatus' => [SubmissionStatus::class],
            'SubmissionSource' => [SubmissionSource::class],
            'EvaluationStatus' => [EvaluationStatus::class],
            'Role' => [Role::class],
            'ScheduleItemType' => [ScheduleItemType::class],
            'CertificateType' => [CertificateType::class],
            'CheckpointType' => [CheckpointType::class],
            'IncidentKind' => [IncidentKind::class],
            'JudgeAssignmentStatus' => [JudgeAssignmentStatus::class],
            'EventStatus' => [EventStatus::class],
            'TeamMemberRole' => [TeamMemberRole::class],
            'TeamMemberStatus' => [TeamMemberStatus::class],
            'AttendanceMethod' => [AttendanceMethod::class],
        ];
    }

    /**
     * @param  class-string  $enum
     */
    #[DataProvider('enums')]
    public function test_every_case_has_a_non_empty_label(string $enum): void
    {
        foreach ($enum::cases() as $case) {
            $label = $case->label();

            $this->assertNotSame('', trim($label), "{$enum}::{$case->name} está sem label.");
            $this->assertNotSame(
                $case->value,
                $label,
                "{$enum}::{$case->name} devolve o valor cru em vez de texto em português."
            );
        }
    }
}
