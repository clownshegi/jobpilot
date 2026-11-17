<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate, statusClasses } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import type { ApplicationStatus, Vacancy } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

defineProps<{
    stats: { status: ApplicationStatus; label: string; count: number }[];
    recentVacancies: Vacancy[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-4">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6">
                <Link
                    v-for="stat in stats"
                    :key="stat.status"
                    :href="route('vacancies.index', { status: stat.status })"
                    class="rounded-xl border p-4 transition-colors hover:bg-muted/50"
                >
                    <span :class="cn('rounded-full px-2 py-0.5 text-xs font-medium', statusClasses[stat.status])">{{ stat.label }}</span>
                    <p class="mt-3 text-3xl font-semibold tabular-nums">{{ stat.count }}</p>
                </Link>
            </div>

            <section class="rounded-xl border">
                <header class="flex items-center justify-between border-b p-4">
                    <h2 class="font-medium">Recently added</h2>
                    <Button as-child size="sm" variant="outline">
                        <Link :href="route('vacancies.create')"><Plus /> New vacancy</Link>
                    </Button>
                </header>
                <p v-if="recentVacancies.length === 0" class="p-6 text-center text-sm text-muted-foreground">
                    No vacancies yet. Add the first one to start tracking.
                </p>
                <ul v-else class="divide-y">
                    <li v-for="vacancy in recentVacancies" :key="vacancy.id" class="flex items-center gap-4 px-4 py-3">
                        <div class="min-w-0 flex-1">
                            <Link :href="route('vacancies.show', { vacancy: vacancy.id })" class="font-medium hover:underline">{{
                                vacancy.title
                            }}</Link>
                            <p class="text-sm text-muted-foreground">{{ vacancy.company?.name }}</p>
                        </div>
                        <StatusBadge v-if="vacancy.application" :status="vacancy.application.status" :label="vacancy.application.status_label" />
                        <span class="hidden whitespace-nowrap text-sm text-muted-foreground sm:inline">{{ formatDate(vacancy.created_at) }}</span>
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
