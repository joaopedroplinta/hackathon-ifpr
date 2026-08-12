import { Head } from '@inertiajs/react';
import { Award, CircleAlert, ShieldCheck } from 'lucide-react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';
import { ValidacaoCertificado } from '@/types/validacao-certificado';

export default function ValidarCertificado(props: ValidacaoCertificado) {
    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Validar certificado" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-md flex-col gap-6 p-4 pb-24 sm:p-6">
                <header>
                    <h1 className="text-2xl font-semibold">Validar certificado</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Confirma se um certificado foi mesmo emitido por este evento.</p>
                </header>

                {!props.encontrado ? (
                    <div className="rounded-xl border border-dashed p-10 text-center">
                        <CircleAlert className="text-muted-foreground mx-auto h-8 w-8" aria-hidden="true" />
                        <p className="mt-3 font-medium">Certificado não encontrado</p>
                        <p className="text-muted-foreground mt-1 text-sm">Confira se o link ou o código foi copiado corretamente.</p>
                    </div>
                ) : (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6">
                        <div className="mb-4 flex items-center gap-2 text-green-700 dark:text-green-500">
                            <ShieldCheck className="h-5 w-5 shrink-0" aria-hidden="true" />
                            <p className="font-medium">Certificado válido</p>
                        </div>

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
                    </div>
                )}
            </main>
        </div>
    );
}
