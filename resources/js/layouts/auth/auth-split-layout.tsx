import { Link } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import RedeInterativa from '@/components/hackathon/rede-interativa';

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
 */
export default function AuthSplitLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="grid min-h-svh lg:grid-cols-2">
            <RedeInterativa className="bg-verde-mata relative hidden flex-col justify-between p-10 lg:flex">
                <Link href={route('home')} className="relative z-10 flex items-center gap-2 text-lg font-medium text-white">
                    <AppLogoIcon className="size-8 fill-current text-white" />
                    Hackathon IFPR
                </Link>

                <div className="relative z-10 flex flex-col gap-6">
                    <div>
                        <p className="text-verde-brilho font-mono text-sm">
                            <span aria-hidden="true">$ </span>hackathon --sobre
                        </p>
                        <h2 className="font-display mt-2 max-w-md text-3xl font-semibold tracking-tight text-balance text-white">
                            Inscrição, submissão e avaliação em um lugar só.
                        </h2>
                    </div>

                    <ul className="flex flex-col gap-2 font-mono text-sm text-white/70">
                        {destaques.map((item) => (
                            <li key={item} className="flex items-center gap-2">
                                <span className="text-verde-brilho" aria-hidden="true">
                                    [ok]
                                </span>
                                {item}
                            </li>
                        ))}
                    </ul>
                </div>
            </RedeInterativa>

            <div className="flex flex-col items-center justify-center gap-8 p-6 sm:p-10">
                <div className="flex w-full max-w-sm flex-col gap-8">
                    <Link href={route('home')} className="flex items-center justify-center gap-2 lg:hidden">
                        <AppLogoIcon className="size-9 fill-current" />
                    </Link>

                    <div className="flex flex-col gap-2 text-center">
                        <h1 className="font-display text-2xl font-semibold tracking-tight">{title}</h1>
                        {description && <p className="text-muted-foreground text-sm text-balance">{description}</p>}
                    </div>

                    {children}
                </div>
            </div>
        </div>
    );
}
