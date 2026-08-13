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
        <div className="bg-verde-mata w-full overflow-hidden rounded-xl text-left shadow-lg">
            <div className="flex items-center gap-1.5 border-b border-white/10 px-4 py-2.5">
                <span className="size-2.5 rounded-full bg-white/20" />
                <span className="size-2.5 rounded-full bg-white/20" />
                <span className="size-2.5 rounded-full bg-white/20" />
                <span className="ml-2 font-mono text-xs text-white/50">hackathon.log</span>
            </div>

            <div className="px-4 py-4 font-mono text-[13px] leading-relaxed sm:px-5 sm:text-sm">
                <p className="text-verde-brilho">
                    <span aria-hidden="true">$ </span>hackathon --status
                </p>
                {linhas.map((linha) => (
                    <p key={linha.hora} className={linha.filho ? 'pl-4 text-white/60' : 'text-white/90'}>
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
