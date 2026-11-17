import type { NavItem } from '@/types';
import { Briefcase, Building2, Github, LayoutGrid, SquareKanban } from 'lucide-vue-next';

export const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'Board', href: '/board', icon: SquareKanban },
    { title: 'Vacancies', href: '/vacancies', icon: Briefcase },
    { title: 'Companies', href: '/companies', icon: Building2 },
];

export const footerNavItems: NavItem[] = [{ title: 'Source code', href: 'https://github.com/clownshegi/jobpilot', icon: Github }];

/** A section stays highlighted on its nested pages too, e.g. /vacancies/5/edit. */
export function isActive(href: string, currentUrl: string): boolean {
    const path = currentUrl.split('?')[0];

    return path === href || path.startsWith(`${href}/`);
}
