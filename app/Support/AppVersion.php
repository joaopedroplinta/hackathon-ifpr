<?php

namespace App\Support;

/**
 * Número em `composer.json` (fonte de verdade, bump manual a cada tag) + hash
 * curto do commit lido direto de `.git/`, sem `exec()` -- alguns hosts de
 * produção desabilitam funções de shell, e ler arquivo é mais barato mesmo
 * onde não desabilitam.
 */
class AppVersion
{
    /**
     * Em produção só o número da tag; fora dela, o hash do commit junto --
     * é o que diferencia "a versão que está rodando aqui" de "a versão que
     * foi tagueada".
     */
    public function display(): string
    {
        if (config('app.env') === 'production') {
            return 'v'.$this->numero();
        }

        $commit = $this->commit();

        return $commit
            ? 'v'.$this->numero().'-dev+'.$commit
            : 'v'.$this->numero().'-dev';
    }

    public function numero(): string
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        return $composer['version'] ?? '0.0.0';
    }

    public function commit(): ?string
    {
        $head = base_path('.git/HEAD');

        if (! is_file($head)) {
            return null;
        }

        $conteudo = trim(file_get_contents($head));

        if (! str_starts_with($conteudo, 'ref: ')) {
            // HEAD destacado (deploy fez checkout de um commit específico).
            return substr($conteudo, 0, 7);
        }

        $ref = substr($conteudo, 5);
        $refPath = base_path('.git/'.$ref);

        if (is_file($refPath)) {
            return substr(trim(file_get_contents($refPath)), 0, 7);
        }

        return $this->commitEmPackedRefs($ref);
    }

    /**
     * `git gc` compacta refs soltas em `.git/packed-refs` -- sem isso, o
     * hash some depois da primeira faxina do git no repositório de deploy.
     */
    private function commitEmPackedRefs(string $ref): ?string
    {
        $packed = base_path('.git/packed-refs');

        if (! is_file($packed)) {
            return null;
        }

        foreach (file($packed) as $linha) {
            if (str_ends_with(trim($linha), ' '.$ref)) {
                return substr($linha, 0, 7);
            }
        }

        return null;
    }
}
