<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { Option } from '@/types/models';
import type { HTMLAttributes } from 'vue';

const props = defineProps<{
    options: Option[];
    placeholder?: string;
    class?: HTMLAttributes['class'];
}>();

const model = defineModel<string>();
</script>

<template>
    <select
        v-model="model"
        :class="
            cn(
                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                props.class,
            )
        "
    >
        <!-- Empty strings reach Laravel as null (ConvertEmptyStringsToNull middleware). -->
        <option v-if="placeholder !== undefined" value="">{{ placeholder }}</option>
        <option v-for="option in options" :key="option.value" :value="option.value">{{ option.label }}</option>
    </select>
</template>
