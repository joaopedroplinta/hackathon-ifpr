import { Head } from '@inertiajs/react';

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
                <SecaoFormulario titulo="Aparência" instrucao="Escolha entre tema claro, escuro ou a preferência definida no seu dispositivo.">
                    <AppearanceTabs />
                </SecaoFormulario>
            </SettingsLayout>
        </AppLayout>
    );
}
