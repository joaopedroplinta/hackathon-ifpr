import { Check, Circle } from 'lucide-react';

type Props = {
    senha: string;
};

type Requisito = {
    chave: string;
    rotulo: string;
    atende: (senha: string) => boolean;
};

const requisitos: Requisito[] = [
    { chave: 'tamanho', rotulo: 'Pelo menos 8 caracteres', atende: (s) => s.length >= 8 },
    { chave: 'maiuscula', rotulo: 'Uma letra maiúscula', atende: (s) => /[A-Z]/.test(s) },
    { chave: 'minuscula', rotulo: 'Uma letra minúscula', atende: (s) => /[a-z]/.test(s) },
    { chave: 'simbolo', rotulo: 'Um símbolo (ex.: !@#$%)', atende: (s) => /[^A-Za-z0-9]/.test(s) },
];

/**
 * Mesma regra de `Password::defaults()` (AppServiceProvider::boot) --
 * atualize os dois juntos se a regra mudar, senão a lista promete uma
 * coisa e o servidor exige outra.
 *
 * Some sozinha quando os quatro requisitos são atendidos: nesse ponto ela
 * já cumpriu o papel (evitar erro só na hora de enviar) e continuar
 * ocupando espaço na tela é ruído. Ícone junto da cor, nunca só cor --
 * .claude/rules/frontend.md.
 */
export default function PasswordRequirements({ senha }: Props) {
    const faltaAlgum = requisitos.some((requisito) => !requisito.atende(senha));

    if (senha !== '' && !faltaAlgum) {
        return null;
    }

    return (
        <ul id="password-requisitos" className="mt-1 flex flex-col gap-1 text-xs">
            {requisitos.map((requisito) => {
                const atendido = requisito.atende(senha);

                return (
                    <li key={requisito.chave} className={`flex items-center gap-1.5 ${atendido ? 'text-verde-ifpr' : 'text-muted-foreground'}`}>
                        {atendido ? (
                            <Check className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        ) : (
                            <Circle className="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                        )}
                        {requisito.rotulo}
                    </li>
                );
            })}
        </ul>
    );
}
