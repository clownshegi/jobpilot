<script setup lang="ts">
import { statusClasses } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { ApplicationStatus } from '@/types/models';

defineProps<{
    status: ApplicationStatus;
    label: string;
}>();
</script>

<template>
    <span :class="cn('inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', statusClasses[status])">
        {{ label }}
    </span>
</template>
