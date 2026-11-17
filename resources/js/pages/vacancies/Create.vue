<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import VacancyForm, { type VacancyFormData } from '@/components/vacancies/VacancyForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Option } from '@/types/models';
import { Head } from '@inertiajs/vue3';

defineProps<{
    companyNames: string[];
    statuses: Option[];
    workModes: Option[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vacancies', href: '/vacancies' },
    { title: 'New vacancy', href: '/vacancies/create' },
];

const initial: VacancyFormData = {
    title: '',
    company_name: '',
    url: '',
    location: '',
    work_mode: '',
    salary_min: '',
    salary_max: '',
    salary_currency: '',
    source: '',
    description: '',
    status: 'wishlist',
    notes: '',
};
</script>

<template>
    <Head title="New vacancy" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <PageHeader title="New vacancy" description="Add a job you want to track." />
            <VacancyForm
                :initial="initial"
                method="post"
                :action="route('vacancies.store')"
                :company-names="companyNames"
                :work-modes="workModes"
                :statuses="statuses"
                submit-label="Add vacancy"
            />
        </div>
    </AppLayout>
</template>
