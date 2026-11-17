export type ApplicationStatus = 'wishlist' | 'applied' | 'screening' | 'interview' | 'offer' | 'rejected';
export type WorkMode = 'remote' | 'hybrid' | 'onsite';

export interface Option<T extends string = string> {
    value: T;
    label: string;
}

export interface Company {
    id: number;
    name: string;
    website: string | null;
    location: string | null;
    notes: string | null;
    vacancies_count?: number;
}

export interface StatusChange {
    id: number;
    from_label: string | null;
    to_label: string;
    created_at: string;
}

export interface JobApplication {
    id: number;
    status: ApplicationStatus;
    status_label: string;
    position: number;
    applied_at: string | null;
    notes: string | null;
    vacancy?: Vacancy;
    status_changes?: StatusChange[];
}

export interface Vacancy {
    id: number;
    title: string;
    url: string | null;
    location: string | null;
    work_mode: WorkMode | null;
    work_mode_label: string | null;
    salary_min: number | null;
    salary_max: number | null;
    salary_currency: string | null;
    source: string | null;
    description: string | null;
    created_at: string;
    company?: Company;
    application?: JobApplication;
}

export interface BoardColumn {
    status: ApplicationStatus;
    label: string;
    applications: JobApplication[];
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    meta: {
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        links: PaginationLink[];
    };
}
