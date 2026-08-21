import { Link } from '@inertiajs/react';
import { Check } from 'lucide-react';

import AppLogoIcon from '@/components/app-logo-icon';

interface AuthLayoutProps {
    children: React.ReactNode;
    title?: string;
    description?: string;
}

const destaques = ['Inscrição e formação de equipe', 'Envio de submissão com histórico de versões', 'Avaliação por rubrica, resultado público'];

/**
 * Layout compartilhado por login, registro, recuperação de senha etc.
 * (auth-layout.tsx aponta pra cá). Um arquivo só -- mudar aqui muda as
 * seis telas de autenticação de uma vez.
 *
 * O painel esquerdo é o único lugar do sistema com bloco sólido na cor
 * institucional -- em todo o resto o verde é só detalhe (anel de foco,
 * link). Aqui é um momento de marca deliberado, não decoração espalhada.
 */
export default function AuthSplitLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh lg:grid-cols-2">
            <div className="bg-verde-mata relative hidden flex-col justify-between overflow-hidden p-10 lg:flex">
                {/* Textura de fundo -- grade de pontos (motivo terminal) com um brilho
                    radial atrás do headline. Só CSS, sem imagem: a marca desse painel é
                    a cor institucional sólida, não uma foto de banco de imagem. */}
                <div
                    className="pointer-events-none absolute inset-0"
                    style={{
                        backgroundImage: 'radial-gradient(circle, rgba(255,255,255,0.14) 1px, transparent 1px)',
                        backgroundSize: '26px 26px',
                        maskImage: 'radial-gradient(ellipse 65% 55% at 30% 62%, black 35%, transparent 80%)',
                        WebkitMaskImage: 'radial-gradient(ellipse 65% 55% at 30% 62%, black 35%, transparent 80%)',
                    }}
                    aria-hidden="true"
                />
                <div
                    className="bg-verde-brilho pointer-events-none absolute top-[58%] left-[28%] size-[26rem] -translate-x-1/2 -translate-y-1/2 rounded-full opacity-20 blur-[110px]"
                    aria-hidden="true"
                />

                <Link href={route('home')} className="relative flex items-center gap-2 text-lg font-medium text-white">
                    <AppLogoIcon className="size-8 fill-current text-white" />
                    Hackathon IFPR
                </Link>

                <div className="relative flex flex-col gap-6">
                    <h2 className="max-w-md text-3xl font-bold tracking-tight text-balance text-white">
                        Inscrição, submissão e avaliação em um lugar só.
                    </h2>

                    <ul className="flex flex-col gap-2 text-sm text-white/70">
                        {destaques.map((item) => (
                            <li key={item} className="flex items-center gap-2">
                                <Check className="text-verde-brilho size-4 shrink-0" aria-hidden="true" />
                                {item}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>

            <div className="flex flex-col items-center justify-center gap-8 p-6 sm:p-10">
                <div className="flex w-full max-w-sm flex-col gap-8">
                    <Link href={route('home')} className="flex items-center justify-center gap-2 lg:hidden">
                        <AppLogoIcon className="size-9 fill-current" />
                    </Link>

                    <div className="flex flex-col gap-2 text-center">
                        <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
                        {description && <p className="text-muted-foreground text-sm text-balance">{description}</p>}
                    </div>

                    {children}
                </div>
            </div>
        </div>
    );
}
