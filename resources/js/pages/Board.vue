<script setup lang="ts">
import BoardCard from '@/components/board/BoardCard.vue';
import { Button } from '@/components/ui/button';
import { useBoard } from '@/composables/useBoard';
import AppLayout from '@/layouts/AppLayout.vue';
import { statusClasses } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';
import type { BoardColumn } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { toRef } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';

const props = defineProps<{
    columns: BoardColumn[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Board', href: '/board' }];

const { columns, onDragEnd } = useBoard(toRef(props, 'columns'));
</script>

<template>
    <Head title="Board" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-col gap-4 p-4">
            <div class="flex items-center justify-between gap-4">
                <h1 class="text-xl font-semibold tracking-tight">Application board</h1>
                <Button as-child size="sm">
                    <Link :href="route('vacancies.create')"><Plus /> New vacancy</Link>
                </Button>
            </div>

            <div class="flex flex-1 gap-4 overflow-x-auto pb-2">
                <section v-for="column in columns" :key="column.status" class="flex w-72 shrink-0 flex-col rounded-xl bg-muted/50">
                    <header class="flex items-center justify-between px-3 pb-2 pt-3">
                        <span :class="cn('rounded-full px-2 py-0.5 text-xs font-medium', statusClasses[column.status])">{{ column.label }}</span>
                        <span class="text-xs text-muted-foreground">{{ column.applications.length }}</span>
                    </header>

                    <VueDraggable
                        v-model="column.applications"
                        group="applications"
                        :animation="150"
                        ghost-class="opacity-40"
                        :data-status="column.status"
                        class="flex min-h-24 flex-1 flex-col gap-2 px-2 pb-3"
                        @end="onDragEnd"
                    >
                        <BoardCard
                            v-for="application in column.applications"
                            :key="application.id"
                            :data-id="application.id"
                            :application="application"
                        />
                    </VueDraggable>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
