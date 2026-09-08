import { Eye, EyeOff } from 'lucide-react';
import * as React from 'react';

import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type CampoSenhaProps = Omit<React.ComponentProps<'input'>, 'type'>;

/** Campo de senha com revelação local: evita erro de digitação sem guardar o valor fora do formulário. */
const CampoSenha = React.forwardRef<HTMLInputElement, CampoSenhaProps>(({ className, disabled, ...props }, ref) => {
    const [visivel, setVisivel] = React.useState(false);

    return (
        <div className="relative">
            <Input ref={ref} type={visivel ? 'text' : 'password'} disabled={disabled} className={cn('pr-11', className)} {...props} />
            <button
                type="button"
                aria-label={visivel ? 'Ocultar senha' : 'Mostrar senha'}
                aria-pressed={visivel}
                disabled={disabled}
                onClick={() => setVisivel((atual) => !atual)}
                className="text-muted-foreground hover:text-foreground focus-visible:ring-ring absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
            >
                {visivel ? <EyeOff className="size-4" aria-hidden="true" /> : <Eye className="size-4" aria-hidden="true" />}
            </button>
        </div>
    );
});

CampoSenha.displayName = 'CampoSenha';

export default CampoSenha;
