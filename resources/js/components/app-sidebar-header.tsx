import AppearanceToggleDropdown from '@/components/appearance-dropdown';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
    return (
        <header className="border-border bg-background/95 sticky top-0 z-30 flex min-h-16 shrink-0 items-center gap-2 border-b px-4 backdrop-blur-md sm:px-6">
            <div className="flex flex-1 items-center gap-2">
                <SidebarTrigger className="-ml-1 size-11" aria-label="Alternar navegação" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <AppearanceToggleDropdown />
        </header>
    );
}
