<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 60px 70px;
            color: #1b1b1d;
        }
        .moldura {
            border: 3px solid #357724;
            padding: 50px;
            text-align: center;
        }
        .rotulo {
            font-size: 12px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: #357724;
            margin-bottom: 30px;
        }
        .titulo {
            font-size: 26px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .nome {
            font-size: 30px;
            font-weight: bold;
            margin: 30px 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
            display: inline-block;
        }
        .corpo {
            font-size: 14px;
            line-height: 1.6;
            margin: 0 auto;
            max-width: 480px;
        }
        .rodape {
            margin-top: 50px;
            font-size: 11px;
            color: #6b6e68;
        }
        .codigo {
            margin-top: 6px;
            font-family: 'DejaVu Sans Mono', monospace;
        }
    </style>
</head>
<body>
    <div class="moldura">
        <div class="rotulo">{{ $certificate->type->label() }}</div>
        <div class="titulo">{{ $certificate->event->name }}</div>

        <div class="nome">{{ $certificate->user->name }}</div>

        <div class="corpo">
            @if ($certificate->type->usesAttendanceHours())
                Certificamos a participação com carga horária de
                <strong>{{ $certificate->payload['carga_horaria'] ?? 0 }} horas</strong>.
            @elseif (! empty($certificate->payload['colocacao']))
                Certificamos a conquista de <strong>{{ $certificate->payload['colocacao'] }}</strong>.
            @else
                Certificamos a participação neste evento.
            @endif
        </div>

        <div class="rodape">
            Emitido em {{ $certificate->issued_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}
            <div class="codigo">
                Validação: {{ url('/validar/'.$certificate->code) }}
            </div>
        </div>
    </div>
</body>
</html>
