import { AlarmClock, CircleCheck, TriangleAlert } from 'lucide-react';
import { useEffect, useState } from 'react';

import { PrazoSubmissao } from '@/types/submissao';

/**
 * Contador regressivo do prazo de envio.
 *
 * É enfeite, e de propósito: quem decide se o prazo virou é o servidor,
 * comparando com now() (.claude/rules/security.md). O relógio do celular do
 * participante pode estar minutos adiantado — por isso a tela nunca desabilita
 * o botão de enviar com base neste número. Envio atrasado é aceito e marcado
 * como `late`, não recusado.
 */
type Props = {
    prazo: PrazoSubmissao;
    /**
     * A equipe ainda consegue enviar? Depois do prazo a resposta muda por
     * equipe: quem não entregou ainda pode (vira `late`), quem já entregou
     * está travada. Sem isto o contador prometia "o envio ainda é aceito"
     * na mesma tela que dizia que nada mais podia ser alterado.
     */
    envioAindaAceito: boolean;
};

export default function ContadorPrazo({ prazo, envioAindaAceito }: Props) {
    const alvo = prazo.encerra_em ? new Date(prazo.encerra_em).getTime() : null;
    const [restante, setRestante] = useState(() => (alvo ? alvo - Date.now() : null));

    useEffect(() => {
        if (alvo === null) {
            return;
        }

        const id = window.setInterval(() => setRestante(alvo - Date.now()), 1000);

        return () => window.clearInterval(id);
    }, [alvo]);

    if (alvo === null || restante === null) {
        return (
            <p className="text-muted-foreground flex items-center gap-2 text-sm">
                <CircleCheck className="h-4 w-4 shrink-0" aria-hidden="true" />
                Sem prazo definido para o envio.
            </p>
        );
    }

    const dataLocal = new Date(alvo).toLocaleString('pt-BR', {
        timeZone: 'America/Sao_Paulo',
        dateStyle: 'short',
        timeStyle: 'short',
    });

    // Ícone e texto juntos: estado nunca é comunicado só por cor
    // (.claude/rules/frontend.md).
    if (restante <= 0) {
        return (
            <p role="status" className="flex items-center gap-2 text-sm font-medium text-amber-700 dark:text-amber-400">
                <TriangleAlert className="h-4 w-4 shrink-0" aria-hidden="true" />
                <span>
                    Prazo encerrado em {dataLocal}.{envioAindaAceito && ' O envio ainda é aceito, mas entra como fora do prazo.'}
                </span>
            </p>
        );
    }

    const urgente = restante < 60 * 60 * 1000;

    return (
        <p
            role="status"
            aria-live="polite"
            className={`flex items-center gap-2 text-sm ${urgente ? 'font-medium text-amber-700 dark:text-amber-400' : 'text-muted-foreground'}`}
        >
            <AlarmClock className="h-4 w-4 shrink-0" aria-hidden="true" />
            <span>
                Falta{restante >= 2000 ? 'm' : ''} {formatarRestante(restante)} para o prazo — encerra {dataLocal}.
            </span>
        </p>
    );
}

function formatarRestante(ms: number): string {
    const totalSegundos = Math.floor(ms / 1000);
    const dias = Math.floor(totalSegundos / 86400);
    const horas = Math.floor((totalSegundos % 86400) / 3600);
    const minutos = Math.floor((totalSegundos % 3600) / 60);
    const segundos = totalSegundos % 60;

    if (dias > 0) {
        return `${dias}d ${horas}h`;
    }

    if (horas > 0) {
        return `${horas}h ${minutos}min`;
    }

    return `${minutos}min ${segundos}s`;
}
