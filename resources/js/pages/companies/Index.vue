<script setup lang="ts">
import CompanyDialog from '@/components/companies/CompanyDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Company } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, MapPin, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{
    companies: Company[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Companies', href: '/companies' }];

const dialogOpen = ref(false);
const editing = ref<Company | null>(null);

const openDialog = (company: Company | null) => {
    editing.value = company;
    dialogOpen.value = true;
};

const destroy = (company: Company) => {
    const vacancies = company.vacancies_count ? ` and its ${company.vacancies_count} vacancies` : '';

    if (confirm(`Delete ${company.name}${vacancies}?`)) {
        router.delete(route('companies.destroy', { company: company.id }), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Companies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <PageHeader title="Companies" description="Everyone you've applied to or plan to.">
                <Button @click="openDialog(null)"><Plus /> New company</Button>
            </PageHeader>

            <div v-if="companies.length === 0" class="rounded-xl border border-dashed p-10 text-center text-sm text-muted-foreground">
                No companies yet. They are also created automatically when you add a vacancy.
            </div>

            <ul v-else class="divide-y rounded-xl border">
                <li v-for="company in companies" :key="company.id" class="flex items-center gap-4 p-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="truncate font-medium">{{ company.name }}</span>
                            <a
                                v-if="company.website"
                                :href="company.website"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-muted-foreground hover:text-foreground"
                                :aria-label="`${company.name} website`"
                            >
                                <ExternalLink class="size-4" />
                            </a>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-muted-foreground">
                            <span v-if="company.location" class="inline-flex items-center gap-1"
                                ><MapPin class="size-3.5" />{{ company.location }}</span
                            >
                            <Link :href="route('vacancies.index', { search: company.name })" class="hover:text-foreground hover:underline">
                                {{ company.vacancies_count }} {{ company.vacancies_count === 1 ? 'vacancy' : 'vacancies' }}
                            </Link>
                        </div>
                    </div>
                    <Button variant="ghost" size="icon" :aria-label="`Edit ${company.name}`" @click="openDialog(company)"><Pencil /></Button>
                    <Button variant="ghost" size="icon" :aria-label="`Delete ${company.name}`" @click="destroy(company)"><Trash2 /></Button>
                </li>
            </ul>
        </div>

        <CompanyDialog v-model:open="dialogOpen" :company="editing" />
    </AppLayout>
</template>
