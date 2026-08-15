import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { Button } from '@/components/ui/button';

const NOME_COOKIE = 'cookie_consent';
const UM_ANO_EM_SEGUNDOS = 60 * 60 * 24 * 365;

function lerCookie(nome: string): string | null {
    const linha = document.cookie.split('; ').find((item) => item.startsWith(`${nome}=`));
    return linha ? decodeURIComponent(linha.split('=')[1]) : null;
}

function gravarCookie(nome: string, valor: string): void {
    document.cookie = `${nome}=${encodeURIComponent(valor)}; max-age=${UM_ANO_EM_SEGUNDOS}; path=/; samesite=lax`;
}

/**
 * Cookie de sessão e CSRF token são estritamente necessários e continuam
 * ativos independente da resposta -- o aviso existe por transparência
 * (LGPD art. 9º), não porque algo pode ser bloqueado (issue #73).
 */
export default function AvisoCookies() {
    const [visivel, setVisivel] = useState(false);

    useEffect(() => {
        setVisivel(lerCookie(NOME_COOKIE) === null);
    }, []);

    if (!visivel) {
        return null;
    }

    const responder = (valor: 'aceito' | 'recusado') => {
        gravarCookie(NOME_COOKIE, valor);
        setVisivel(false);
    };

    return (
        <div role="region" aria-label="Aviso de cookies" className="bg-background fixed inset-x-0 bottom-0 z-50 border-t p-4 sm:p-6">
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-muted-foreground text-sm">
                    Usamos cookies essenciais para manter você conectado e proteger o sistema.{' '}
                    <Link href={route('cookies.show')} className="text-foreground underline underline-offset-2">
                        Saiba quais
                    </Link>
                    .
                </p>
                <div className="flex shrink-0 gap-2">
                    <Button variant="outline" size="sm" onClick={() => responder('recusado')}>
                        Recusar
                    </Button>
                    <Button size="sm" onClick={() => responder('aceito')}>
                        Aceitar
                    </Button>
                </div>
            </div>
        </div>
    );
}
