<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { useUserEvent } from '@/composables/useUserEvent';
import type { VacancyImport } from '@/types/models';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck, Link2, LoaderCircle } from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

const open = ref(false);
const form = useForm({ url: '' });
const page = usePage();

const importId = ref<number | null>(null);
// Updates arrive over the websocket and may beat the redirect back, so keep them all by id.
const updates = reactive(new Map<number, VacancyImport>());
const current = computed(() => (importId.value === null ? null : (updates.get(importId.value) ?? null)));

useUserEvent<VacancyImport>('vacancy-import.updated', (update) => {
    updates.set(update.id, update);

    if (update.id === importId.value && update.status === 'done') {
        router.reload();
    }
});

const submit = () => {
    form.post(route('vacancy-imports.store'), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            const flashed = (page.props.flash as { vacancyImport?: VacancyImport }).vacancyImport;

            if (flashed) {
                importId.value = flashed.id;
                if (!updates.has(flashed.id)) {
                    updates.set(flashed.id, flashed);
                }
            }
        },
    });
};

const reset = () => {
    importId.value = null;
    form.reset();
    form.clearErrors();
};

const onOpenChange = (value: boolean) => {
    open.value = value;
    if (!value && current.value?.status !== 'pending' && current.value?.status !== 'processing') {
        reset();
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="onOpenChange">
        <DialogTrigger as-child>
            <Button variant="outline" size="sm"><Link2 /> Import from link</Button>
        </DialogTrigger>

        <DialogContent>
            <DialogHeader>
                <DialogTitle>Import a vacancy</DialogTitle>
                <DialogDescription>Paste a link to a job posting. It's processed in the background — you can close this window.</DialogDescription>
            </DialogHeader>

            <form v-if="!current" id="import-form" class="grid gap-2" @submit.prevent="submit">
                <Input v-model="form.url" type="url" required autofocus placeholder="https://djinni.co/jobs/…" aria-label="Vacancy link" />
                <InputError :message="form.errors.url" />
            </form>

            <div v-else-if="current.status === 'pending' || current.status === 'processing'" class="flex items-center gap-3 text-sm">
                <LoaderCircle class="size-5 animate-spin text-muted-foreground" />
                {{ current.status === 'pending' ? 'Waiting in the queue…' : 'Reading the page…' }}
            </div>

            <div v-else-if="current.status === 'done'" class="flex items-start gap-3 text-sm">
                <CircleCheck class="size-5 shrink-0 text-emerald-600" />
                <span>{{ current.message ?? 'Added to your wishlist.' }}</span>
            </div>

            <div v-else class="flex items-start gap-3 text-sm">
                <CircleAlert class="size-5 shrink-0 text-destructive" />
                <span>{{ current.message }}</span>
            </div>

            <DialogFooter>
                <template v-if="!current">
                    <Button type="submit" form="import-form" :disabled="form.processing">Import</Button>
                </template>
                <template v-else-if="current.status === 'done' && current.vacancy_id">
                    <Button variant="outline" type="button" @click="reset">Import another</Button>
                    <Button as-child>
                        <Link :href="route('vacancies.show', { vacancy: current.vacancy_id })">Open vacancy</Link>
                    </Button>
                </template>
                <template v-else-if="current.status === 'failed'">
                    <Button variant="outline" as-child>
                        <Link :href="route('vacancies.create')">Add manually</Link>
                    </Button>
                    <Button type="button" @click="reset">Try another link</Button>
                </template>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
