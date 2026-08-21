import { Head, Link, router, useForm } from '@inertiajs/react';
import { motion, useReducedMotion, type Variants } from 'framer-motion';
import { LoaderCircle, Users as UsersIcon } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { Paginated } from '@/types';
import { FiltrosUsuario, LinhaUsuario, OpcaoPapel } from '@/types/usuarios';

interface Props {
    usuarios: Paginated<LinhaUsuario>;
    filtros: FiltrosUsuario;
    opcoes_papeis: OpcaoPapel[];
}

type PapeisForm = {
    roles: string[];
};

/** Um Dialog por linha -- cada um com seu próprio useForm, fechado por padrão. */
function EditarPapeis({ usuario, opcoesPapeis }: { usuario: LinhaUsuario; opcoesPapeis: OpcaoPapel[] }) {
    const [aberto, setAberto] = useState(false);
    const { data, setData, patch, processing, errors, reset } = useForm<PapeisForm>({ roles: usuario.papeis });

    const alternar = (valor: string, marcado: boolean) => {
        setData('roles', marcado ? [...data.roles, valor] : data.roles.filter((papel) => papel !== valor));
    };

    const salvar: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('admin.usuarios.update', usuario.id), {
            preserveScroll: true,
            onSuccess: () => setAberto(false),
        });
    };

    return (
        <Dialog
            open={aberto}
            onOpenChange={(valor) => {
                setAberto(valor);
                if (!valor) reset();
            }}
        >
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    Editar papéis
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Papéis de {usuario.nome}</DialogTitle>
                <DialogDescription>Papéis acumulam — marque quantos fizerem sentido para esta pessoa.</DialogDescription>

                <form onSubmit={salvar} className="flex flex-col gap-4">
                    <div className="flex flex-col gap-3">
                        {opcoesPapeis.map((papel) => {
                            const bloqueadoParaSiMesmo = usuario.sou_eu && papel.value === 'admin';

                            return (
                                <div key={papel.value} className="flex items-center gap-3">
                                    <Checkbox
                                        id={`papel-${usuario.id}-${papel.value}`}
                                        checked={data.roles.includes(papel.value)}
                                        disabled={bloqueadoParaSiMesmo}
                                        onCheckedChange={(marcado) => alternar(papel.value, marcado === true)}
                                    />
                                    <Label htmlFor={`papel-${usuario.id}-${papel.value}`}>
                                        {papel.label}
                                        {bloqueadoParaSiMesmo && (
                                            <span className="text-muted-foreground ml-1.5 text-xs">(você não pode remover de si mesmo)</span>
                                        )}
                                    </Label>
                                </div>
                            );
                        })}
                    </div>

                    <InputError message={errors.roles} />

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                            {processing ? 'Salvando…' : 'Salvar'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function ListaUsuarios({ usuarios, filtros, opcoes_papeis: opcoesPapeis }: Props) {
    const [busca, setBusca] = useState(filtros.busca ?? '');
    const reduzMovimento = useReducedMotion();

    const buscar: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(route('admin.usuarios.index'), busca !== '' ? { busca } : {}, { preserveState: true, preserveScroll: true, replace: true });
    };

    const fadeIn: Variants = {
        oculto: reduzMovimento ? {} : { opacity: 0, y: 10 },
        visivel: { opacity: 1, y: 0, transition: reduzMovimento ? { duration: 0 } : { duration: 0.4, ease: 'easeOut' } },
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Usuários', href: route('admin.usuarios.index') }]}>
            <Head title="Usuários" />

            <motion.div initial="oculto" animate="visivel" variants={fadeIn} className="mx-auto w-full max-w-5xl p-4 sm:p-6">
                <header className="mb-6">
                    <h1 className="text-2xl font-bold tracking-tight">Usuários</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Conceder ou remover papel (jurado, organizador, admin). Papéis acumulam — ver PLANO.md §3.
                    </p>
                </header>

                <form onSubmit={buscar} className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div className="flex-1">
                        <Label htmlFor="busca">Buscar por nome ou e-mail</Label>
                        <Input
                            id="busca"
                            value={busca}
                            onChange={(e) => setBusca(e.target.value)}
                            placeholder="Ex.: joao@ifpr.edu.br"
                            className="mt-1"
                        />
                    </div>
                    <Button type="submit" className="h-10">
                        Buscar
                    </Button>
                    {filtros.busca && (
                        <Button
                            type="button"
                            variant="ghost"
                            className="h-10"
                            onClick={() => {
                                setBusca('');
                                router.get(route('admin.usuarios.index'));
                            }}
                        >
                            Limpar
                        </Button>
                    )}
                </form>

                {usuarios.data.length === 0 ? (
                    <div className="border-border bg-card flex flex-col items-center gap-3 rounded-xl border p-10 text-center">
                        <span className="bg-muted flex size-11 items-center justify-center rounded-full">
                            <UsersIcon className="text-muted-foreground size-5" aria-hidden="true" />
                        </span>
                        <p className="font-semibold">Nenhum usuário encontrado</p>
                        <p className="text-muted-foreground text-sm">Tente outro nome ou e-mail, ou limpe a busca.</p>
                    </div>
                ) : (
                    <div className="border-border bg-card overflow-x-auto rounded-xl border">
                        <table className="w-full min-w-[40rem] text-sm">
                            <caption className="sr-only">Usuários e seus papéis</caption>
                            <thead className="bg-muted/50 text-left">
                                <tr>
                                    <th scope="col" className="p-3 text-xs font-semibold tracking-wide uppercase">
                                        Nome
                                    </th>
                                    <th scope="col" className="p-3 text-xs font-semibold tracking-wide uppercase">
                                        Papéis
                                    </th>
                                    <th scope="col" className="p-3 text-xs font-semibold tracking-wide uppercase">
                                        <span className="sr-only">Ações</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {usuarios.data.map((usuario) => (
                                    <tr key={usuario.id} className="border-border border-t">
                                        <td className="p-3">
                                            <p className="font-semibold">
                                                {usuario.nome}
                                                {usuario.sou_eu && <span className="text-muted-foreground ml-1.5 text-xs font-normal">(você)</span>}
                                            </p>
                                            <p className="text-muted-foreground text-xs">{usuario.email}</p>
                                        </td>
                                        <td className="p-3">
                                            {usuario.papeis.length === 0 ? (
                                                <span className="text-muted-foreground text-xs">Participante</span>
                                            ) : (
                                                <div className="flex flex-wrap gap-1.5">
                                                    {usuario.papeis.map((papel) => (
                                                        <span
                                                            key={papel}
                                                            className="bg-primary/10 text-primary inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                                        >
                                                            {opcoesPapeis.find((opcao) => opcao.value === papel)?.label ?? papel}
                                                        </span>
                                                    ))}
                                                </div>
                                            )}
                                        </td>
                                        <td className="p-3 text-right">
                                            <EditarPapeis usuario={usuario} opcoesPapeis={opcoesPapeis} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {usuarios.last_page > 1 && (
                    <nav aria-label="Paginação" className="mt-4 flex flex-wrap gap-1">
                        {usuarios.links.map((link, i) =>
                            link.url ? (
                                <Link
                                    key={i}
                                    href={link.url}
                                    preserveScroll
                                    aria-current={link.active ? 'page' : undefined}
                                    className={`rounded-full px-3 py-2 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'bg-card hover:bg-muted'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ) : (
                                <span
                                    key={i}
                                    className="text-muted-foreground bg-card rounded-full px-3 py-2 text-sm opacity-50"
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ),
                        )}
                    </nav>
                )}
            </motion.div>
        </AppLayout>
    );
}
