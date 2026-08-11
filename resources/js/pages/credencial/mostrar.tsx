import { Head } from '@inertiajs/react';

import AppLayout from '@/layouts/app-layout';
import { Credencial } from '@/types/credencial';

export default function MostrarCredencial({ nome, qr_svg, token }: Credencial) {
    return (
        <AppLayout breadcrumbs={[{ title: 'Crachá', href: route('credencial.show') }]}>
            <Head title="Crachá" />

            <div className="mx-auto w-full max-w-sm p-4">
                <h1 className="mb-1 text-2xl font-semibold">Crachá</h1>
                <p className="text-muted-foreground mb-6 text-sm">
                    Mostre este código pra organização em cada entrada e oficina. É pessoal e não muda durante o evento.
                </p>

                <div className="flex flex-col items-center gap-4 rounded-xl border p-6">
                    {/* SVG vem do servidor via bacon/bacon-qr-code, a partir de um
                        uuid que a gente mesmo gera -- nunca de entrada do usuário,
                        então não tem risco de injeção aqui. */}
                    <div className="rounded-lg bg-white p-3" dangerouslySetInnerHTML={{ __html: qr_svg }} />

                    <p className="font-medium">{nome}</p>

                    <p className="text-muted-foreground text-center text-xs break-all">
                        Câmera não lê? Peça pra organização buscar seu nome na lista, ou informe este código: {token}
                    </p>
                </div>
            </div>
        </AppLayout>
    );
}
