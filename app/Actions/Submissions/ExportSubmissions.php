<?php

namespace App\Actions\Submissions;

use App\Models\Submission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Monta o zip que o organizador baixa em `/painel/submissoes/exportar`: uma
 * planilha com os metadados de cada submissão e os arquivos que cada equipe
 * anexou, agrupados por equipe.
 *
 * Recebe a lista já filtrada (Submission\Controller cuida de status/trilha/
 * busca) para ficar testável sem precisar montar uma requisição HTTP --
 * ver .claude/rules/estrutura.md.
 */
class ExportSubmissions
{
    /**
     * @param  Collection<int, Submission>  $submissions  Precisa vir com `team.track` e `files` carregados.
     * @return string Caminho no disco `local`, pronto para `Storage::download()`.
     */
    public function handle(Collection $submissions): string
    {
        // Nome gerado pelo sistema -- nunca a partir de entrada do usuário
        // (.claude/rules/security.md, mesmo espírito do upload).
        $path = 'exports/'.Str::uuid().'.zip';
        $absolutePath = Storage::disk('local')->path($path);

        Storage::disk('local')->makeDirectory('exports');

        $zip = new ZipArchive;
        $zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('submissoes.csv', $this->csv($submissions));

        foreach ($submissions as $submission) {
            $pasta = Str::slug($submission->team->name);

            foreach ($submission->files as $file) {
                if (Storage::disk('local')->exists($file->path)) {
                    $zip->addFile(Storage::disk('local')->path($file->path), "{$pasta}/{$file->original_name}");
                }
            }
        }

        $zip->close();

        return $path;
    }

    /**
     * @param  Collection<int, Submission>  $submissions
     */
    private function csv(Collection $submissions): string
    {
        $buffer = fopen('php://temp', 'r+');

        fputcsv($buffer, [
            'Equipe', 'Trilha', 'Título', 'Situação', 'Origem',
            'Repositório', 'Vídeo', 'Deploy', 'Enviado em', 'Versão',
        ]);

        foreach ($submissions as $submission) {
            fputcsv($buffer, [
                $submission->team->name,
                $submission->team->track?->name ?? '',
                $submission->title,
                $submission->status->label(),
                $submission->source->label(),
                $submission->repo_url,
                $submission->video_url,
                $submission->deploy_url,
                // America/Sao_Paulo: é a planilha que o organizador confere
                // na hora, não um registro técnico -- .claude/rules/database.md.
                $submission->submitted_at?->timezone('America/Sao_Paulo')->format('d/m/Y H:i'),
                $submission->current_version,
            ]);
        }

        rewind($buffer);
        $conteudo = stream_get_contents($buffer);
        fclose($buffer);

        // BOM: Excel no Windows -- o mais comum na organização -- lê UTF-8
        // sem acento quebrado só com isto na frente.
        return "\xEF\xBB\xBF".$conteudo;
    }
}
