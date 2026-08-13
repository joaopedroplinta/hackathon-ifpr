import { SVGAttributes } from 'react';

/**
 * Prompt de terminal (`>_`) -- PLANO.md §11. Glifo de cor única (fill-current)
 * de propósito: quem decide se vira "selo verde" ou "traço no cabeçalho" é
 * quem usa o componente, do mesmo jeito que o ícone antigo funcionava.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M6 5L15.5 12L6 19V15.2L10.8 12L6 8.8V5Z" />
            <rect x="16.5" y="16" width="5.5" height="2.4" rx="0.4" />
        </svg>
    );
}
