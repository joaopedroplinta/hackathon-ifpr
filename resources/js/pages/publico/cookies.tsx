import { Head } from '@inertiajs/react';
import { Cookie, KeyRound, ShieldCheck } from 'lucide-react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';

export default function Cookies() {
    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Cookies" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 pb-24 sm:p-6">
                <header>
                    <h1 className="font-display text-2xl font-semibold tracking-tight">Cookies</h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        O sistema usa só cookies necessários pro funcionamento — nenhum de rastreamento ou publicidade.
                    </p>
                </header>

                <section className="rounded-xl border p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <KeyRound className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Sessão e segurança
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Guardam que você está autenticado e protegem os formulários contra envio forjado por outro site (CSRF). Sem eles, entrar na
                        conta ou enviar qualquer formulário não funciona.
                    </p>
                </section>

                <section className="rounded-xl border p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <Cookie className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Preferência de cookies
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Guarda se você já respondeu ao aviso de cookies, para ele não aparecer de novo a cada visita.
                    </p>
                </section>

                <section className="rounded-xl border border-dashed p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <ShieldCheck className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />O que não usamos
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Nenhum cookie de analytics ou de terceiro. As fontes da interface vêm da Bunny Fonts, sem rastreamento.
                    </p>
                </section>
            </main>
        </div>
    );
}
