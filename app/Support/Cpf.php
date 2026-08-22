<?php

namespace App\Support;

/**
 * CPF fica salvo só com dígitos (`ProfileUpdateRequest` normaliza na
 * entrada). Formatação de exibição mora aqui porque o certificado em PDF
 * é gerado no backend (dompdf), sem passar pelo React que já formata CPF
 * na tela de Configurações.
 */
class Cpf
{
    public function format(?string $cpf): ?string
    {
        if ($cpf === null || strlen($cpf) !== 11) {
            return $cpf;
        }

        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }
}
