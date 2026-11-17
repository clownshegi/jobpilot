<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import type { Option } from '@/types/models';
import { useForm } from '@inertiajs/vue3';

export type VacancyFormData = {
    title: string;
    company_name: string;
    url: string;
    location: string;
    work_mode: string;
    salary_min: string;
    salary_max: string;
    salary_currency: string;
    source: string;
    description: string;
    status?: string;
    notes?: string;
};

/** Shared by the create and edit pages; status and notes are only shown when creating. */
const props = defineProps<{
    initial: VacancyFormData;
    method: 'post' | 'put';
    action: string;
    companyNames: string[];
    workModes: Option[];
    statuses?: Option[];
    submitLabel: string;
}>();

const form = useForm<VacancyFormData>({ ...props.initial });

const submit = () => form.submit(props.method, props.action);
</script>

<template>
    <form class="max-w-3xl space-y-6" @submit.prevent="submit">
        <div class="grid gap-6 md:grid-cols-2">
            <div class="grid gap-2">
                <Label for="title">Position</Label>
                <Input id="title" v-model="form.title" required placeholder="Middle Laravel Developer" />
                <InputError :message="form.errors.title" />
            </div>
            <div class="grid gap-2">
                <Label for="company_name">Company</Label>
                <Input id="company_name" v-model="form.company_name" required list="company-names" placeholder="Pick or type a new one" />
                <datalist id="company-names">
                    <option v-for="name in companyNames" :key="name" :value="name" />
                </datalist>
                <InputError :message="form.errors.company_name" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="url">Link to the vacancy</Label>
            <Input id="url" v-model="form.url" type="url" placeholder="https://" />
            <InputError :message="form.errors.url" />
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="grid gap-2">
                <Label for="location">Location</Label>
                <Input id="location" v-model="form.location" placeholder="Brno" />
                <InputError :message="form.errors.location" />
            </div>
            <div class="grid gap-2">
                <Label for="work_mode">Work mode</Label>
                <NativeSelect id="work_mode" v-model="form.work_mode" :options="workModes" placeholder="Not specified" />
                <InputError :message="form.errors.work_mode" />
            </div>
            <div class="grid gap-2">
                <Label for="source">Source</Label>
                <Input id="source" v-model="form.source" list="sources" placeholder="djinni, dou, linkedin…" />
                <datalist id="sources">
                    <option v-for="source in ['djinni', 'dou', 'linkedin', 'jobs.cz', 'startupjobs', 'referral']" :key="source" :value="source" />
                </datalist>
                <InputError :message="form.errors.source" />
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="grid gap-2">
                <Label for="salary_min">Salary from</Label>
                <Input id="salary_min" v-model="form.salary_min" type="number" min="0" />
                <InputError :message="form.errors.salary_min" />
            </div>
            <div class="grid gap-2">
                <Label for="salary_max">Salary to</Label>
                <Input id="salary_max" v-model="form.salary_max" type="number" min="0" />
                <InputError :message="form.errors.salary_max" />
            </div>
            <div class="grid gap-2">
                <Label for="salary_currency">Currency</Label>
                <Input id="salary_currency" v-model="form.salary_currency" maxlength="3" list="currencies" placeholder="USD" class="uppercase" />
                <datalist id="currencies">
                    <option v-for="currency in ['USD', 'EUR', 'CZK', 'UAH', 'PLN']" :key="currency" :value="currency" />
                </datalist>
                <InputError :message="form.errors.salary_currency" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="description">Description</Label>
            <Textarea
                id="description"
                v-model="form.description"
                rows="8"
                placeholder="Paste the vacancy text — it will be used for AI matching later."
            />
            <InputError :message="form.errors.description" />
        </div>

        <div v-if="statuses" class="grid gap-6 md:grid-cols-3">
            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <NativeSelect id="status" v-model="form.status" :options="statuses" />
                <InputError :message="form.errors.status" />
            </div>
            <div class="grid gap-2 md:col-span-2">
                <Label for="notes">Notes</Label>
                <Textarea id="notes" v-model="form.notes" rows="2" placeholder="Recruiter, referral, anything worth remembering" />
                <InputError :message="form.errors.notes" />
            </div>
        </div>

        <Button type="submit" :disabled="form.processing">{{ submitLabel }}</Button>
    </form>
</template>
