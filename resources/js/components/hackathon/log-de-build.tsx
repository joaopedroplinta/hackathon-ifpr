type LinhaLog = {
    hora: string;
    texto: string;
    filho?: boolean;
};

const linhas: LinhaLog[] = [
    { hora: '10:32:01', texto: 'equipe "Sensores Verdes" formada' },
    { hora: '10:32:04', texto: '4 integrantes confirmados', filho: true },
    { hora: '11:47:18', texto: 'projeto "EcoRota" enviado' },
    { hora: '11:47:19', texto: 'repositório vinculado · v1', filho: true },
    { hora: '14:15:52', texto: 'avaliação concluída — jurado 2/3' },
    { hora: '14:16:03', texto: 'nota registrada', filho: true },
];

/**
 * Elemento de assinatura da landing -- PLANO.md §11. Único momento
 * visualmente ousado da interface: o resto (formulários, tabelas do admin)
 * fica disciplinado, herdando só paleta e tipografia, sem repetir o efeito.
 * Log ilustrativo, não um feed ao vivo -- mostra a forma real do fluxo
 * (equipe → projeto → avaliação) na língua de terminal que o produto já fala.
 */
export default function LogDeBuild() {
    return (
        <div className="bg-verde-mata ring-verde-brilho/10 relative w-full overflow-hidden rounded-xl text-left shadow-[0_24px_60px_-20px_rgba(0,0,0,0.45)] ring-1">
            {/* fio de sinal no topo -- mesma ideia do contador, o painel de log
                é o único momento visualmente ousado da tela (PLANO.md §11),
                então o acabamento dele precisa parecer instrumento, não janela. */}
            <span className="via-verde-brilho/60 absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent to-transparent" aria-hidden="true" />

            <div className="flex items-center gap-1.5 border-b border-white/10 px-4 py-2.5">
                <span className="size-2.5 rounded-full bg-white/20" />
                <span className="size-2.5 rounded-full bg-white/20" />
                <span className="size-2.5 rounded-full bg-white/20" />
                <span className="ml-2 font-mono text-xs text-white/50">hackathon.log</span>
            </div>

            <div className="px-4 py-4 font-mono text-[13px] leading-relaxed sm:px-5 sm:text-sm">
                <p className="text-verde-brilho motion-safe:animate-in motion-safe:fade-in motion-safe:duration-500">
                    <span aria-hidden="true">$ </span>hackathon --status
                </p>
                {linhas.map((linha, indice) => (
                    <p
                        key={linha.hora}
                        style={{ animationDelay: `${300 + indice * 160}ms` }}
                        className={`motion-safe:animate-in motion-safe:fade-in motion-safe:slide-in-from-left-2 motion-safe:fill-mode-both motion-safe:duration-500 ${linha.filho ? 'pl-4 text-white/60' : 'text-white/90'}`}
                    >
                        {linha.filho && <span aria-hidden="true">└─ </span>}
                        <span className="text-verde-brilho/80">[{linha.hora}]</span> {linha.texto}
                    </p>
                ))}
                <p className="text-white/90">
                    <span className="bg-verde-brilho ml-px inline-block h-[1em] w-[0.55em] translate-y-[0.15em] animate-[cursor-blink_1.1s_steps(1)_infinite]" />
                </p>
            </div>
        </div>
    );
}
