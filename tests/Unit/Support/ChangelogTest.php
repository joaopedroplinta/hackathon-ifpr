<?php

namespace Tests\Unit\Support;

use App\Support\Changelog;
use PHPUnit\Framework\TestCase;

class ChangelogTest extends TestCase
{
    private const TEXTO = <<<'MD'
# Changelog

## [Não lançado]

### Adicionado

- Coisa ainda não lançada

## [1.1.0] - 2026-02-02

Resumo da `1.1.0`.
Segunda linha do resumo.

Nota de deploy que não deve aparecer.

- Item solto de deploy

### Corrigido

- Item que quebra
  em duas linhas (#1)
- Item com sub-lista
  - sub-item ignorado
    continuação do sub-item
- <script>alert(1)</script> e [clique](javascript:alert(1))

### Infra e processo

- Bastidor

### Segurança

- Reforço de acesso

## [1.0.0] - 2026-01-01

### Testes

- Só testes
MD;

    public function test_keeps_order_and_skips_unreleased(): void
    {
        $versoes = (new Changelog)->parse(self::TEXTO);

        $this->assertSame(['1.1.0', '1.0.0'], array_column($versoes, 'numero'));
        $this->assertSame('2026-02-02', $versoes[0]['data']);
    }

    public function test_summary_is_only_the_first_paragraph(): void
    {
        $v = (new Changelog)->parse(self::TEXTO)[0];

        $this->assertSame('Resumo da <code>1.1.0</code>. Segunda linha do resumo.', $v['resumo']);
    }

    public function test_only_allowed_sections_are_listed(): void
    {
        $v = (new Changelog)->parse(self::TEXTO)[0];

        $this->assertSame(['Corrigido', 'Segurança'], array_column($v['secoes'], 'titulo'));
    }

    public function test_version_with_only_hidden_sections_has_no_sections_and_no_summary(): void
    {
        $v = (new Changelog)->parse(self::TEXTO)[1];

        $this->assertSame([], $v['secoes']);
        $this->assertNull($v['resumo']);
    }

    public function test_wrapped_item_is_one_item_and_sublists_are_dropped(): void
    {
        $itens = (new Changelog)->parse(self::TEXTO)[0]['secoes'][0]['itens'];

        $this->assertCount(3, $itens);
        $this->assertSame('Item que quebra em duas linhas (#1)', $itens[0]);
        $this->assertSame('Item com sub-lista', $itens[1]);
    }

    public function test_raw_html_is_escaped_and_unsafe_links_lose_href(): void
    {
        $item = (new Changelog)->parse(self::TEXTO)[0]['secoes'][0]['itens'][2];

        $this->assertStringNotContainsString('<script>', $item);
        $this->assertStringContainsString('&lt;script&gt;', $item);
        $this->assertStringNotContainsString('javascript:', $item);
    }
}
