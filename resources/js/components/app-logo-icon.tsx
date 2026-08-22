import { SVGAttributes } from 'react';

/**
 * "Encontro": dois quadrados arredondados se sobrepondo -- duas equipes
 * convergindo pra um resultado só. Substitui o prompt de terminal (`>_`)
 * da primeira identidade, reprovado em revisão de UX por ler como template
 * genérico de IA (PLANO.md §11). Glifo de cor única (fill-current) de
 * propósito: quem decide se vira "selo verde" ou "traço no cabeçalho" é
 * quem usa o componente, do mesmo jeito que o ícone antigo funcionava.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <rect x="3" y="3" width="12" height="12" rx="4" />
            <rect x="9" y="9" width="12" height="12" rx="4" />
        </svg>
    );
}
