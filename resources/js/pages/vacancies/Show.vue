<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatDate, formatSalary } from '@/lib/format';
import type { BreadcrumbItem } from '@/types';
import type { JobApplication, Option, Vacancy } from '@/types/models';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ExternalLink, Pencil, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    vacancy: Vacancy;
    application: JobApplication;
    statuses: Option[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Vacancies', href: '/vacancies' },
    { title: props.vacancy.title, href: `/vacancies/${props.vacancy.id}` },
];

const status = ref<string>(props.application.status);
watch(
    () => props.application.status,
    (value) => (status.value = value),
);

const changeStatus = () => {
    router.patch(route('job-applications.move', { jobApplication: props.application.id }), { status: status.value }, { preserveScroll: true });
};

const notesForm = useForm({ notes: props.application.notes ?? '' });
const saveNotes = () => notesForm.patch(route('job-applications.update', { jobApplication: props.application.id }), { preserveScroll: true });

const details = computed(() =>
    [
        ['Location', props.vacancy.location],
        ['Work mode', props.vacancy.work_mode_label],
        ['Salary', formatSalary(props.vacancy)],
        ['Source', props.vacancy.source],
        ['Added', formatDate(props.vacancy.created_at)],
        ['Applied', props.application.applied_at ? formatDate(props.application.applied_at) : null],
    ].filter((row): row is [string, string] => Boolean(row[1])),
);

const destroy = () => {
    if (confirm(`Delete "${props.vacancy.title}"? This also removes its application history.`)) {
        router.delete(route('vacancies.destroy', { vacancy: props.vacancy.id }));
    }
};
</script>

<template>
    <Head :title="vacancy.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="grid gap-6 p-4 lg:grid-cols-[1fr_20rem]">
            <div class="min-w-0 space-y-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight">{{ vacancy.title }}</h1>
                        <p class="text-muted-foreground">{{ vacancy.company?.name }}</p>
                    </div>
                    <div class="flex gap-2">
                        <Button v-if="vacancy.url" variant="outline" as-child>
                            <a :href="vacancy.url" target="_blank" rel="noopener noreferrer"><ExternalLink /> Open posting</a>
                        </Button>
                        <Button variant="outline" as-child>
                            <Link :href="route('vacancies.edit', { vacancy: vacancy.id })"><Pencil /> Edit</Link>
                        </Button>
                        <Button variant="ghost" size="icon" aria-label="Delete vacancy" @click="destroy"><Trash2 /></Button>
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-4 rounded-xl border p-4 sm:grid-cols-3">
                    <div v-for="[term, value] in details" :key="term">
                        <dt class="text-xs text-muted-foreground">{{ term }}</dt>
                        <dd class="text-sm font-medium">{{ value }}</dd>
                    </div>
                </dl>

                <section class="space-y-2">
                    <h2 class="font-medium">Description</h2>
                    <p v-if="vacancy.description" class="whitespace-pre-line text-sm leading-relaxed">{{ vacancy.description }}</p>
                    <p v-else class="text-sm text-muted-foreground">No description yet. Paste it on the edit page to use AI matching later.</p>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="space-y-3 rounded-xl border p-4">
                    <div class="flex items-center justify-between">
                        <h2 class="font-medium">Application</h2>
                        <StatusBadge :status="application.status" :label="application.status_label" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="status">Move to</Label>
                        <NativeSelect id="status" v-model="status" :options="statuses" @change="changeStatus" />
                    </div>
                    <form class="grid gap-2" @submit.prevent="saveNotes">
                        <Label for="notes">Notes</Label>
                        <Textarea id="notes" v-model="notesForm.notes" rows="4" />
                        <InputError :message="notesForm.errors.notes" />
                        <div class="flex items-center gap-3">
                            <Button size="sm" :disabled="notesForm.processing || !notesForm.isDirty">Save notes</Button>
                            <span v-if="notesForm.recentlySuccessful" class="text-sm text-muted-foreground">Saved.</span>
                        </div>
                    </form>
                </section>

                <section v-if="application.status_changes?.length" class="space-y-3 rounded-xl border p-4">
                    <h2 class="font-medium">History</h2>
                    <ol class="space-y-2 text-sm">
                        <li v-for="change in application.status_changes" :key="change.id" class="flex justify-between gap-2">
                            <span>
                                <template v-if="change.from_label">{{ change.from_label }} → </template>{{ change.to_label }}
                            </span>
                            <span class="whitespace-nowrap text-muted-foreground">{{ formatDate(change.created_at) }}</span>
                        </li>
                    </ol>
                </section>
            </aside>
        </div>
    </AppLayout>
</template>
