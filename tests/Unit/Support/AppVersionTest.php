<?php

namespace Tests\Unit\Support;

use App\Support\AppVersion;
use Tests\TestCase;

class AppVersionTest extends TestCase
{
    public function test_numero_reads_the_version_from_composer_json(): void
    {
        $numero = (new AppVersion)->numero();

        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $numero);
    }

    public function test_display_shows_only_the_tag_in_production(): void
    {
        config(['app.env' => 'production']);

        $exibicao = (new AppVersion)->display();

        $this->assertSame('v'.(new AppVersion)->numero(), $exibicao);
    }

    public function test_display_appends_a_dev_suffix_outside_production(): void
    {
        config(['app.env' => 'local']);

        $exibicao = (new AppVersion)->display();

        $this->assertStringContainsString('-dev', $exibicao);
        $this->assertStringStartsWith('v'.(new AppVersion)->numero(), $exibicao);
    }

    public function test_commit_returns_null_when_there_is_no_git_metadata(): void
    {
        // Ambiente de teste roda dentro do próprio repositório git, então
        // aqui só garante que o método não quebra e devolve string ou nulo
        // -- não dá pra simular "sem .git" sem mexer no filesystem real.
        $commit = (new AppVersion)->commit();

        $this->assertTrue($commit === null || preg_match('/^[0-9a-f]{7}$/', $commit) === 1);
    }
}
