import EstadoVazio from '@/components/hackathon/estado-vazio';
import Status from '@/components/hackathon/status';
import PublicLayout from '@/layouts/public-layout';
import { ValidacaoCertificado } from '@/types/validacao-certificado';
import { CircleAlert, ShieldCheck } from 'lucide-react';

export default function ValidarCertificado(props: ValidacaoCertificado) {
    return (
        <PublicLayout titulo="Validar certificado" descricao="Confira a autenticidade de um certificado emitido pela plataforma.">
            {!props.encontrado ? (
                <EstadoVazio
                    icon={CircleAlert}
                    titulo="Certificado não encontrado"
                    descricao="Confira se o link ou o código foi copiado corretamente. Não foi possível confirmar um certificado com este endereço."
                    acao={{ href: route('home'), texto: 'Voltar ao evento' }}
                />
            ) : (
                <div className="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <section className="border-border bg-card overflow-hidden rounded-2xl border">
                        <div className="border-border border-b p-6 sm:p-8">
                            <Status tom="sucesso">Certificado válido</Status>
                            <p className="text-muted-foreground mt-6 text-sm">Emitido para</p>
                            <h2 className="mt-2 text-2xl font-semibold tracking-tight break-words sm:text-3xl">{props.nome}</h2>
                        </div>
                        <dl className="grid gap-6 p-6 sm:grid-cols-2 sm:p-8">
                            {[
                                ['Evento', props.evento],
                                ['Tipo', props.tipo_label],
                                ['Carga horária', props.carga_horaria + ' horas'],
                                ['Emitido em', props.emitido_em],
                            ].map(([label, value]) => (
                                <div key={label} className="min-w-0">
                                    <dt className="text-muted-foreground text-xs">{label}</dt>
                                    <dd className="mt-2 font-medium break-words">{value}</dd>
                                </div>
                            ))}
                        </dl>
                    </section>
                    <aside className="bg-secondary rounded-2xl p-6">
                        <ShieldCheck className="text-primary size-6" aria-hidden="true" />
                        <h2 className="mt-4 font-semibold">Verificação pública</h2>
                        <p className="text-muted-foreground mt-3 text-sm leading-relaxed">
                            Os dados ao lado correspondem ao registro emitido pelo sistema. Compare o nome, o evento e a carga horária com o documento
                            recebido.
                        </p>
                    </aside>
                </div>
            )}
        </PublicLayout>
    );
}
