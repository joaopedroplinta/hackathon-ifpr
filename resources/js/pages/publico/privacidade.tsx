import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Clock, Database, Mail, Share2, ShieldCheck } from 'lucide-react';

import CabecalhoPublico from '@/components/hackathon/cabecalho-publico';

export default function Privacidade() {
    return (
        <div className="bg-background text-foreground min-h-svh">
            <Head title="Política de Privacidade" />

            <CabecalhoPublico />

            <main className="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4 pb-24 sm:p-6">
                <header>
                    <h1 className="font-display text-2xl font-semibold tracking-tight">Política de Privacidade</h1>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Como o sistema do 1º Hackathon IFPR Pinhais trata os dados de quem se inscreve, participa ou julga o evento — conforme a Lei
                        Geral de Proteção de Dados (Lei 13.709/2018).
                    </p>
                </header>

                <section className="rounded-xl border p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <Database className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Que dados coletamos
                    </h2>
                    <ul className="text-muted-foreground mt-2 flex flex-col gap-1.5 text-sm">
                        <li>
                            <strong className="text-foreground">Cadastro:</strong> nome, e-mail e senha — ou, se você entrar com Google, nome, e-mail
                            e foto que a própria Google fornece
                        </li>
                        <li>
                            <strong className="text-foreground">Inscrição no evento:</strong> telefone, curso e tamanho de camiseta
                        </li>
                        <li>
                            <strong className="text-foreground">Crachá digital:</strong> um código único pro check-in por QR na entrada
                        </li>
                        <li>
                            <strong className="text-foreground">Atividade sensível:</strong> alteração de nota, desqualificação e publicação de
                            resultado ficam num log de auditoria com autor e horário
                        </li>
                    </ul>
                </section>

                <section className="rounded-xl border border-dashed p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <AlertTriangle className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Restrição alimentar é dado sensível
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Informar restrição alimentar na inscrição é <strong className="text-foreground">opcional</strong>. Esse campo pode revelar
                        condição de saúde ou convicção religiosa — por isso a LGPD trata como dado sensível (art. 5º, II). Usamos só pra logística de
                        alimentação do evento, e ele é apagado se você excluir sua conta.
                    </p>
                </section>

                <section className="rounded-xl border p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <ShieldCheck className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Por que coletamos
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Pra viabilizar o evento em que você optou por se inscrever: identificar sua equipe, avisar sobre prazo e agenda, fazer o
                        check-in, calcular e publicar o resultado, emitir certificado. A base legal é o seu consentimento ao se inscrever e o legítimo
                        interesse da organização em realizar o evento.
                    </p>
                </section>

                <section className="rounded-xl border p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <Share2 className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Com quem compartilhamos
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Jurado só enxerga a submissão atribuída a ele — nunca a lista completa de equipes. Organizador vê o necessário pra operar o
                        evento. Nunca vendemos nem compartilhamos seu dado com anúncio ou rastreamento de terceiros.
                    </p>
                </section>

                <section className="rounded-xl border p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <Clock className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Quanto tempo guardamos
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Enquanto sua conta existir. Nota e certificado já emitido continuam existindo como registro histórico mesmo depois — mas sem
                        seu nome ou e-mail, porque excluir a conta anonimiza esses dados em vez de simplesmente apagar a avaliação de todo mundo que
                        participou com você.
                    </p>
                </section>

                <section className="rounded-xl border p-4">
                    <h2 className="flex items-center gap-2 font-medium">
                        <Mail className="text-muted-foreground h-4 w-4 shrink-0" aria-hidden="true" />
                        Seus direitos e contato
                    </h2>
                    <p className="text-muted-foreground mt-2 text-sm">
                        Você pode acessar, corrigir ou excluir seus dados a qualquer momento em{' '}
                        <span className="text-foreground">Configurações → Excluir conta</span>, se estiver logado. Para dúvida, pedido de
                        portabilidade ou qualquer outro direito do art. 18 da LGPD, o encarregado de proteção de dados deste projeto responde em{' '}
                        <a href="mailto:hackathon@ifpr.edu.br" className="text-foreground underline underline-offset-2">
                            hackathon@ifpr.edu.br
                        </a>
                        .
                    </p>
                </section>

                <p className="text-muted-foreground text-center text-xs">
                    Veja também a{' '}
                    <Link href={route('cookies.show')} className="text-foreground underline underline-offset-2">
                        política de cookies
                    </Link>
                    .
                </p>
            </main>
        </div>
    );
}
