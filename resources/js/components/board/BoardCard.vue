<script setup lang="ts">
import { formatSalary } from '@/lib/format';
import type { JobApplication } from '@/types/models';
import { Link } from '@inertiajs/vue3';
import { MapPin } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    application: JobApplication;
}>();

const vacancy = computed(() => props.application.vacancy!);
const salary = computed(() => formatSalary(vacancy.value));
</script>

<template>
    <article class="cursor-grab rounded-lg border bg-card p-3 shadow-sm transition-shadow hover:shadow-md active:cursor-grabbing">
        <Link :href="route('vacancies.show', { vacancy: vacancy.id })" class="block font-medium leading-snug hover:underline">
            {{ vacancy.title }}
        </Link>
        <p class="mt-0.5 text-sm text-muted-foreground">{{ vacancy.company?.name }}</p>
        <div v-if="vacancy.location || vacancy.work_mode_label || salary" class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-muted-foreground">
            <span v-if="vacancy.location || vacancy.work_mode_label" class="inline-flex items-center gap-1">
                <MapPin class="size-3" />{{ [vacancy.location, vacancy.work_mode_label].filter(Boolean).join(' · ') }}
            </span>
            <span v-if="salary">{{ salary }}</span>
        </div>
    </article>
</template>
