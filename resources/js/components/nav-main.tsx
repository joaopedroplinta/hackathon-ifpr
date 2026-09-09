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
            <SidebarGroupLabel className="text-sidebar-foreground/55 mb-1 px-3 text-[10px] font-semibold tracking-[0.16em] uppercase">
                {label}
            </SidebarGroupLabel>
            <SidebarMenu className="gap-1">
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={item === itemAtivo}
                            tooltip={item.title}
                            className="group/nav-item text-sidebar-foreground/75 after:bg-primary hover:bg-sidebar-accent hover:text-sidebar-accent-foreground data-[active=true]:bg-primary/10 data-[active=true]:text-primary relative h-11 rounded-xl px-3 transition-colors after:absolute after:top-1/2 after:left-0 after:h-5 after:w-0.5 after:-translate-y-1/2 after:scale-y-0 after:rounded-full after:transition-transform data-[active=true]:font-semibold data-[active=true]:after:scale-y-100"
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
