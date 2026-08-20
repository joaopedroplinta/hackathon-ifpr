<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Confere os dois dígitos verificadores de verdade (mod 11) -- não só o
 * formato. O CPF entra no certificado pra dar validade legal a ele
 * (PLANO.md §4), então "11111111111" ou "123.456.789-00" passando batido
 * aqui vira problema institucional depois, não só um campo mal preenchido.
 */
class CpfValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $digitos = preg_replace('/\D/', '', (string) $value);

        if (strlen($digitos) !== 11) {
            $fail('O :attribute precisa ter 11 dígitos.');

            return;
        }

        // 00000000000, 11111111111 etc. batem no cálculo do dígito
        // verificador mas nunca foram emitidos -- rejeitar de propósito.
        if (preg_match('/^(\d)\1{10}$/', $digitos) === 1) {
            $fail('O :attribute informado não é válido.');

            return;
        }

        if ($this->digitoVerificador($digitos, 9) !== (int) $digitos[9]
            || $this->digitoVerificador($digitos, 10) !== (int) $digitos[10]) {
            $fail('O :attribute informado não é válido.');
        }
    }

    private function digitoVerificador(string $digitos, int $tamanho): int
    {
        $soma = 0;
        $peso = $tamanho + 1;

        for ($i = 0; $i < $tamanho; $i++) {
            $soma += (int) $digitos[$i] * $peso;
            $peso--;
        }

        $resto = $soma % 11;

        return $resto < 2 ? 0 : 11 - $resto;
    }
}
