<?php

namespace Tests\Unit\Support;

use App\Models\ScheduleItem;
use App\Support\IcsCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * O dobramento de linha do RFC 5545 corta em 75 octetos, não caracteres --
 * item de agenda em português real (acento) pode cair bem no limite e
 * corromper o .ics se o corte partir um caractere UTF-8 ao meio.
 *
 * RefreshDatabase mesmo sem gravar nada de propósito: ScheduleItem::factory()
 * usa Event::factory() como default de event_id, e o Eloquent resolve esse
 * default com create(), não make() -- mesmo um ->make() no item de agenda
 * grava um Event de verdade no banco. Sem transação pra desfazer, essa linha
 * vaza pros testes seguintes que assumem "nenhum evento cadastrado".
 */
class IcsCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_folds_a_long_ascii_line_without_losing_content(): void
    {
        $ics = new IcsCalendar;

        $item = ScheduleItem::factory()->make([
            'id' => 1,
            'title' => 'Título curto',
            'description' => str_repeat('a', 200),
            'location' => null,
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        $saida = $ics->build(new Collection([$item]), 'Evento');

        $this->assertStringContainsString(str_repeat('a', 63), $saida);
        $this->assertSame(1, preg_match_all('/^DESCRIPTION:/m', $saida));
    }

    /**
     * Reproduz o corte exato no meio de um caractere multibyte: com esse
     * padding, o byte 75 cai entre os dois bytes de um "ã" em UTF-8.
     */
    public function test_never_splits_a_multibyte_character_when_folding(): void
    {
        $ics = new IcsCalendar;

        $descricaoQuebrava = str_repeat('a', 60).'ção realista de agenda em português, com acentuação de verdade';

        $item = ScheduleItem::factory()->make([
            'id' => 2,
            'title' => 'Cerimônia de abertura',
            'description' => $descricaoQuebrava,
            'location' => 'Auditório Central — Bloco A, salão de convenções',
            'starts_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        $saida = $ics->build(new Collection([$item]), 'Evento');

        foreach (explode("\r\n", $saida) as $linha) {
            $this->assertTrue(
                mb_check_encoding($linha, 'UTF-8'),
                'Linha com encoding UTF-8 inválido -- caractere multibyte partido ao meio: '.bin2hex($linha)
            );
        }

        // Desdobra (RFC 5545: linha de continuação começa com 1 espaço,
        // remover o espaço e concatenar reconstrói o campo original) e
        // confirma que o texto sobrevive inteiro, sem byte perdido.
        $desdobrado = preg_replace("/\r\n /", '', $saida);
        $this->assertStringContainsString('DESCRIPTION:'.$this->escaparComoOClasse($descricaoQuebrava), $desdobrado);
    }

    private function escaparComoOClasse(string $texto): string
    {
        return str_replace(
            ['\\', ',', ';', "\r\n", "\n"],
            ['\\\\', '\\,', '\\;', '\\n', '\\n'],
            $texto
        );
    }
}
