import { useEffect, useState } from 'react';

type Props = {
    /** ISO 8601. Enfeite -- nenhuma tela decide nada a partir disto. */
    alvo: string;
    rotulo: string;
};

/**
 * Contagem regressiva da landing, até o início do evento (ou até a
 * inscrição fechar, conforme o chamador decidir). Não é fonte de decisão --
 * quem confirma prazo é sempre o servidor (.claude/rules/security.md).
 *
 * Tipográfico, não em caixas -- caixa com borda por dígito é o clichê de
 * "SaaS launch countdown" que estávamos tentando tirar daqui.
 */
export default function ContadorEvento({ alvo, rotulo }: Props) {
    const alvoMs = new Date(alvo).getTime();
    const [restante, setRestante] = useState(() => alvoMs - Date.now());

    useEffect(() => {
        const id = window.setInterval(() => setRestante(alvoMs - Date.now()), 1000);

        return () => window.clearInterval(id);
    }, [alvoMs]);

    if (restante <= 0) {
        return null;
    }

    const totalSegundos = Math.floor(restante / 1000);
    const partes = [
        { valor: Math.floor(totalSegundos / 86400), rotulo: 'dias' },
        { valor: Math.floor((totalSegundos % 86400) / 3600), rotulo: 'horas' },
        { valor: Math.floor((totalSegundos % 3600) / 60), rotulo: 'min' },
        { valor: totalSegundos % 60, rotulo: 'seg' },
    ];

    return (
        <div role="status" aria-live="polite" className="flex flex-col items-center gap-3">
            <p className="text-muted-foreground text-xs tracking-wide uppercase">{rotulo}</p>
            <div className="flex items-baseline gap-1.5 sm:gap-2.5">
                {partes.map((parte, indice) => (
                    <span key={parte.rotulo} className="flex items-baseline">
                        <span className="text-3xl font-medium tabular-nums sm:text-5xl">{String(parte.valor).padStart(2, '0')}</span>
                        <span className="text-muted-foreground ml-1 text-xs sm:text-sm">{parte.rotulo}</span>
                        {indice < partes.length - 1 && <span className="text-muted-foreground/40 ml-2.5 sm:ml-3.5">·</span>}
                    </span>
                ))}
            </div>
        </div>
    );
}
