<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    @php
        // Lidos do snapshot em payload.template, gravado por IssueCertificate
        // no momento da emissão -- nunca do Event ao vivo, senão trocar o
        // design depois mudaria a aparência de certificado já emitido.
        $logoPath = $certificate->payload['template']['logo_path'] ?? null;
        $corDestaque = $certificate->payload['template']['accent_color'] ?? '#357724';
        $logoBase64 = $logoPath && Illuminate\Support\Facades\Storage::disk('local')->exists($logoPath)
            ? base64_encode(Illuminate\Support\Facades\Storage::disk('local')->get($logoPath))
            : null;
        $logoMime = str_ends_with((string) $logoPath, '.png') ? 'image/png' : 'image/jpeg';
    @endphp
    <style>
        @page { margin: 0; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 60px 70px;
            color: #1b1b1d;
        }
        .moldura {
            border: 3px solid {{ $corDestaque }};
            padding: 50px;
            text-align: center;
        }
        .logo {
            max-height: 60px;
            max-width: 220px;
            margin-bottom: 20px;
        }
        .rotulo {
            font-size: 12px;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: {{ $corDestaque }};
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
        .projeto {
            font-size: 13px;
            color: #4b4e49;
            margin-top: 8px;
        }
        .identificacao {
            margin-top: 24px;
            font-size: 11px;
            color: #6b6e68;
        }
        .assinatura {
            margin: 40px auto 0;
            width: 260px;
            border-top: 1px solid #6b6e68;
            padding-top: 8px;
            font-size: 13px;
        }
        .assinatura .cargo {
            font-size: 11px;
            color: #6b6e68;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .rodape {
            margin-top: 30px;
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
    @php
        $matriculaCampo = $certificate->user->tipo_vinculo?->exigeMatricula();
        $matricula = $matriculaCampo ? $certificate->user->{$matriculaCampo} : null;
        $cpfFormatado = app(App\Support\Cpf::class)->format($certificate->user->cpf);
        $equipe = $certificate->payload['equipe'] ?? null;
        $projeto = $certificate->payload['projeto'] ?? null;
    @endphp

    <div class="moldura">
        @if ($logoBase64)
            <img class="logo" src="data:{{ $logoMime }};base64,{{ $logoBase64 }}" alt="">
        @endif
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

            @if ($equipe)
                <div class="projeto">
                    Equipe {{ $equipe }}@if ($projeto), projeto "{{ $projeto }}"@endif
                </div>
            @endif
        </div>

        <div class="identificacao">
            CPF: {{ $cpfFormatado ?? 'não informado' }}
            @if ($matricula)
                &nbsp;·&nbsp;Matrícula: {{ $matricula }}
            @endif
        </div>

        @if ($certificate->event->certificate_signer_name)
            <div class="assinatura">
                <strong>{{ $certificate->event->certificate_signer_name }}</strong>
                <div class="cargo">{{ $certificate->event->certificate_signer_role }}</div>
            </div>
        @endif

        <div class="rodape">
            Emitido em {{ $certificate->issued_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}
            <div class="codigo">
                Validação: {{ url('/validar/'.$certificate->code) }}
            </div>
        </div>
    </div>
</body>
</html>
