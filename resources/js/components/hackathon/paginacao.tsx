import { Link } from '@inertiajs/react';

type LinkPaginacao = { url: string | null; label: string; active: boolean };

type PaginacaoProps = {
    links: LinkPaginacao[];
    lastPage: number;
    preserveScroll?: boolean;
};

/** Paginação padrão do Laravel (`->links()` via Inertia). Só renderiza com mais de uma página. */
export default function Paginacao({ links, lastPage, preserveScroll = true }: PaginacaoProps) {
    if (lastPage <= 1) {
        return null;
    }

    return (
        <nav aria-label="Paginação" className="flex flex-wrap gap-1">
            {links.map((link, indice) =>
                link.url ? (
                    <Link
                        key={indice}
                        href={link.url}
                        preserveScroll={preserveScroll}
                        aria-current={link.active ? 'page' : undefined}
                        className={`inline-flex min-h-11 min-w-11 items-center justify-center rounded-full px-3 text-sm ${
                            link.active ? 'bg-primary text-primary-foreground' : 'bg-card hover:bg-muted'
                        }`}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <span
                        key={indice}
                        className="text-muted-foreground bg-card inline-flex min-h-11 min-w-11 items-center justify-center rounded-full px-3 text-sm opacity-50"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ),
            )}
        </nav>
    );
}
