import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useRef } from 'react';

// Components...
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

import CampoSenha from '@/components/hackathon/campo-senha';
import SecaoFormulario from '@/components/hackathon/secao-formulario';

import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';

export default function DeleteUser() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const { data, setData, delete: destroy, processing, reset, errors, clearErrors } = useForm({ password: '' });

    const deleteUser: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        clearErrors();
        reset();
    };

    return (
        <SecaoFormulario titulo="Excluir conta" instrucao="Exclua permanentemente sua conta e todos os seus dados.">
            <div className="border-destructive/30 bg-destructive/10 space-y-4 rounded-xl border p-5" role="alert">
                <div className="text-destructive space-y-1">
                    <p className="font-semibold">Esta ação não pode ser desfeita.</p>
                    <p className="text-sm leading-relaxed">Depois da exclusão, seus dados não poderão ser recuperados.</p>
                </div>

                <Dialog>
                    <DialogTrigger asChild>
                        <Button variant="destructive" className="min-h-11">
                            Excluir conta
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogTitle>Tem certeza que quer excluir sua conta?</DialogTitle>
                        <DialogDescription>
                            Depois de excluída, todos os dados da sua conta são apagados permanentemente. Digite sua senha para confirmar que quer
                            excluir a conta.
                        </DialogDescription>
                        <form className="space-y-6" onSubmit={deleteUser}>
                            <div className="grid gap-2">
                                <Label htmlFor="password" className="sr-only">
                                    Senha
                                </Label>

                                <CampoSenha
                                    id="password"
                                    name="password"
                                    ref={passwordInput}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Senha"
                                    autoComplete="current-password"
                                />

                                <InputError message={errors.password} />
                            </div>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button variant="secondary" onClick={closeModal}>
                                        Cancelar
                                    </Button>
                                </DialogClose>

                                <Button variant="destructive" disabled={processing} asChild>
                                    <button type="submit">
                                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" aria-hidden="true" />}
                                        {processing ? 'Excluindo…' : 'Excluir conta'}
                                    </button>
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </SecaoFormulario>
    );
}
