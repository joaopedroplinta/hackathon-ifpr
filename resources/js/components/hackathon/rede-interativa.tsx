import * as React from 'react';

import { cn } from '@/lib/utils';

interface RedeInterativaProps extends React.HTMLAttributes<HTMLDivElement> {
    corNo?: string;
    corPulso?: string;
    corConexao?: string;
    quantidadeNos?: number;
    raioConexao?: number;
}

/**
 * Rede de nós num canvas, acende perto do cursor e manda pulsos entre
 * conexões -- adaptado de um componente do catálogo 21st.dev
 * (dhileepkumargm/interactive-synapse-network). Diferenças do original:
 *
 * - Tamanho segue o container (ResizeObserver), não a janela inteira -- o
 *   original era pensado pra ocupar a tela toda, aqui é só o hero.
 * - Sem o "rastro" pintando um retângulo translúcido a cada frame: aquilo
 *   fixava uma cor de fundo escura, quebrava o tema claro. Troquei por
 *   clearRect puro -- perde o motion-blur, ganha funcionar nos dois temas.
 * - `respeitaMovimentoReduzido`: para de vez o rAF se o SO pede menos
 *   animação, não só deixa mais devagar.
 * - Sem `role="img"`/`aria-label` no wrapper -- o original tratava tudo
 *   dentro como decoração, mas aqui os filhos são o hero de verdade
 *   (título, botões, links), não podem sumir da árvore de acessibilidade.
 */
export function RedeInterativa({
    children,
    className,
    corNo = 'rgba(143,209,79,0.85)',
    corPulso = 'rgba(230,255,210,1)',
    corConexao = '143,209,79',
    quantidadeNos = 42,
    raioConexao = 160,
    ...props
}: RedeInterativaProps) {
    const containerRef = React.useRef<HTMLDivElement>(null);
    const canvasRef = React.useRef<HTMLCanvasElement>(null);

    React.useEffect(() => {
        const container = containerRef.current;
        const canvas = canvasRef.current;
        if (!container || !canvas) return;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const respeitaMovimentoReduzido = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        let width = 0;
        let height = 0;
        const mouse = { x: -9999, y: -9999 };
        let rafId = 0;

        class Pulso {
            progresso = 0;
            velocidade = 0.025;

            constructor(
                public origem: No,
                public destino: No,
            ) {}

            atualizar() {
                this.progresso += this.velocidade;
            }

            desenhar() {
                const x = this.origem.x + (this.destino.x - this.origem.x) * this.progresso;
                const y = this.origem.y + (this.destino.y - this.origem.y) * this.progresso;
                ctx!.beginPath();
                ctx!.arc(x, y, 2.5, 0, Math.PI * 2);
                ctx!.fillStyle = corPulso;
                ctx!.fill();
            }
        }

        class No {
            x = Math.random() * width;
            y = Math.random() * height;
            vx = (Math.random() - 0.5) * 0.4;
            vy = (Math.random() - 0.5) * 0.4;
            raio = Math.random() * 1.5 + 1.5;
            conexoes: No[] = [];
            pulsos: Pulso[] = [];
            ativacao = 0;

            atualizar() {
                this.x += this.vx;
                this.y += this.vy;

                if (this.x < 0 || this.x > width) this.vx *= -1;
                if (this.y < 0 || this.y > height) this.vy *= -1;

                const dx = this.x - mouse.x;
                const dy = this.y - mouse.y;
                const dist = Math.hypot(dx, dy);
                const alvo = Math.max(0, 1 - dist / (raioConexao * 0.8));
                this.ativacao += (alvo - this.ativacao) * 0.1;

                if (this.ativacao > 0.5 && Math.random() > 0.985 && this.conexoes.length > 0) {
                    const destino = this.conexoes[Math.floor(Math.random() * this.conexoes.length)];
                    this.pulsos.push(new Pulso(this, destino));
                }

                this.pulsos = this.pulsos.filter((p) => p.progresso < 1);
                this.pulsos.forEach((p) => p.atualizar());
            }

            desenhar() {
                ctx!.beginPath();
                ctx!.arc(this.x, this.y, this.raio, 0, Math.PI * 2);
                const alfa = Math.max(0.25, this.ativacao);
                ctx!.fillStyle = corNo.replace(/[^,]+(?=\))/, alfa.toString());
                ctx!.fill();
                this.pulsos.forEach((p) => p.desenhar());
            }
        }

        let nos: No[] = [];

        const construirRede = () => {
            nos = Array.from({ length: quantidadeNos }, () => new No());
            nos.forEach((n1) => {
                nos.forEach((n2) => {
                    if (n1 !== n2 && Math.hypot(n1.x - n2.x, n1.y - n2.y) < raioConexao) {
                        n1.conexoes.push(n2);
                    }
                });
            });
        };

        const ajustarTamanho = () => {
            width = canvas.width = container.clientWidth;
            height = canvas.height = container.clientHeight;
            construirRede();
        };

        ajustarTamanho();

        const onMouseMove = (e: MouseEvent) => {
            const rect = container.getBoundingClientRect();
            mouse.x = e.clientX - rect.left;
            mouse.y = e.clientY - rect.top;
        };
        const onMouseLeave = () => {
            mouse.x = -9999;
            mouse.y = -9999;
        };

        container.addEventListener('mousemove', onMouseMove);
        container.addEventListener('mouseleave', onMouseLeave);

        const resizeObserver = new ResizeObserver(ajustarTamanho);
        resizeObserver.observe(container);

        const desenharFrame = () => {
            ctx.clearRect(0, 0, width, height);

            nos.forEach((n1) => {
                n1.conexoes.forEach((n2) => {
                    const a = Math.max(0.04, n1.ativacao, n2.ativacao) * 0.25;
                    ctx.beginPath();
                    ctx.moveTo(n1.x, n1.y);
                    ctx.lineTo(n2.x, n2.y);
                    ctx.strokeStyle = `rgba(${corConexao},${a})`;
                    ctx.stroke();
                });
            });

            nos.forEach((n) => {
                n.atualizar();
                n.desenhar();
            });
        };

        if (respeitaMovimentoReduzido) {
            desenharFrame();
        } else {
            const animar = () => {
                desenharFrame();
                rafId = requestAnimationFrame(animar);
            };
            animar();
        }

        return () => {
            cancelAnimationFrame(rafId);
            container.removeEventListener('mousemove', onMouseMove);
            container.removeEventListener('mouseleave', onMouseLeave);
            resizeObserver.disconnect();
        };
    }, [corNo, corPulso, corConexao, quantidadeNos, raioConexao]);

    return (
        <div ref={containerRef} className={cn('relative overflow-hidden', className)} {...props}>
            <canvas ref={canvasRef} aria-hidden="true" className="pointer-events-none absolute inset-0 h-full w-full" />
            {children}
        </div>
    );
}

export default RedeInterativa;
