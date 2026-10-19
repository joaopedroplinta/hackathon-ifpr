<?php

namespace App\Support;

/**
 * Texto digitado pelo usuário (nome de equipe) entra em e-mail montado com
 * MailMessage::line(), que o Laravel renderiza como Markdown. Sem escapar,
 * "[clique aqui](https://...)" vira link clicável em e-mail com o remetente
 * oficial. O assunto não passa por Markdown e não deve ser escapado.
 */
class MarkdownText
{
    public static function escape(string $text): string
    {
        return preg_replace('/([\\\\`*_{}\[\]()#+\-.!|<>~&])/', '\\\\$1', $text);
    }
}
