import { Head } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { ScanLine, ShieldCheck } from 'lucide-react';

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

            <div className="mx-auto w-full max-w-lg p-4 sm:p-8">
                <div className="mb-6 flex items-start gap-4">
                    <span className="bg-primary/10 flex size-12 shrink-0 items-center justify-center rounded-2xl">
                        <ScanLine className="text-primary size-6" aria-hidden="true" />
                    </span>
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Seu crachá digital</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Mostre este código pra organização em cada entrada e oficina. É pessoal e não muda durante o evento.
                        </p>
                    </div>
                </div>

                <motion.div
                    initial="oculto"
                    animate="visivel"
                    variants={fadeIn}
                    className="border-border bg-card relative flex flex-col items-center gap-5 overflow-hidden rounded-[2rem] border p-6 shadow-xl shadow-black/5 sm:p-8"
                >
                    <div className="bg-primary absolute inset-x-0 top-0 h-2" aria-hidden="true" />
                    <div className="mt-2 flex w-full items-center justify-between text-xs">
                        <span className="font-semibold">Hackathon IFPR</span>
                        <span className="text-muted-foreground flex items-center gap-1">
                            <ShieldCheck className="size-3.5" aria-hidden="true" /> Identificação oficial
                        </span>
                    </div>
                    {/* SVG vem do servidor via bacon/bacon-qr-code, a partir de um
                        uuid que a gente mesmo gera -- nunca de entrada do usuário,
                        então não tem risco de injeção aqui. */}
                    <div aria-label="QR Code do crachá" className="rounded-xl bg-white p-4" dangerouslySetInnerHTML={{ __html: qr_svg }} />

                    <div className="text-center">
                        <p className="text-lg font-semibold">{nome}</p>
                        <p className="text-muted-foreground mt-1 text-xs">Participante</p>
                    </div>

                    <p className="text-muted-foreground border-border w-full border-t pt-4 text-center text-xs break-all">
                        Câmera não lê? Peça pra organização buscar seu nome na lista, ou informe este código: {token}
                    </p>
                </motion.div>
            </div>
        </AppLayout>
    );
}
