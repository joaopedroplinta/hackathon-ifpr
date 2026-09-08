import { Link, usePage } from '@inertiajs/react';
import { KeyRound, Palette, UserRound } from 'lucide-react';

import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Perfil',
        url: '/settings/profile',
        icon: UserRound,
    },
    {
        title: 'Senha',
        url: '/settings/password',
        icon: KeyRound,
    },
    {
        title: 'Aparência',
        url: '/settings/appearance',
        icon: Palette,
    },
];

export default function SettingsLayout({ children }: { children: React.ReactNode }) {
    const { url } = usePage();
    const currentPath = url.split('?')[0];

    return (
        <div className="flex flex-col gap-8 p-4 pb-12 sm:p-6 sm:pb-14 lg:p-8 lg:pb-16">
            <header className="border-border border-b pb-6">
                <p className="text-primary mb-2 text-xs font-semibold tracking-widest uppercase">Sua conta</p>
                <h1 className="text-3xl font-bold tracking-tight">Configurações</h1>
                <p className="text-muted-foreground mt-2 text-sm">Gerencie seus dados, segurança e preferências de aparência.</p>
            </header>

            <div className="grid items-start gap-8 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-12">
                <aside className="border-border bg-card rounded-2xl border p-3 lg:sticky lg:top-28">
                    <nav aria-label="Configurações" className="grid gap-1 sm:grid-cols-3 lg:flex lg:flex-col">
                        {sidebarNavItems.map((item) => (
                            <Link
                                key={item.url}
                                href={item.url}
                                prefetch
                                aria-current={currentPath === item.url ? 'page' : undefined}
                                className={cn(
                                    'flex min-h-11 items-center gap-3 rounded-xl px-3 text-sm font-medium transition-colors',
                                    currentPath === item.url
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                                )}
                            >
                                {item.icon && <item.icon className="size-4 shrink-0" aria-hidden="true" />}
                                {item.title}
                            </Link>
                        ))}
                    </nav>
                </aside>
                <div className="max-w-3xl min-w-0">{children}</div>
            </div>
        </div>
    );
}
