import { LoaderCircle } from 'lucide-react';
import { type ReactNode } from 'react';

import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

type ConfirmarAcaoProps = {
    trigger: ReactNode;
    titulo: string;
    descricao: ReactNode;
    textoConfirmar: string;
    onConfirmar: () => void;
    processando?: boolean;
    destrutiva?: boolean;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
};

/**
 * Confirmação acessível de ação sensível (desqualificar, remover, publicar
 * resultado…), compondo o Dialog existente -- foco, Escape e nome acessível
 * já vêm do Radix. Motivo/justificativa continua sendo campo da própria
 * tela quando o backend exige (ver .claude/rules/security.md, auditoria).
 */
export default function ConfirmarAcao({
    trigger,
    titulo,
    descricao,
    textoConfirmar,
    onConfirmar,
    processando = false,
    destrutiva = false,
    open,
    onOpenChange,
}: ConfirmarAcaoProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogTitle>{titulo}</DialogTitle>
                <DialogDescription>{descricao}</DialogDescription>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button type="button" variant="secondary" className="h-11">
                            Cancelar
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        variant={destrutiva ? 'destructive' : 'default'}
                        className="h-11"
                        disabled={processando}
                        onClick={onConfirmar}
                    >
                        {processando && <LoaderCircle className="size-4 animate-spin" aria-hidden="true" />}
                        {processando ? 'Processando…' : textoConfirmar}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
