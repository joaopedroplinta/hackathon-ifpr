import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, CalendarDays, ClipboardCheck, ClipboardList, FileText, Folder, LayoutGrid, QrCode, Scale, ScanLine } from 'lucide-react';
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
        title: 'Submissões',
        url: '/admin/submissoes',
        icon: FileText,
    },
    {
        title: 'Agenda',
        url: '/admin/agenda',
        icon: CalendarDays,
    },
    {
        title: 'Check-in',
        url: '/admin/checkin',
        icon: ScanLine,
    },
    {
        title: 'Rubrica',
        url: '/admin/rubrica',
        icon: ClipboardList,
    },
    {
        title: 'Jurados',
        url: '/admin/jurados',
        icon: Scale,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repositório',
        url: 'https://github.com/joaopedroplinta/hackathon-ifpr',
        icon: Folder,
    },
    {
        title: 'Regulamento',
        url: '#',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const navItems = [...mainNavItems, ...(auth?.is_judge ? judgeNavItems : []), ...(auth?.is_staff ? staffNavItems : [])];

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
            </SidebarFooter>
        </Sidebar>
    );
}
