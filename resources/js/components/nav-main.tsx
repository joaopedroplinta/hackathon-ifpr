import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

/**
 * Página de detalhe (ex.: /painel/submissoes/52) não batia com nenhum item
 * do menu -- comparação era só ===, então nada ficava marcado como "você
 * está aqui" fora da própria página de listagem. Casa pelo prefixo mais
 * específico entre os itens do menu, não só o primeiro que combina: sem
 * isso, "/painel" (Painel) e "/painel/evento" (Evento) ficariam ativos ao
 * mesmo tempo em /painel/evento.
 */
function itemMaisEspecifico(items: NavItem[], urlAtual: string): NavItem | null {
    return items.reduce<NavItem | null>((melhor, item) => {
        const combina = item.url === urlAtual || urlAtual.startsWith(`${item.url}/`);
        if (!combina) return melhor;

        return !melhor || item.url.length > melhor.url.length ? item : melhor;
    }, null);
}

export function NavMain({ items = [], label = 'Navegação' }: { items: NavItem[]; label?: string }) {
    const page = usePage();
    // Filtro de listagem (?status=...) não pode derrubar o destaque do item
    // de menu correspondente -- só o caminho importa aqui, não a query string.
    const itemAtivo = itemMaisEspecifico(items, page.url.split('?')[0]);

    return (
        <SidebarGroup className="px-3 py-2">
            <SidebarGroupLabel className="text-muted-foreground mb-1 text-[11px] font-semibold tracking-widest uppercase">{label}</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={item === itemAtivo}
                            tooltip={item.title}
                            className="data-[active=true]:bg-primary/10 data-[active=true]:text-primary h-11 rounded-lg px-3 data-[active=true]:font-semibold"
                        >
                            <Link href={item.url} prefetch aria-current={item === itemAtivo ? 'page' : undefined}>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
