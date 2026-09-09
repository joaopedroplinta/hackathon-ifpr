import { Head } from '@inertiajs/react';
import { MonitorCog, Moon, Sun } from 'lucide-react';

import AppearanceTabs from '@/components/appearance-tabs';
import SecaoFormulario from '@/components/hackathon/secao-formulario';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Aparência',
        href: '/settings/appearance',
    },
];

export default function Appearance() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Aparência" />

            <SettingsLayout>
                <div className="mb-6 grid grid-cols-3 gap-3" aria-hidden="true">
                    {[
                        { icon: Sun, className: 'bg-white text-neutral-900' },
                        { icon: Moon, className: 'bg-neutral-900 text-white' },
                        { icon: MonitorCog, className: 'bg-gradient-to-br from-white to-neutral-900 text-primary' },
                    ].map(({ icon: Icon, className }, index) => (
                        <div key={index} className={`border-border flex h-24 items-end rounded-2xl border p-4 shadow-sm sm:h-28 ${className}`}>
                            <Icon className="size-5" />
                        </div>
                    ))}
                </div>
                <SecaoFormulario
                    titulo="Aparência"
                    instrucao="Escolha entre tema claro, escuro ou a preferência definida no seu dispositivo. A mudança é aplicada imediatamente."
                    className="bg-background"
                >
                    <AppearanceTabs />
                </SecaoFormulario>
            </SettingsLayout>
        </AppLayout>
    );
}
