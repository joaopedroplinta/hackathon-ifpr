<?php

namespace App\Actions\Submissions;

use App\Enums\SubmissionSource;
use App\Models\Event;
use App\Models\Team;
use Illuminate\Support\Carbon;

/**
 * Lê o CSV do formulário externo (degrau 2 do plano B, PLANO.md Anexo A.2).
 * Colunas esperadas na primeira linha: `email_lider`, `repo_url` (as duas
 * únicas obrigatórias -- é tudo que o formulário de emergência pede) e,
 * opcionais, `titulo`, `resumo`, `video_url`, `enviado_em` (ISO 8601; sem
 * ela, assume o momento do import).
 *
 * Casa por e-mail do líder porque é o campo do formulário de emergência
 * que não muda (nome de equipe pode ter erro de digitação). Time não
 * encontrado ou já com submissão que conta pra avaliação nunca vira
 * escrita silenciosa -- entra no relatório pra decisão humana.
 */
class ImportSubmissionsFromCsv
{
    public function __construct(private RecordExternalSubmission $record) {}

    /**
     * @return array{importadas: int, conflitos: array<int, string>, nao_encontrados: array<int, string>}
     */
    public function handle(Event $event, string $caminhoCsv, SubmissionSource $source): array
    {
        $resultado = ['importadas' => 0, 'conflitos' => [], 'nao_encontrados' => []];

        $handle = fopen($caminhoCsv, 'r');
        $cabecalho = fgetcsv($handle);

        if ($handle === false || $cabecalho === false) {
            return $resultado;
        }

        $indice = array_flip(array_map('trim', $cabecalho));

        while (($linha = fgetcsv($handle)) !== false) {
            $email = trim($linha[$indice['email_lider']] ?? '');

            if ($email === '') {
                continue;
            }

            $team = Team::forEvent($event)
                ->whereHas('leader', fn ($q) => $q->where('email', $email))
                ->first();

            if (! $team) {
                $resultado['nao_encontrados'][] = $email;

                continue;
            }

            $enviadoEmBruto = trim($linha[$indice['enviado_em']] ?? '');
            // ->utc() explícito: Carbon::parse() com offset diferente de UTC
            // (ex.: "-03:00" do formulário de emergência) mantém esse fuso
            // como o "lar" do objeto -- ao gravar, o cast do Eloquent
            // formata a hora de parede daquele fuso sem o offset junto, e o
            // Postgres lê como se já fosse UTC. Sem isto, um horário do
            // formulário chegava até 3h errado no banco (.claude/rules/database.md).
            $enviadoEm = $enviadoEmBruto !== '' ? Carbon::parse($enviadoEmBruto)->utc() : now();

            $submission = $this->record->handle(
                $team,
                [
                    'title' => $this->valor($linha, $indice, 'titulo'),
                    'summary' => $this->valor($linha, $indice, 'resumo'),
                    'repo_url' => $this->valor($linha, $indice, 'repo_url'),
                    'video_url' => $this->valor($linha, $indice, 'video_url'),
                ],
                $source,
                null,
                $enviadoEm,
            );

            if ($submission === null) {
                $resultado['conflitos'][] = $team->name;

                continue;
            }

            $resultado['importadas']++;
        }

        fclose($handle);

        return $resultado;
    }

    /**
     * @param  array<int, string>  $linha
     * @param  array<string, int>  $indice
     */
    private function valor(array $linha, array $indice, string $coluna): ?string
    {
        if (! isset($indice[$coluna])) {
            return null;
        }

        $valor = trim($linha[$indice[$coluna]] ?? '');

        return $valor !== '' ? $valor : null;
    }
}
