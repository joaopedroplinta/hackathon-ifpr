import { Head, Link } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { Cookie, KeyRound, ShieldCheck } from 'lucide-react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import RodapePublico from '@/components/hackathon/rodape-publico';

export default function Cookies() {
    const reduzMovimento = useReducedMotion();

    const listaVariants: Variants = {
        oculto: {},
        visivel: { transition: { staggerChildren: reduzMovimento ? 0 : 0.08, delayChildren: reduzMovimento ? 0 : 0.1 } },
    };

    const itemVariants: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 12 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Cookies" />

            <CabecalhoPublico />

            <motion.main
                initial="oculto"
                animate="visivel"
                variants={listaVariants}
                className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 pb-24 sm:p-6"
            >
                <motion.header variants={itemVariants} className="pt-8 sm:pt-12">
                    <h1 className="text-3xl font-medium tracking-tight sm:text-4xl">Cookies</h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        O sistema usa só cookies necessários pro funcionamento — nenhum de rastreamento ou publicidade.
                    </p>
                </motion.header>

                <motion.section variants={itemVariants} className="bg-card rounded-2xl p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <KeyRound className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Sessão e segurança
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Guardam que você está autenticado e protegem os formulários contra envio forjado por outro site (CSRF). Sem eles, entrar na
                        conta ou enviar qualquer formulário não funciona.
                    </p>
                </motion.section>

                <motion.section variants={itemVariants} className="bg-card rounded-2xl p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <Cookie className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Preferência de cookies
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Guarda se você já respondeu ao aviso de cookies, para ele não aparecer de novo a cada visita.
                    </p>
                </motion.section>

                <motion.section variants={itemVariants} className="bg-card rounded-2xl p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <ShieldCheck className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />O que não usamos
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Nenhum cookie de analytics ou de terceiro. As fontes da interface vêm da Bunny Fonts, sem rastreamento.
                    </p>
                </motion.section>

                <motion.p variants={itemVariants} className="text-muted-foreground text-center text-xs">
                    Veja também a{' '}
                    <Link href={route('privacidade.show')} className="text-foreground underline underline-offset-2">
                        política de privacidade
                    </Link>
                    .
                </motion.p>
            </motion.main>

            <RodapePublico />
        </div>
    );
}
