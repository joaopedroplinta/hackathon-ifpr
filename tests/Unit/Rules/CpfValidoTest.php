<?php

namespace Tests\Unit\Rules;

use App\Rules\CpfValido;
use PHPUnit\Framework\TestCase;

class CpfValidoTest extends TestCase
{
    private function falha(string $cpf): bool
    {
        $falhou = false;

        (new CpfValido)->validate('cpf', $cpf, function () use (&$falhou) {
            $falhou = true;
        });

        return $falhou;
    }

    public function test_aceita_cpf_valido_com_ou_sem_mascara(): void
    {
        $this->assertFalse($this->falha('52998228712'));
        $this->assertFalse($this->falha('529.982.287-12'));
        $this->assertFalse($this->falha('111.444.777-35'));
    }

    public function test_rejeita_digito_verificador_errado(): void
    {
        $this->assertTrue($this->falha('529.982.287-13'));
    }

    public function test_rejeita_sequencia_repetida(): void
    {
        $this->assertTrue($this->falha('111.111.111-11'));
        $this->assertTrue($this->falha('000.000.000-00'));
    }

    public function test_rejeita_tamanho_errado(): void
    {
        $this->assertTrue($this->falha('123'));
        $this->assertTrue($this->falha('123456789012'));
    }
}
