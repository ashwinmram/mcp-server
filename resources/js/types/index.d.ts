import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    locale: string;
    dir: 'ltr' | 'rtl';
    locales: Record<string, string>;
    translations: Record<string, Record<string, unknown>>;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface DashboardStatItem {
    name: string;
    stat: string;
    previousStat: string;
    change: string;
    changeType: 'increase' | 'decrease';
    comparisonType: 'snapshot' | 'prior_period';
    changeFormat: 'relative' | 'points';
}

export interface DashboardStats {
    knowledgeBase: DashboardStatItem[];
    projectDetails: DashboardStatItem[];
    bySourceProject: DashboardStatItem[];
}
