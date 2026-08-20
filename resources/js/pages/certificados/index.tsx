import { Head } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { Award, Clock, Download } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { MeuCertificado } from '@/types/certificado';

interface Props {
    certificados: MeuCertificado[];
}

export default function MeusCertificados({ certificados }: Props) {
    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Certificados', href: route('certificates.index') }]}>
            <Head title="Certificados" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-2xl p-4 sm:p-6">
                <h1 className="mb-1 text-2xl font-medium tracking-tight">Certificados</h1>
                <p className="text-muted-foreground mb-6 text-sm">Seus certificados de participação, jurado, organização e colocação.</p>

                {certificados.length === 0 ? (
                    <div className="bg-card flex flex-col items-center gap-3 rounded-2xl p-10 text-center">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <Award className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-medium">Nenhum certificado ainda.</p>
                        <p className="text-muted-foreground text-sm">Eles aparecem aqui depois que a organização emitir, geralmente após o evento.</p>
                    </div>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {certificados.map((c) => (
                            <li key={c.id} className="bg-card flex flex-col gap-3 rounded-2xl p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p className="font-medium">{c.tipo_label}</p>
                                    <p className="text-muted-foreground text-sm">
                                        {c.evento} · {c.emitido_em}
                                    </p>
                                </div>

                                {c.pronto ? (
                                    <Button asChild size="sm">
                                        <a href={route('certificates.download', c.id)}>
                                            <Download className="h-4 w-4" aria-hidden="true" />
                                            Baixar PDF
                                        </a>
                                    </Button>
                                ) : (
                                    <span className="text-muted-foreground inline-flex items-center gap-1.5 text-sm">
                                        <Clock className="h-4 w-4 shrink-0" aria-hidden="true" />
                                        Gerando o PDF…
                                    </span>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </motion.div>
        </AppLayout>
    );
}
