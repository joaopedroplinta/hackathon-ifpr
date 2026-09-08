import { Link } from '@inertiajs/react';
import { Cookie, KeyRound, ShieldCheck } from 'lucide-react';

import DocumentoPublico from '@/components/hackathon/documento-publico';
import PublicLayout from '@/layouts/public-layout';

export default function Cookies() {
    return (
        <PublicLayout titulo="Cookies" descricao="O sistema usa só cookies necessários pro funcionamento — nenhum de rastreamento ou publicidade.">
            <DocumentoPublico
                secoes={[
                    { id: 'secao-1', titulo: 'Sessão e segurança' },
                    { id: 'secao-2', titulo: 'Preferência de cookies' },
                    { id: 'secao-3', titulo: 'O que não usamos' },
                ]}
            >
                <section id="secao-1" tabIndex={-1} className="border-border bg-card rounded-xl border p-4">
                    <h2 className="flex items-center gap-2 font-semibold">
                        <KeyRound className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Sessão e segurança
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Guardam que você está autenticado e protegem os formulários contra envio forjado por outro site (CSRF). Sem eles, entrar na
                        conta ou enviar qualquer formulário não funciona.
                    </p>
                </section>

                <section id="secao-2" tabIndex={-1} className="border-border bg-card rounded-xl border p-4">
                    <h2 className="flex items-center gap-2 font-semibold">
                        <Cookie className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Preferência de cookies
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Guarda se você já respondeu ao aviso de cookies, para ele não aparecer de novo a cada visita.
                    </p>
                </section>

                <section id="secao-3" tabIndex={-1} className="border-border bg-card rounded-xl border p-4">
                    <h2 className="flex items-center gap-2 font-semibold">
                        <ShieldCheck className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />O que não usamos
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Nenhum cookie de analytics ou de terceiro. As fontes da interface vêm da Bunny Fonts, sem rastreamento.
                    </p>
                </section>

                <p className="text-muted-foreground text-center text-xs">
                    Veja também a{' '}
                    <Link href={route('privacidade.show')} className="text-foreground underline underline-offset-2">
                        política de privacidade
                    </Link>
                    .
                </p>
            </DocumentoPublico>
        </PublicLayout>
    );
}
