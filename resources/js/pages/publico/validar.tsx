import { Head } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { Award, CircleAlert, ShieldCheck } from 'lucide-react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import RodapePublico from '@/components/hackathon/rodape-publico';
import { ValidacaoCertificado } from '@/types/validacao-certificado';

export default function ValidarCertificado(props: ValidacaoCertificado) {
    const reduzMovimento = useReducedMotion();

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 12 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.5, ease: 'easeOut' } },
    };

    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Validar certificado" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-md flex-col gap-8 p-4 pb-24 sm:p-6">
                <motion.header initial="oculto" animate="visivel" variants={fadeIn} className="pt-8 sm:pt-12">
                    <h1 className="text-3xl font-medium tracking-tight">Validar certificado</h1>
                    <p className="text-muted-foreground mt-2 text-sm">Confirma se um certificado foi mesmo emitido por este evento.</p>
                </motion.header>

                {!props.encontrado ? (
                    <motion.div
                        initial="oculto"
                        animate="visivel"
                        variants={fadeIn}
                        className="bg-card flex flex-col items-center gap-3 rounded-2xl p-10 text-center"
                    >
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <CircleAlert className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-medium">Certificado não encontrado</p>
                        <p className="text-muted-foreground text-sm">Confira se o link ou o código foi copiado corretamente.</p>
                    </motion.div>
                ) : (
                    <motion.div
                        initial={reduzMovimento ? false : { opacity: 0, y: 16, scale: 0.98 }}
                        animate={{ opacity: 1, y: 0, scale: 1 }}
                        transition={reduzMovimento ? { duration: 0 } : { type: 'spring', stiffness: 260, damping: 24 }}
                        className="bg-card rounded-2xl p-6"
                    >
                        <motion.div
                            initial={reduzMovimento ? false : { opacity: 0, scale: 0.7 }}
                            animate={{ opacity: 1, scale: 1 }}
                            transition={reduzMovimento ? { duration: 0 } : { type: 'spring', stiffness: 400, damping: 18, delay: 0.15 }}
                            className="mb-4 flex items-center gap-2 text-emerald-700 dark:text-emerald-400"
                        >
                            <ShieldCheck className="h-5 w-5 shrink-0" aria-hidden="true" />
                            <p className="font-medium">Certificado válido</p>
                        </motion.div>

                        <dl className="flex flex-col gap-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground">Nome</dt>
                                <dd className="font-medium">{props.nome}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Tipo</dt>
                                <dd className="flex items-center gap-1.5 font-medium">
                                    <Award className="h-4 w-4 shrink-0" aria-hidden="true" />
                                    {props.tipo_label}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Evento</dt>
                                <dd className="font-medium">{props.evento}</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Carga horária</dt>
                                <dd className="font-medium">{props.carga_horaria} horas</dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">Emitido em</dt>
                                <dd className="font-medium">{props.emitido_em}</dd>
                            </div>
                        </dl>
                    </motion.div>
                )}
            </main>

            <RodapePublico />
        </div>
    );
}
