import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    Award,
    BookOpen,
    CalendarDays,
    ClipboardCheck,
    ClipboardList,
    FileText,
    Folder,
    Globe,
    LayoutGrid,
    QrCode,
    Scale,
    ScanLine,
    Settings,
    Trophy,
    Users,
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Início',
        url: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Crachá',
        url: '/credencial',
        icon: QrCode,
    },
    {
        title: 'Certificados',
        url: '/certificados',
        icon: Award,
    },
];

/** Só aparece para quem tem o papel de jurado. O acesso em si é da Policy. */
const judgeNavItems: NavItem[] = [
    {
        title: 'Avaliar',
        url: '/jurado',
        icon: ClipboardCheck,
    },
];

/** Só aparece para organizador e admin. O acesso em si é da Policy. */
const staffNavItems: NavItem[] = [
    {
        title: 'Painel',
        url: '/painel',
        icon: LayoutGrid,
    },
    {
        title: 'Evento',
        url: '/painel/evento',
        icon: Settings,
    },
    {
        title: 'Submissões',
        url: '/painel/submissoes',
        icon: FileText,
    },
    {
        title: 'Agenda',
        url: '/painel/agenda',
        icon: CalendarDays,
    },
    {
        title: 'Check-in',
        url: '/painel/checkin',
        icon: ScanLine,
    },
    {
        title: 'Incidentes',
        url: '/painel/incidentes',
        icon: AlertTriangle,
    },
    {
        title: 'Rubrica',
        url: '/painel/rubrica',
        icon: ClipboardList,
    },
    {
        title: 'Jurados',
        url: '/painel/jurados',
        icon: Scale,
    },
    {
        title: 'Resultados',
        url: '/painel/resultados',
        icon: Trophy,
    },
    {
        // "Certificados" sozinho colidiria com o item de mainNavItems (o
        // participante vê os dois quando também é staff) -- NavMain usa o
        // título como key, então precisa ser distinto de verdade, não só
        // a URL.
        title: 'Emitir certificados',
        url: '/painel/certificados',
        icon: Award,
    },
];

/** Só aparece para admin. Papel é exclusivo de admin, não de todo staff -- PLANO.md §3. */
const adminNavItems: NavItem[] = [
    {
        title: 'Usuários',
        url: '/admin/usuarios',
        icon: Users,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Site público',
        url: '/',
        icon: Globe,
    },
    {
        title: 'Repositório',
        url: 'https://github.com/joaopedroplinta/hackathon-ifpr',
        icon: Folder,
    },
    {
        title: 'Regulamento',
        url: '/regulamento',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth, app_version } = usePage<SharedData>().props;
    const navItems = [
        ...mainNavItems,
        ...(auth?.is_judge ? judgeNavItems : []),
        ...(auth?.is_staff ? staffNavItems : []),
        ...(auth?.is_admin ? adminNavItems : []),
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
                <span className="px-2 pb-1 text-xs text-neutral-400 group-data-[collapsible=icon]:hidden dark:text-neutral-600">{app_version}</span>
            </SidebarFooter>
        </Sidebar>
    );
}
