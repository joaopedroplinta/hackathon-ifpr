import * as React from 'react';

import { cn } from '@/lib/utils';

interface FundoCircuitoProps extends React.HTMLAttributes<HTMLDivElement> {
    opacidade?: number;
}

/**
 * Placas de circuito repetindo em SVG por trás do conteúdo -- adaptado de
 * um componente do catálogo 21st.dev (thegridcn/circuit-background), com a
 * animação convertida de styled-jsx (Next.js) pra uma tag <style> comum e a
 * cor amarrada no verde-brilho, a mesma assinatura do log de build.
 */
export function FundoCircuito({ children, className, opacidade = 0.14, ...props }: FundoCircuitoProps) {
    return (
        <div className={cn('relative overflow-hidden', className)} {...props}>
            <svg
                className="fundo-circuito-svg text-verde-brilho pointer-events-none absolute inset-0 h-full w-full"
                style={{ opacity: opacidade }}
                xmlns="http://www.w3.org/2000/svg"
                aria-hidden="true"
            >
                <defs>
                    <pattern id="padrao-circuito" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
                        <path d="M0 50 H30 M70 50 H100" stroke="currentColor" strokeWidth="1" fill="none" />
                        <path d="M50 0 V30 M50 70 V100" stroke="currentColor" strokeWidth="1" fill="none" />
                        <circle cx="50" cy="50" r="4" fill="none" stroke="currentColor" strokeWidth="1" />
                        <circle cx="0" cy="0" r="2" fill="currentColor" />
                        <circle cx="100" cy="0" r="2" fill="currentColor" />
                        <circle cx="0" cy="100" r="2" fill="currentColor" />
                        <circle cx="100" cy="100" r="2" fill="currentColor" />
                        <path d="M30 50 L50 30 M50 70 L70 50" stroke="currentColor" strokeWidth="1" fill="none" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#padrao-circuito)" />
            </svg>

            {children}

            <style>{`
                @keyframes circuito-flow {
                    0% { stroke-dashoffset: 0; }
                    100% { stroke-dashoffset: 200; }
                }
                .fundo-circuito-svg path {
                    stroke-dasharray: 10 5;
                    animation: circuito-flow 12s linear infinite;
                }
                @media (prefers-reduced-motion: reduce) {
                    .fundo-circuito-svg path { animation: none; }
                }
            `}</style>
        </div>
    );
}

export default FundoCircuito;
