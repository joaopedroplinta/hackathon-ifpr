import { Head } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';

import AppLayout from '@/layouts/app-layout';
import { Credencial } from '@/types/credencial';

export default function MostrarCredencial({ nome, qr_svg, token }: Credencial) {
    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10, scale: 0.98 },
        visivel: { opacity: 1, y: 0, scale: 1, transition: reduzMovimento ? { duration: 0 } : { type: 'spring', stiffness: 260, damping: 24 } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Crachá', href: route('credencial.show') }]}>
            <Head title="Crachá" />

            <div className="mx-auto w-full max-w-md p-4 sm:p-6">
                <h1 className="mb-1 text-2xl font-bold tracking-tight">Crachá</h1>
                <p className="text-muted-foreground mb-6 text-sm">
                    Mostre este código pra organização em cada entrada e oficina. É pessoal e não muda durante o evento.
                </p>

                <motion.div
                    initial="oculto"
                    animate="visivel"
                    variants={fadeIn}
                    className="border-border bg-card flex flex-col items-center gap-5 rounded-2xl border p-6 sm:p-8"
                >
                    {/* SVG vem do servidor via bacon/bacon-qr-code, a partir de um
                        uuid que a gente mesmo gera -- nunca de entrada do usuário,
                        então não tem risco de injeção aqui. */}
                    <div aria-label="QR Code do crachá" className="rounded-xl bg-white p-4" dangerouslySetInnerHTML={{ __html: qr_svg }} />

                    <p className="font-semibold">{nome}</p>

                    <p className="text-muted-foreground border-border w-full border-t pt-4 text-center text-xs break-all">
                        Câmera não lê? Peça pra organização buscar seu nome na lista, ou informe este código: {token}
                    </p>
                </motion.div>
            </div>
        </AppLayout>
    );
}
