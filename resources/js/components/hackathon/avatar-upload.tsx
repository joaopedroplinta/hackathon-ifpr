import { router } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useRef, useState } from 'react';

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useInitials } from '@/hooks/use-initials';

type Props = {
    nome: string;
    avatarUrl: string | null;
};

/**
 * Envia sozinho ao escolher o arquivo -- sem botão "salvar" separado do
 * resto do formulário de perfil, porque é outra rota (upload de arquivo
 * precisa de `forceFormData`, o form de nome/e-mail não).
 */
export default function AvatarUpload({ nome, avatarUrl }: Props) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [enviando, setEnviando] = useState(false);
    const [removendo, setRemovendo] = useState(false);
    const getInitials = useInitials();

    const enviar = (arquivo: File) => {
        setEnviando(true);
        router.post(
            route('profile.avatar.update'),
            { foto: arquivo },
            {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => {
                    setEnviando(false);
                    if (inputRef.current) inputRef.current.value = '';
                },
            },
        );
    };

    const remover = () => {
        setRemovendo(true);
        router.delete(route('profile.avatar.destroy'), {
            preserveScroll: true,
            onFinish: () => setRemovendo(false),
        });
    };

    return (
        <div className="flex items-center gap-4">
            <Avatar className="size-16">
                <AvatarImage src={avatarUrl ?? undefined} alt={nome} />
                <AvatarFallback className="text-lg">{getInitials(nome)}</AvatarFallback>
            </Avatar>

            <div className="flex flex-col gap-2">
                <Label htmlFor="avatar-input" className="sr-only">
                    Foto de perfil
                </Label>
                <input
                    ref={inputRef}
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    className="sr-only"
                    id="avatar-input"
                    onChange={(e) => {
                        const arquivo = e.target.files?.[0];
                        if (arquivo) enviar(arquivo);
                    }}
                />
                <div className="flex gap-2">
                    <Button type="button" variant="outline" size="sm" disabled={enviando} onClick={() => inputRef.current?.click()}>
                        {enviando && <LoaderCircle className="size-4 animate-spin" aria-hidden="true" />}
                        {enviando ? 'Enviando…' : 'Trocar foto'}
                    </Button>
                    {avatarUrl && (
                        <Button type="button" variant="ghost" size="sm" disabled={removendo} onClick={remover}>
                            Remover
                        </Button>
                    )}
                </div>
                <p className="text-muted-foreground text-xs">PNG, JPG ou WEBP, até 3 MB.</p>
            </div>
        </div>
    );
}
