<?php

namespace App\Support;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\InlinesOnly\InlinesOnlyExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Lê o CHANGELOG.md (fonte única das notas de versão) e devolve só o que
 * interessa a quem usa o sistema. Seção fora de SECOES some da página até
 * alguém decidir incluí-la -- falha para o lado seguro.
 */
class Changelog
{
    /** Seções mostradas. Infra e processo, Testes e Documentação ficam de fora. */
    private const SECOES = ['Adicionado', 'Alterado', 'Corrigido', 'Segurança'];

    /** Markdown → HTML inline; HTML cru escapado e link inseguro (javascript:) sem href. */
    private const MARKDOWN = ['html_input' => 'escape', 'allow_unsafe_links' => false];

    private ?MarkdownConverter $conversor = null;

    /**
     * @return list<array{numero: string, data: string, resumo: ?string, secoes: list<array{titulo: string, itens: list<string>}>}>
     */
    public function fromFile(): array
    {
        $texto = @file_get_contents(base_path('CHANGELOG.md'));

        return $texto === false ? [] : $this->parse($texto);
    }

    /**
     * @return list<array{numero: string, data: string, resumo: ?string, secoes: list<array{titulo: string, itens: list<string>}>}>
     */
    public function parse(string $markdown): array
    {
        $versoes = [];
        $v = null;          // versão em montagem; null fora de uma versão listável
        $resumo = [];       // linhas do 1º parágrafo
        $resumoFechado = false;
        $secao = null;      // título da seção permitida em curso; null se ignorada
        $itens = [];
        $subLista = false;  // dentro de sub-item: continuação não é do item pai
        $cerca = false;     // dentro de bloco de código

        $fecharSecao = function () use (&$v, &$secao, &$itens) {
            if ($v !== null && $secao !== null && $itens !== []) {
                $v['secoes'][] = ['titulo' => $secao, 'itens' => array_map(fn ($i) => $this->html($i), $itens)];
            }
            $secao = null;
            $itens = [];
        };

        $fecharVersao = function () use (&$v, &$versoes, &$resumo, &$resumoFechado, $fecharSecao) {
            $fecharSecao();
            if ($v !== null) {
                $v['resumo'] = $resumo === [] ? null : $this->html(implode(' ', $resumo));
                $versoes[] = $v;
            }
            $v = null;
            $resumo = [];
            $resumoFechado = false;
        };

        foreach (preg_split('/\R/', $markdown) as $linha) {
            if (str_starts_with(ltrim($linha), '```')) {
                $cerca = ! $cerca;

                continue;
            }
            if ($cerca) {
                continue;
            }

            if (str_starts_with($linha, '## ')) {
                $fecharVersao();
                if (preg_match('/^## \[(\d+\.\d+\.\d+)\] - (\d{4}-\d{2}-\d{2})\s*$/', $linha, $m)) {
                    $v = ['numero' => $m[1], 'data' => $m[2], 'resumo' => null, 'secoes' => []];
                }

                continue;
            }

            if ($v === null) {
                continue;
            }

            if (str_starts_with($linha, '### ')) {
                $fecharSecao();
                $resumoFechado = true;
                $titulo = trim(substr($linha, 4));
                $secao = in_array($titulo, self::SECOES, true) ? $titulo : null;
                $subLista = false;

                continue;
            }

            if ($secao === null) {
                // Antes do 1º ### só o 1º parágrafo interessa; o resto são notas de deploy.
                if (! $resumoFechado) {
                    if (trim($linha) === '') {
                        $resumoFechado = $resumo !== [];
                    } else {
                        $resumo[] = trim($linha);
                    }
                }

                continue;
            }

            if (str_starts_with($linha, '- ')) {
                $itens[] = substr($linha, 2);
                $subLista = false;
            } elseif (preg_match('/^\s+[-*] /', $linha)) {
                $subLista = true;
            } elseif (! $subLista && $itens !== [] && preg_match('/^\s+\S/', $linha)) {
                $itens[array_key_last($itens)] .= ' '.trim($linha);
            }
        }
        $fecharVersao();

        return $versoes;
    }

    /**
     * Mesmo ambiente de Str::inlineMarkdown(), mas criado uma vez só: montar o
     * Environment por item custava ~80 ms por request com 112 itens.
     */
    private function html(string $texto): string
    {
        if ($this->conversor === null) {
            $ambiente = new Environment(self::MARKDOWN);
            $ambiente->addExtension(new GithubFlavoredMarkdownExtension);
            $ambiente->addExtension(new InlinesOnlyExtension);
            $this->conversor = new MarkdownConverter($ambiente);
        }

        return trim($this->conversor->convert(trim($texto))->getContent());
    }
}
