import AppearanceToggleDropdown from '@/components/appearance-dropdown';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    return (
        <header className="border-border/80 bg-background/90 sticky top-0 z-30 flex min-h-16 shrink-0 items-center gap-2 border-b px-4 backdrop-blur-xl sm:px-6 lg:px-8">
            <div className="flex flex-1 items-center gap-2">
                <SidebarTrigger className="hover:bg-muted -ml-1 size-10 rounded-xl" aria-label="Alternar navegação" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <AppearanceToggleDropdown />
        </header>
    );
}
