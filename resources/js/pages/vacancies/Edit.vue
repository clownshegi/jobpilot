<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import VacancyForm, { type VacancyFormData } from '@/components/vacancies/VacancyForm.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Option, Vacancy } from '@/types/models';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    vacancy: Vacancy;
    companyNames: string[];
    workModes: Option[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vacancies', href: '/vacancies' },
    { title: props.vacancy.title, href: `/vacancies/${props.vacancy.id}` },
    { title: 'Edit', href: `/vacancies/${props.vacancy.id}/edit` },
];

const text = (value: string | number | null) => (value === null ? '' : String(value));

const initial: VacancyFormData = {
    title: props.vacancy.title,
    company_name: props.vacancy.company?.name ?? '',
    url: text(props.vacancy.url),
    location: text(props.vacancy.location),
    work_mode: text(props.vacancy.work_mode),
    salary_min: text(props.vacancy.salary_min),
    salary_max: text(props.vacancy.salary_max),
    salary_currency: text(props.vacancy.salary_currency),
    source: text(props.vacancy.source),
    description: text(props.vacancy.description),
};
</script>

<template>
    <Head :title="`Edit · ${vacancy.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <PageHeader title="Edit vacancy" :description="`${vacancy.title} at ${vacancy.company?.name}`" />
            <VacancyForm
                :initial="initial"
                method="put"
                :action="route('vacancies.update', { vacancy: vacancy.id })"
                :company-names="companyNames"
                :work-modes="workModes"
                submit-label="Save changes"
            />
        </div>
    </AppLayout>
</template>
