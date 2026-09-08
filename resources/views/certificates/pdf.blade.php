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
    @php
        $nameLength = mb_strlen($certificate->user->name);
        $nameSize = $nameLength > 120 ? 14 : ($nameLength > 65 ? 21 : 30);
    @endphp
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #243f33; font-size: 10pt; }
        /* Fixed decorations fit inside one landscape A4 sheet. */
        .paper { position: absolute; top: 0; left: 0; width: 841pt; height: 595pt; background: #faf8f1; }
        .rail { position: absolute; top: 0; left: 0; width: 144pt; height: 595pt; background: #153c30; border-right: 4pt solid {{ $corDestaque }}; }
        .rail-frame { position: absolute; top: 28pt; left: 20pt; width: 102pt; height: 537pt; border: 0.6pt solid #779080; }
        .rail-brand { position: absolute; top: 55pt; left: 30pt; color: #f6f3e7; font-size: 23pt; font-weight: bold; letter-spacing: 2pt; }
        .rail-campus { font-size: 8pt; line-height: 1.6; margin-top: 8pt; color: #c7d4c7; }
        .orbit { position: absolute; border: 0.6pt solid #688578; border-radius: 50%; left: 31pt; width: 80pt; height: 80pt; }
        .rail-note { position: absolute; top: 407pt; left: 30pt; color: #e0d0a1; font-family: 'DejaVu Serif', serif; font-size: 13pt; line-height: 1.6; }
        .frame { position: absolute; top: 26pt; left: 170pt; width: 644pt; height: 541pt; border: 0.6pt solid #d6c7a3; }
        .corner { position: absolute; width: 24pt; height: 24pt; border-color: #ac9158; border-style: solid; }
        .moldura { position: absolute; top: 49pt; left: 202pt; width: 580pt; }
        table { border-collapse: collapse; width: 100%; } td { padding: 0; }
        .cabecalho { height: 44pt; }
        .marca-texto { font-size: 8pt; font-weight: bold; letter-spacing: 1.3pt; }
        .marca-sub { font-size: 8pt; margin-top: 5pt; color: #6b786f; }
        .logo { max-width: 132pt; max-height: 40pt; }
        .edicao { font-size: 7pt; text-align: right; color: #6b786f; letter-spacing: 1pt; }
        .rotulo { margin: 18pt 0 5pt; color: {{ $corDestaque }}; font-size: 8pt; letter-spacing: 2pt; text-transform: uppercase; }
        .titulo { margin: 0; font-family: 'DejaVu Serif', serif; font-size: 43pt; font-weight: normal; line-height: 1.2; }
        .gold-rule { width: 58pt; height: 2pt; background: #ac9158; margin: 12pt 0 15pt; }
        .intro { font-size: 9pt; color: #647168; margin-bottom: 6pt; }
        .nome { font-family: 'DejaVu Serif', serif; font-size: {{ $nameSize }}pt; line-height: 1.2; margin: 0 0 9pt; word-wrap: break-word; }
        .identificacao { color: #6b786f; font-size: 8pt; margin-bottom: 14pt; }
        .corpo { font-size: 10pt; line-height: 1.7; word-wrap: break-word; }
        .projeto { border-left: 2pt solid #b39b64; padding-left: 10pt; margin-top: 12pt; font-size: 8pt; line-height: 1.5; color: #59685e; word-wrap: break-word; }
        .rodape { position: absolute; top: 475pt; left: 202pt; width: 580pt; }
        .assinatura { border-top: 0.6pt solid #8c9b8d; width: 247pt; padding-top: 8pt; font-size: 9pt; }
        .cargo { color: #6b786f; font-size: 7pt; margin-top: 4pt; }
        .validacao { color: #647168; font-size: 8pt; line-height: 1.6; text-align: right; }
        .verification { position: absolute; top: 535pt; left: 202pt; width: 580pt; border-top: 0.5pt solid #ddd6c3; padding-top: 7pt; font-size: 6.5pt; }
        .verification a { color: #365d49; text-decoration: none; }
        .codigo { font-family: 'DejaVu Sans Mono', monospace; font-size: 6.5pt; }
    </style>
</head>
<body>
    <div class="paper"></div><div class="rail"></div><div class="rail-frame"></div>
    <div class="rail-brand">IFPR<div class="rail-campus">CAMPUS<br>PINHAIS</div></div>
    <div class="orbit" style="top: 162pt;"></div><div class="orbit" style="top: 202pt;"></div><div class="orbit" style="top: 242pt;"></div>
    <div class="rail-note">Ideias que<br>conectam.<br>Pessoas que<br>transformam.</div>
    <div class="frame"></div>
    <div class="corner" style="top: 26pt; left: 170pt; border-width: 2pt 0 0 2pt;"></div>
    <div class="corner" style="top: 543pt; left: 790pt; border-width: 0 2pt 2pt 0;"></div>
    <div class="moldura">

        <table class="cabecalho" role="presentation"><tr>
            <td style="vertical-align: middle; width: 50%;"><div class="marca-texto">HACKATHON IFPR</div><div class="marca-sub">Instituto Federal do Paraná · Campus Pinhais</div></td>
            <td style="vertical-align: middle; width: 50%;">
                @if ($logoBase64)<img class="logo" src="data:{{ $logoMime }};base64,{{ $logoBase64 }}" alt="">
                @else<div class="edicao">Documento de certificação</div>@endif
            </td>
        </tr></table>

        <div class="rotulo">{{ $certificate->type->label() }}</div>
        <h1 class="titulo">Certificado</h1>
        <div class="gold-rule"></div>
        <div class="intro">Certificamos que</div>
        <div class="nome">{{ $certificate->user->name }}</div>
        <div class="identificacao">CPF: {{ $cpfFormatado ?? 'não informado' }}@if ($matricula)&nbsp;·&nbsp; Matrícula: {{ $matricula }}@endif</div>
        <div class="corpo">
            @if ($certificate->type->usesAttendanceHours())
                participou do <strong>{{ $certificate->event->name }}</strong>, cumprindo carga horária de <strong>{{ $certificate->payload['carga_horaria'] ?? 0 }} horas</strong>.
            @elseif (! empty($certificate->payload['colocacao']))
                recebeu o reconhecimento de <strong>{{ $certificate->payload['colocacao'] }}</strong> no <strong>{{ $certificate->event->name }}</strong>.
            @else
                atuou {{ match ($certificate->type) {
                    App\Enums\CertificateType::Jurado => 'como integrante da banca avaliadora',
                    App\Enums\CertificateType::Mentor => 'na mentoria',
                    App\Enums\CertificateType::Organizador => 'na organização',
                    default => 'como participante',
                } }} do <strong>{{ $certificate->event->name }}</strong>.
            @endif
        </div>
        @if ($equipe)
            <div class="projeto"><strong>Registro da participação</strong><br>Equipe {{ $equipe }}@if ($projeto) · Projeto “{{ $projeto }}”@endif</div>
        @endif


    </div>
        <table class="rodape" role="presentation"><tr>
            <td style="vertical-align: bottom; width: 50%;">
                @if ($certificate->event->certificate_signer_name)
                    <div class="assinatura"><strong>{{ $certificate->event->certificate_signer_name }}</strong><div class="cargo">{{ $certificate->event->certificate_signer_role }}</div></div>
                @endif
            </td>
            <td style="vertical-align: bottom; width: 50%;"><div class="validacao">
                Pinhais, {{ $certificate->issued_at->timezone('America/Sao_Paulo')->translatedFormat('d \\d\\e F \\d\\e Y') }}<br>
                <span class="cargo">Data de emissão</span>
            </div></td>
        </tr></table>
    <div class="verification"><table role="presentation"><tr>
        <td><a href="{{ url('/validar/'.$certificate->code) }}">Verifique a autenticidade deste certificado<br>{{ url('/validar/'.$certificate->code) }}</a></td>
        <td style="text-align: right;">Código de validação<br><span class="codigo">{{ $certificate->code }}</span></td>
    </tr></table></div>
</body>
</html>
