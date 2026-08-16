<?php

namespace App\Support;

use App\Models\ScheduleItem;
use Illuminate\Support\Collection;

/**
 * Gera o arquivo .ics da agenda pública (RFC 5545). Sem dependência externa
 * de propósito -- é um formato de texto simples e o pacote mais próximo
 * (bacon/bacon-qr-code) não faz isto; não vale puxar mais uma lib pra tão
 * pouco código.
 */
class IcsCalendar
{
    /**
     * @param  Collection<int, ScheduleItem>  $items
     */
    public function build(Collection $items, string $nomeEvento): string
    {
        $linhas = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Hackathon IFPR//Agenda//PT',
            'CALSCALE:GREGORIAN',
            'X-WR-CALNAME:'.$this->escapar($nomeEvento),
        ];

        foreach ($items as $item) {
            $linhas = [...$linhas, ...$this->evento($item)];
        }

        $linhas[] = 'END:VCALENDAR';

        // CRLF é exigido pelo formato -- não é o \n do resto do sistema.
        return implode("\r\n", array_merge(...array_map(fn (string $linha) => $this->dobrar($linha), $linhas)))."\r\n";
    }

    /**
     * @return array<int, string>
     */
    private function evento(ScheduleItem $item): array
    {
        $linhas = [
            'BEGIN:VEVENT',
            'UID:schedule-item-'.$item->id.'@hackathon-ifpr',
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$item->starts_at->utc()->format('Ymd\THis\Z'),
            'DTEND:'.$item->ends_at->utc()->format('Ymd\THis\Z'),
            'SUMMARY:'.$this->escapar($item->title),
        ];

        if ($item->description) {
            $linhas[] = 'DESCRIPTION:'.$this->escapar($item->description);
        }

        if ($item->location) {
            $linhas[] = 'LOCATION:'.$this->escapar($item->location);
        }

        $linhas[] = 'END:VEVENT';

        return $linhas;
    }

    /**
     * Vírgula, ponto e vírgula e quebra de linha têm significado no formato
     * -- sem escapar, um resumo com vírgula corrompe o campo seguinte.
     */
    private function escapar(string $texto): string
    {
        return str_replace(
            ['\\', ',', ';', "\r\n", "\n"],
            ['\\\\', '\\,', '\\;', '\\n', '\\n'],
            $texto
        );
    }

    /**
     * RFC 5545: linha com mais de 75 octetos dobra numa linha nova que
     * começa com espaço. Sem isto, alguns validadores rejeitam a descrição
     * mais longa.
     *
     * Corte em 75 é em octeto, não caractere -- e título/descrição em
     * português tem acento, que ocupa mais de 1 byte em UTF-8. Cortar no
     * offset exato pode partir um caractere ao meio e corromper a
     * codificação (ex.: "não" virando bytes inválidos), o que faz alguns
     * clientes de calendário rejeitar o arquivo inteiro.
     *
     * @return array<int, string>
     */
    private function dobrar(string $linha): array
    {
        if (strlen($linha) <= 75) {
            return [$linha];
        }

        $partes = [];
        $restante = $linha;

        while (strlen($restante) > 75) {
            $corte = 75;

            // Byte de continuação UTF-8 tem os bits mais altos "10" --
            // recuar até cair no início de um caractere (ASCII ou byte
            // líder de sequência multibyte) evita partir um caractere.
            while ($corte > 0 && (ord($restante[$corte]) & 0xC0) === 0x80) {
                $corte--;
            }

            $partes[] = substr($restante, 0, $corte);
            $restante = ' '.substr($restante, $corte);
        }

        $partes[] = $restante;

        return $partes;
    }
}
