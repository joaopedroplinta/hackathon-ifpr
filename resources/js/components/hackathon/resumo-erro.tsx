import { AlertTriangle } from 'lucide-react';

type ResumoErroProps = {
    titulo?: string;
    erros: Record<string, string | undefined>;
};

/** Resumo de erro de formulário para leitores de tela e formulários longos com muitos campos. */
export default function ResumoErro({ titulo = 'Corrija os campos destacados', erros }: ResumoErroProps) {
    const mensagens = Object.values(erros).filter((mensagem): mensagem is string => Boolean(mensagem));

    if (mensagens.length === 0) {
        return null;
    }

    return (
        <div
            role="alert"
            className="flex flex-col gap-2 rounded-2xl border border-red-600/30 bg-red-600/10 p-4 text-sm text-red-900 dark:text-red-200"
        >
            <p className="flex items-center gap-2 font-medium">
                <AlertTriangle className="size-4 shrink-0" aria-hidden="true" />
                {titulo}
            </p>
            <ul className="list-disc space-y-1 pl-6">
                {mensagens.map((mensagem, indice) => (
                    <li key={indice}>{mensagem}</li>
                ))}
            </ul>
        </div>
    );
}
