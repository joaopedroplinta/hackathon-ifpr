import { type ReactNode } from 'react';

type Props = {
    secoes: { id: string; titulo: string }[];
    children: ReactNode;
};

export default function DocumentoPublico({ secoes, children }: Props) {
    return (
        <div className="grid items-start gap-8 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-16">
            <nav aria-label="Nesta página" className="border-border rounded-2xl border p-5 lg:sticky lg:top-28">
                <p className="text-muted-foreground mb-3 text-xs font-semibold tracking-widest uppercase">Nesta página</p>
                <ol className="flex flex-col gap-1">
                    {secoes.map((section, index) => (
                        <li key={section.id}>
                            <a href={`#${section.id}`} className="hover:bg-muted flex min-h-11 items-center gap-3 rounded-lg px-2 py-2 text-sm">
                                <span className="text-primary shrink-0 font-mono text-xs">{String(index + 1).padStart(2, '0')}</span>
                                {section.titulo}
                            </a>
                        </li>
                    ))}
                </ol>
            </nav>
            <div className="public-document max-w-3xl min-w-0 space-y-6">{children}</div>
        </div>
    );
}
