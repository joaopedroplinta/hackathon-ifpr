import QrScanner from 'qr-scanner';
import { useEffect, useRef, useState } from 'react';

type Props = {
    onDecode: (texto: string) => void;
};

type Estado = 'abrindo' | 'lendo' | 'sem-contexto-seguro' | 'permissao-negada' | 'sem-camera';

/** Nome técnico do erro, mostrado pequeno pra o organizador poder reportar. */
function nomeDoErro(erro: unknown): string {
    return erro instanceof Error ? erro.name : String(erro);
}

export default function LeitorQr({ onDecode }: Props) {
    const videoRef = useRef<HTMLVideoElement>(null);
    const onDecodeRef = useRef(onDecode);
    onDecodeRef.current = onDecode;
    const [estado, setEstado] = useState<Estado>('abrindo');
    const [detalhe, setDetalhe] = useState('');

    useEffect(() => {
        // getUserMedia só existe em HTTPS ou localhost -- em rede local via
        // IP puro (http://192.168.x.x) o navegador nem oferece a API.
        if (!window.isSecureContext || !videoRef.current) {
            setEstado('sem-contexto-seguro');
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

        let cancelado = false;

        // iOS só lista as câmeras depois que a permissão é dada; sem este
        // pedido prévio o QrScanner enxerga zero câmeras ("Camera not found").
        const pedirPermissao = async () => {
            const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
            stream.getTracks().forEach((track) => track.stop());
        };

        pedirPermissao()
            .then(() => (cancelado ? undefined : scanner.start()))
            .then(() => (cancelado ? undefined : setEstado('lendo')))
            .catch((erro: unknown) => {
                if (cancelado) {
                    return;
                }

                console.error('leitor-qr', erro);
                setDetalhe(nomeDoErro(erro));
                setEstado(erro instanceof Error && erro.name === 'NotAllowedError' ? 'permissao-negada' : 'sem-camera');
            });

        return () => {
            cancelado = true;
            scanner.stop();
            scanner.destroy();
        };
    }, []);

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
            {estado === 'abrindo' && (
                <div className="absolute inset-0 flex items-center justify-center bg-black/60 text-sm text-white">Abrindo câmera…</div>
            )}
        </div>
    );
}
