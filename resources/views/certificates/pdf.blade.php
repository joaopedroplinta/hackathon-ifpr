<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    @php
        // A aparência é um snapshot salvo no payload no momento da emissão.
        // Assim, alterar logo ou cor do evento não reescreve documentos já emitidos.
        $logoPath = $certificate->payload['template']['logo_path'] ?? null;
        $corDestaque = $certificate->payload['template']['accent_color'] ?? '#357724';
        $logoBase64 = $logoPath && Illuminate\Support\Facades\Storage::disk('local')->exists($logoPath)
            ? base64_encode(Illuminate\Support\Facades\Storage::disk('local')->get($logoPath))
            : null;
        $logoMime = str_ends_with((string) $logoPath, '.png') ? 'image/png' : 'image/jpeg';
        $matriculaCampo = $certificate->user->tipo_vinculo?->exigeMatricula();
        $matricula = $matriculaCampo ? $certificate->user->{$matriculaCampo} : null;
        $cpfFormatado = app(App\Support\Cpf::class)->format($certificate->user->cpf);
        $equipe = $certificate->payload['equipe'] ?? null;
        $projeto = $certificate->payload['projeto'] ?? null;
    @endphp
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; padding: 30px 34px; color: #1d2922; font-family: 'DejaVu Sans', sans-serif; font-size: 12px; }
        .moldura { border: 1px solid {{ $corDestaque }}; outline: 5px solid #f4f1e8; outline-offset: -13px; min-height: 720px; padding: 45px 58px 34px; position: relative; text-align: center; }
        .faixa { background: {{ $corDestaque }}; height: 7px; left: 34px; position: absolute; right: 34px; top: 34px; }
        .cabecalho { border-bottom: 1px solid #d9ddd3; padding-bottom: 19px; width: 100%; }
        .marca-texto { color: {{ $corDestaque }}; font-size: 10px; font-weight: bold; letter-spacing: 2px; text-align: left; text-transform: uppercase; }
        .logo { max-height: 52px; max-width: 200px; text-align: right; }
        .edicao { color: #68736a; font-size: 9px; letter-spacing: 1px; text-align: right; text-transform: uppercase; }
        .selo { border: 1px solid {{ $corDestaque }}; border-radius: 50%; color: {{ $corDestaque }}; display: inline-block; font-size: 8px; font-weight: bold; height: 66px; letter-spacing: 1px; line-height: 13px; padding-top: 17px; text-transform: uppercase; width: 66px; }
        .rotulo { color: {{ $corDestaque }}; font-size: 10px; font-weight: bold; letter-spacing: 3px; margin: 15px 0 9px; text-transform: uppercase; }
        .titulo { color: #173525; font-family: 'DejaVu Serif', serif; font-size: 31px; font-weight: bold; letter-spacing: 1px; margin: 0; }
        .evento { color: #68736a; font-size: 11px; margin: 9px 0 23px; }
        .nome { border-bottom: 2px solid {{ $corDestaque }}; color: #173525; display: inline-block; font-family: 'DejaVu Serif', serif; font-size: 29px; font-weight: bold; margin: 8px 0 18px; max-width: 760px; padding: 0 18px 10px; }
        .corpo { font-size: 13px; line-height: 1.8; margin: 0 auto; max-width: 640px; }
        .projeto { background: #f4f7f2; border-left: 3px solid {{ $corDestaque }}; color: #405047; font-size: 11px; line-height: 1.55; margin: 18px auto 0; max-width: 580px; padding: 9px 14px; text-align: left; }
        .identificacao { color: #657168; font-size: 9px; margin-top: 19px; }
        .rodape { border-top: 1px solid #d9ddd3; margin-top: 25px; padding-top: 16px; text-align: left; width: 100%; }
        .assinatura { border-top: 1px solid #4d5b51; font-size: 11px; padding-top: 7px; text-align: center; width: 250px; }
        .assinatura .cargo { color: #68736a; font-size: 8px; letter-spacing: 1px; margin-top: 3px; text-transform: uppercase; }
        .validacao { color: #68736a; font-size: 9px; line-height: 1.45; text-align: right; }
        .codigo { color: #334238; font-family: 'DejaVu Sans Mono', monospace; font-size: 8px; margin-top: 3px; }
    </style>
</head>
<body>
    <div class="moldura">
        <div class="faixa"></div>
        <table class="cabecalho" role="presentation"><tr>
            <td style="vertical-align: middle; width: 50%;"><div class="marca-texto">Hackathon IFPR · Pinhais</div></td>
            <td style="vertical-align: middle; width: 50%;">
                @if ($logoBase64)<img class="logo" src="data:{{ $logoMime }};base64,{{ $logoBase64 }}" alt="">
                @else<div class="edicao">Documento de certificação</div>@endif
            </td>
        </tr></table>

        <div style="margin-top: 15px;"><div class="selo">IFPR<br>Certifica</div></div>
        <div class="rotulo">{{ $certificate->type->label() }}</div>
        <h1 class="titulo">Certificado</h1>
        <p class="evento">{{ $certificate->event->name }}</p>
        <div class="corpo">Certificamos que</div>
        <div class="nome">{{ $certificate->user->name }}</div>
        <div class="corpo">
            @if ($certificate->type->usesAttendanceHours())
                participou desta edição, cumprindo carga horária de <strong>{{ $certificate->payload['carga_horaria'] ?? 0 }} horas</strong>.
            @elseif (! empty($certificate->payload['colocacao']))
                recebeu o reconhecimento de <strong>{{ $certificate->payload['colocacao'] }}</strong>.
            @else
                participou desta edição do hackathon.
            @endif
        </div>
        @if ($equipe)
            <div class="projeto"><strong>Registro da participação</strong><br>Equipe {{ $equipe }}@if ($projeto) · Projeto “{{ $projeto }}”@endif</div>
        @endif
        <div class="identificacao">CPF: {{ $cpfFormatado ?? 'não informado' }}@if ($matricula)&nbsp;·&nbsp; Matrícula: {{ $matricula }}@endif</div>

        <table class="rodape" role="presentation"><tr>
            <td style="vertical-align: bottom; width: 50%;">
                @if ($certificate->event->certificate_signer_name)
                    <div class="assinatura"><strong>{{ $certificate->event->certificate_signer_name }}</strong><div class="cargo">{{ $certificate->event->certificate_signer_role }}</div></div>
                @endif
            </td>
            <td style="vertical-align: bottom; width: 50%;"><div class="validacao">
                Emitido em {{ $certificate->issued_at->timezone('America/Sao_Paulo')->format('d/m/Y') }}<br>
                Verifique a autenticidade em {{ url('/validar/'.$certificate->code) }}
                <div class="codigo">{{ $certificate->code }}</div>
            </div></td>
        </tr></table>
    </div>
</body>
</html>
