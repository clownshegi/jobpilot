<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types/models';
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: PaginationLink[];
}>();

// Laravel labels the arrows with HTML entities ("&laquo; Previous").
const label = (text: string) => text.replace('&laquo;', '←').replace('&raquo;', '→');
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap gap-1" aria-label="Pagination">
        <template v-for="link in links" :key="link.label">
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                :class="cn('rounded-md px-3 py-1.5 text-sm hover:bg-accent', link.active && 'bg-primary text-primary-foreground hover:bg-primary')"
            >
                {{ label(link.label) }}
            </Link>
            <span v-else class="px-3 py-1.5 text-sm text-muted-foreground">{{ label(link.label) }}</span>
        </template>
    </nav>
</template>
