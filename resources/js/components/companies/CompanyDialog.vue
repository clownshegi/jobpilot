<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { Company } from '@/types/models';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

/** Creates a company, or edits `company` when one is given. */
const props = defineProps<{
    company: Company | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({
    name: '',
    website: '',
    location: '',
    notes: '',
});

watch(open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    form.clearErrors();
    form.defaults({
        name: props.company?.name ?? '',
        website: props.company?.website ?? '',
        location: props.company?.location ?? '',
        notes: props.company?.notes ?? '',
    });
    form.reset();
});

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => (open.value = false) };

    if (props.company) {
        form.put(route('companies.update', { company: props.company.id }), options);
    } else {
        form.post(route('companies.store'), options);
    }
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ company ? 'Edit company' : 'New company' }}</DialogTitle>
            </DialogHeader>

            <form id="company-form" class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="company-name">Name</Label>
                    <Input id="company-name" v-model="form.name" required autofocus />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="company-website">Website</Label>
                    <Input id="company-website" v-model="form.website" type="url" placeholder="https://" />
                    <InputError :message="form.errors.website" />
                </div>
                <div class="grid gap-2">
                    <Label for="company-location">Location</Label>
                    <Input id="company-location" v-model="form.location" placeholder="Brno, Kyiv, Remote…" />
                    <InputError :message="form.errors.location" />
                </div>
                <div class="grid gap-2">
                    <Label for="company-notes">Notes</Label>
                    <Textarea id="company-notes" v-model="form.notes" rows="3" />
                    <InputError :message="form.errors.notes" />
                </div>
            </form>

            <DialogFooter>
                <Button variant="outline" type="button" @click="open = false">Cancel</Button>
                <Button type="submit" form="company-form" :disabled="form.processing">Save</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
