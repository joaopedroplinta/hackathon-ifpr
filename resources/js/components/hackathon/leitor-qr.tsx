import QrScanner from 'qr-scanner';
import { useEffect, useRef, useState } from 'react';

import { Button } from '@/components/ui/button';

type Props = {
    onDecode: (texto: string) => void;
};

type Estado = 'inicial' | 'abrindo' | 'lendo' | 'sem-contexto-seguro' | 'permissao-negada' | 'sem-camera';

/** Nome técnico do erro, mostrado pequeno pra o organizador poder reportar. */
function nomeDoErro(erro: unknown): string {
    return erro instanceof Error ? erro.name : String(erro);
}

export default function LeitorQr({ onDecode }: Props) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const scannerRef = useRef<QrScanner | null>(null);
    const montadoRef = useRef(true);
    const onDecodeRef = useRef(onDecode);
    onDecodeRef.current = onDecode;
    const [estado, setEstado] = useState<Estado>(() => (window.isSecureContext ? 'inicial' : 'sem-contexto-seguro'));
    const [detalhe, setDetalhe] = useState('');

    useEffect(() => {
        montadoRef.current = true;

        return () => {
            montadoRef.current = false;
            scannerRef.current?.destroy();
            scannerRef.current = null;
        };
    }, []);

    // Abrir a câmera só depois de um toque: o iOS derruba o pedido de
    // permissão feito sem gesto do usuário (ou quando algo rouba o foco).
    const abrirCamera = async () => {
        if (!videoRef.current) {
            return;
        }

        setEstado('abrindo');

        try {
            // iOS só lista as câmeras depois que a permissão é dada; sem este
            // pedido prévio o QrScanner enxerga zero câmeras ("Camera not found").
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            stream.getTracks().forEach((track) => track.stop());

            if (!montadoRef.current || !videoRef.current) {
                return;
            }

            const scanner = new QrScanner(
                videoRef.current,
                (resultado) => {
                    scanner.stop();
                    onDecodeRef.current(resultado.data);
                },
                { preferredCamera: 'environment', highlightScanRegion: true, highlightCodeOutline: true },
            );
            scannerRef.current = scanner;

            await scanner.start();
            if (montadoRef.current) {
                setEstado('lendo');
            }
        } catch (erro: unknown) {
            if (!montadoRef.current) {
                return;
            }

            console.error('leitor-qr', erro);
            setDetalhe(nomeDoErro(erro));
            setEstado(erro instanceof Error && erro.name === 'NotAllowedError' ? 'permissao-negada' : 'sem-camera');
        }
    };

    if (estado === 'sem-contexto-seguro') {
        return (
            <p className="text-muted-foreground bg-card rounded-2xl p-4 text-sm">
                A câmera só funciona em conexão segura (HTTPS). Use a busca por nome aqui embaixo.
            </p>
        );
    }

    if (estado === 'permissao-negada') {
        return (
            <p className="text-muted-foreground bg-card rounded-2xl p-4 text-sm">
                Permissão de câmera negada. Libere o acesso nas configurações do navegador ou use a busca por nome.
                {detalhe && <span className="mt-1 block text-xs opacity-70">Detalhe técnico: {detalhe}</span>}
            </p>
        );
    }

    if (estado === 'sem-camera') {
        return (
            <p className="text-muted-foreground bg-card rounded-2xl p-4 text-sm">
                Não foi possível abrir a câmera neste aparelho. Use a busca por nome aqui embaixo.
                {detalhe && <span className="mt-1 block text-xs opacity-70">Detalhe técnico: {detalhe}</span>}
            </p>
        );
    }

    return (
        <div className="relative w-full overflow-hidden rounded-xl bg-black">
            <video ref={videoRef} className="aspect-square w-full object-cover" muted playsInline />
            {estado === 'inicial' && (
                <div className="absolute inset-0 flex items-center justify-center bg-black/60">
                    <Button type="button" size="lg" onClick={abrirCamera}>
                        Abrir câmera
                    </Button>
                </div>
            )}
            {estado === 'abrindo' && (
                <div className="absolute inset-0 flex items-center justify-center bg-black/60 text-sm text-white">Abrindo câmera…</div>
            )}
        </div>
    );
}
