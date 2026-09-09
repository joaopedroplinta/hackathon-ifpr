import AppLogoIcon from '@/components/app-logo-icon';
import { Link } from '@inertiajs/react';

export default function AuthCardLayout({
    children,
    title,
    description,
}: {
    children: React.ReactNode;
    name?: string;
    title?: string;
    description?: string;
}) {
    return (
        <div className="bg-muted/40 flex min-h-svh flex-col items-center justify-center p-5 sm:p-8">
            <div className="flex w-full max-w-md flex-col gap-7">
                <Link
                    href={route('home')}
                    className="focus-visible:ring-ring flex items-center gap-3 self-center rounded-xl font-medium focus-visible:ring-2 focus-visible:outline-none"
                >
                    <span className="bg-primary text-primary-foreground flex size-11 items-center justify-center rounded-2xl">
                        <AppLogoIcon className="size-6 fill-current" />
                    </span>
                    <span>Hackathon IFPR</span>
                </Link>

                <div className="border-border bg-background overflow-hidden rounded-[1.75rem] border shadow-[0_24px_80px_-48px_rgba(0,0,0,0.5)]">
                    <header className="border-border border-b px-6 py-7 text-center sm:px-9">
                        <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
                        {description && <p className="text-muted-foreground mt-2 text-sm leading-relaxed">{description}</p>}
                    </header>
                    <div className="px-6 py-7 sm:px-9 sm:py-8">{children}</div>
                </div>
            </div>
        </div>
    );
}
