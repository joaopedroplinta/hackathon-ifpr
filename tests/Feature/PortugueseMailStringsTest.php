<?php

/**
 * O template padrão de e-mail do Laravel (saudação, aviso do botão, rodapé)
 * vem em inglês -- lang/pt_BR.json cobre essas strings específicas. Sem
 * isso, um Notification em português mistura idioma no meio do e-mail.
 */
it('translates the strings used by the default mail template to portuguese', function () {
    expect(__('Regards,'))->toBe('Atenciosamente,')
        ->and(__('All rights reserved.'))->toBe('Todos os direitos reservados.')
        ->and(__(
            "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
            'into your web browser:',
            ['actionText' => 'Ver resultado']
        ))->toContain('Se você tiver problemas para clicar no botão "Ver resultado"');
});
