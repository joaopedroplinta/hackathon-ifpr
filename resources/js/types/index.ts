import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
    /** Organizador ou admin. Decide navegação, não acesso — quem decide é a Policy. */
    is_staff: boolean;
    /** Papel de jurado. Mesma ressalva do is_staff: só decide o link, não o acesso. */
    is_judge: boolean;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

/** Página do `paginate()` do Laravel, já passada por `through()`. */
export interface Paginated<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
}

export interface EventoAtual {
    nome: string;
    slug: string;
    inscricoes_abertas: boolean;
    inscrito: boolean;
}

export interface SharedData {
    name: string;
    /** Ex.: "v0.6.0" em produção, "v0.6.0-dev+af4e0aa" fora dela. */
    app_version: string;
    auth: Auth;
    /** Evento em foco. Nulo quando nenhum evento saiu do rascunho. */
    evento: EventoAtual | null;
    flash: { sucesso?: string; erro?: string };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
