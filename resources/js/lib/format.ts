import type { ApplicationStatus, Vacancy } from '@/types/models';

export function formatSalary(vacancy: Pick<Vacancy, 'salary_min' | 'salary_max' | 'salary_currency'>): string | null {
    const { salary_min: min, salary_max: max, salary_currency: currency } = vacancy;

    if (min === null && max === null) {
        return null;
    }

    const amount = (value: number) => value.toLocaleString('en-US');
    const range = min !== null && max !== null && min !== max ? `${amount(min)}–${amount(max)}` : amount((min ?? max) as number);

    return currency ? `${range} ${currency}` : range;
}

export function formatDate(iso: string | null): string {
    if (!iso) {
        return '—';
    }

    return new Date(iso).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

/** Tailwind classes for a status, shared by badges and board column headers. */
export const statusClasses: Record<ApplicationStatus, string> = {
    wishlist: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    applied: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    screening: 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
    interview: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    offer: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
};
