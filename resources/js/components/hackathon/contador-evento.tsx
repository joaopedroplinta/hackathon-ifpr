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
        <div role="status" aria-live="polite" className="flex flex-col items-center gap-4">
            <p className="text-muted-foreground font-mono text-xs tracking-wide uppercase">{rotulo}</p>
            <div className="flex gap-2.5 sm:gap-3.5">
                {partes.map((parte) => (
                    <div
                        key={parte.rotulo}
                        className="bg-card relative flex w-16 flex-col items-center overflow-hidden rounded-xl border py-3.5 shadow-sm sm:w-20 sm:py-4"
                    >
                        {/* borda de sinal no topo -- o mesmo verde-brilho do log de build,
                            o painel inteiro fala "instrumento calibrado", não caixa genérica */}
                        <span className="bg-verde-brilho/70 absolute inset-x-0 top-0 h-[2px]" aria-hidden="true" />
                        <span className="font-display text-2xl font-semibold tabular-nums sm:text-4xl">{String(parte.valor).padStart(2, '0')}</span>
                        <span className="text-muted-foreground mt-0.5 font-mono text-[10px] tracking-wide uppercase sm:text-xs">{parte.rotulo}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}
