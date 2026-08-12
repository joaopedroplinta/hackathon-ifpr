import { useEffect, useState } from 'react';

type Props = {
    /** ISO 8601. Enfeite -- nenhuma tela decide nada a partir disto. */
    alvo: string;
    rotulo: string;
};

/**
 * Contagem regressiva grande da landing, até o início do evento (ou até a
 * inscrição fechar, conforme o chamador decidir). Não é fonte de decisão --
 * quem confirma prazo é sempre o servidor (.claude/rules/security.md).
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
            <p className="text-muted-foreground text-sm">{rotulo}</p>
            <div className="flex gap-3 sm:gap-4">
                {partes.map((parte) => (
                    <div key={parte.rotulo} className="bg-card flex w-16 flex-col items-center rounded-xl border py-3 sm:w-20">
                        <span className="font-display text-2xl font-semibold tabular-nums sm:text-3xl">{String(parte.valor).padStart(2, '0')}</span>
                        <span className="text-muted-foreground text-xs">{parte.rotulo}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}
