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

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-5xl p-4 sm:p-8">
                <header className="mb-8 border-b pb-6">
                    <p className="text-primary mb-2 text-sm font-medium">Seu histórico no hackathon</p>
                    <h1 className="text-3xl font-bold tracking-tight">Certificados</h1>
                    <p className="text-muted-foreground mt-2 text-sm">Documentos emitidos pela organização, prontos para guardar e compartilhar.</p>
                </header>

                {certificados.length === 0 ? (
                    <div className="border-border bg-card flex flex-col items-center gap-3 rounded-xl border p-10 text-center">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <Award className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-semibold">Nenhum certificado ainda.</p>
                        <p className="text-muted-foreground text-sm">Eles aparecem aqui depois que a organização emitir, geralmente após o evento.</p>
                    </div>
                ) : (
                    <ul className="grid gap-4 md:grid-cols-2">
                        {certificados.map((c) => (
                            <li key={c.id} className="border-border bg-card relative flex min-h-56 flex-col overflow-hidden rounded-2xl border p-6">
                                <span className="bg-primary/10 absolute -top-10 -right-10 size-36 rounded-full" aria-hidden="true" />
                                <Award className="text-primary relative size-7" aria-hidden="true" />
                                <div className="relative mt-8 flex-1">
                                    <p className="font-semibold">{c.tipo_label}</p>
                                    <p className="mt-1 text-sm">{c.evento}</p>
                                    <p className="text-muted-foreground mt-1 text-xs">Emitido em {c.emitido_em}</p>
                                </div>

                                {c.pronto ? (
                                    <Button asChild className="relative mt-5 min-h-11 w-full" size="sm">
                                        <a href={route('certificates.download', c.id)}>
                                            <Download className="h-4 w-4" aria-hidden="true" />
                                            Baixar PDF
                                        </a>
                                    </Button>
                                ) : (
                                    <span className="text-muted-foreground relative mt-5 inline-flex min-h-11 items-center gap-1.5 text-sm">
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
