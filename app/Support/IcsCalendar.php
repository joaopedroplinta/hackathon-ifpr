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
            $partes[] = substr($restante, 0, 75);
            $restante = ' '.substr($restante, 75);
        }

        $partes[] = $restante;

        return $partes;
    }
}
