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
        <div className="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 pt-6 pb-14 sm:px-6 sm:pt-8 lg:px-8 lg:pt-10 lg:pb-20">
            <header className="border-border grid gap-5 border-b pb-7 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end">
                <div>
                    <h1 className="text-3xl leading-tight font-bold tracking-[-0.035em] sm:text-4xl">Sua conta</h1>
                    <p className="text-muted-foreground mt-2 max-w-2xl text-sm leading-6">
                        Mantenha seus dados atualizados para participar do evento e emitir seus certificados corretamente.
                    </p>
                </div>
                <p className="border-border text-muted-foreground hidden border-l pl-5 text-right text-xs leading-5 sm:block">
                    Perfil
                    <br />
                    Segurança e aparência
                </p>
            </header>

            <div className="grid items-start gap-7 lg:grid-cols-[14rem_minmax(0,1fr)] lg:gap-12">
                <aside className="lg:sticky lg:top-24">
                    <nav
                        aria-label="Configurações"
                        className="border-border bg-muted/40 grid gap-1 rounded-2xl border p-1.5 sm:grid-cols-3 lg:flex lg:flex-col"
                    >
                        {sidebarNavItems.map((item) => (
                            <Link
                                key={item.url}
                                href={item.url}
                                prefetch
                                aria-current={currentPath === item.url ? 'page' : undefined}
                                className={cn(
                                    'focus-visible:ring-ring flex min-h-11 items-center gap-3 rounded-xl px-3.5 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none',
                                    currentPath === item.url
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'text-muted-foreground hover:bg-background/70 hover:text-foreground',
                                )}
                            >
                                {item.icon && (
                                    <span
                                        className={cn(
                                            'flex size-8 shrink-0 items-center justify-center rounded-lg',
                                            currentPath === item.url ? 'bg-primary text-primary-foreground' : 'bg-background text-muted-foreground',
                                        )}
                                    >
                                        <item.icon className="size-4" aria-hidden="true" />
                                    </span>
                                )}
                                {item.title}
                            </Link>
                        ))}
                    </nav>
                </aside>
                <main className="max-w-3xl min-w-0">{children}</main>
            </div>
        </div>
    );
}
