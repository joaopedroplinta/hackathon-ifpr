import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import FlashMessages from '@/components/flash-messages';
import { type BreadcrumbItem } from '@/types';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: { children: React.ReactNode; breadcrumbs?: BreadcrumbItem[] }) {
    return (
        <AppShell variant="sidebar">
            <a href="#conteudo" className="skip-link">
                Pular para o conteúdo
            </a>
            <AppSidebar />
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <FlashMessages />
                <div id="conteudo" tabIndex={-1} className="min-w-0 flex-1 outline-none">
                    {children}
                </div>
            </AppContent>
        </AppShell>
    );
}
