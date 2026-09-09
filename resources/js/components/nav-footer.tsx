import { Icon } from '@/components/icon';
import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';

export function NavFooter({
    items,
    className,
    ...props
}: React.ComponentPropsWithoutRef<typeof SidebarGroup> & {
    items: NavItem[];
}) {
    return (
        <SidebarGroup {...props} className={`group-data-[collapsible=icon]:p-0 ${className || ''}`}>
            <SidebarGroupContent>
                <SidebarMenu>
                    {items.map((item) => (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                className="text-sidebar-foreground/60 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground h-10 rounded-xl"
                            >
                                {/* Link interno usa <Link> pra não recarregar a página inteira; só o
                                    que sai do domínio (repositório) segue como <a target="_blank">
                                    -- .claude/rules/frontend.md. */}
                                {item.url.startsWith('http') ? (
                                    <a href={item.url} target="_blank" rel="noopener noreferrer">
                                        {item.icon && <Icon iconNode={item.icon} className="h-5 w-5" />}
                                        <span>{item.title}</span>
                                    </a>
                                ) : (
                                    <Link href={item.url}>
                                        {item.icon && <Icon iconNode={item.icon} className="h-5 w-5" />}
                                        <span>{item.title}</span>
                                    </Link>
                                )}
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
