import { Head } from '@inertiajs/react';
import { Award, Clock, Download } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { MeuCertificado } from '@/types/certificado';

interface Props {
    certificados: MeuCertificado[];
}

export default function MeusCertificados({ certificados }: Props) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Certificados', href: route('certificates.index') }]}>
            <Head title="Certificados" />

            <div className="mx-auto w-full max-w-2xl p-4">
                <h1 className="mb-1 text-2xl font-semibold">Certificados</h1>
                <p className="text-muted-foreground mb-6 text-sm">Seus certificados de participação, jurado, organização e colocação.</p>

                {certificados.length === 0 ? (
                    <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border p-6 text-center">
                        <Award className="text-muted-foreground mx-auto h-8 w-8" aria-hidden="true" />
                        <p className="mt-3 font-medium">Nenhum certificado ainda.</p>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Eles aparecem aqui depois que a organização emitir, geralmente após o evento.
                        </p>
                    </div>
                ) : (
                    <ul className="flex flex-col gap-3">
                        {certificados.map((c) => (
                            <li
                                key={c.id}
                                className="border-sidebar-border/70 dark:border-sidebar-border flex flex-col gap-3 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between"
                            >
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
            </div>
        </AppLayout>
    );
}
