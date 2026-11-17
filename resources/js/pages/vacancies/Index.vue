<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate, formatSalary } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';
import type { Option, Paginated, Vacancy } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { Plus, Search } from 'lucide-vue-next';
import { reactive } from 'vue';

const props = defineProps<{
    vacancies: Paginated<Vacancy>;
    filters: { search: string; status: string };
    statuses: Option[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Vacancies', href: '/vacancies' }];

const filters = reactive({ ...props.filters });

watchDebounced(
    filters,
    () => {
        const query = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== ''));
        router.get(route('vacancies.index'), query, { preserveState: true, preserveScroll: true, replace: true });
    },
    { debounce: 300 },
);
</script>

<template>
    <Head title="Vacancies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <PageHeader title="Vacancies" :description="`${vacancies.meta.total} in total`">
                <Button as-child>
                    <Link :href="route('vacancies.create')"><Plus /> New vacancy</Link>
                </Button>
            </PageHeader>

            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative sm:max-w-sm sm:flex-1">
                    <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input v-model="filters.search" placeholder="Search by position or company" class="pl-9" aria-label="Search" />
                </div>
                <NativeSelect v-model="filters.status" :options="statuses" placeholder="All statuses" class="sm:w-48" aria-label="Status" />
            </div>

            <div v-if="vacancies.data.length === 0" class="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                Nothing found.
            </div>

            <div v-else class="overflow-x-auto rounded-xl border">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Position</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="hidden px-4 py-3 font-medium md:table-cell">Location</th>
                            <th class="hidden px-4 py-3 font-medium lg:table-cell">Salary</th>
                            <th class="hidden px-4 py-3 font-medium sm:table-cell">Added</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="vacancy in vacancies.data" :key="vacancy.id" class="hover:bg-muted/30">
                            <td class="px-4 py-3">
                                <Link :href="route('vacancies.show', { vacancy: vacancy.id })" class="font-medium hover:underline">{{
                                    vacancy.title
                                }}</Link>
                                <div class="text-muted-foreground">{{ vacancy.company?.name }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge
                                    v-if="vacancy.application"
                                    :status="vacancy.application.status"
                                    :label="vacancy.application.status_label"
                                />
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                {{ [vacancy.location, vacancy.work_mode_label].filter(Boolean).join(' · ') || '—' }}
                            </td>
                            <td class="hidden px-4 py-3 lg:table-cell">{{ formatSalary(vacancy) ?? '—' }}</td>
                            <td class="hidden whitespace-nowrap px-4 py-3 text-muted-foreground sm:table-cell">
                                {{ formatDate(vacancy.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="vacancies.meta.links" />
        </div>
    </AppLayout>
</template>
