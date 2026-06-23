<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { SelectOption, Unit } from '@/types/property';

const props = defineProps<{
    unit?: Unit;
    statuses: SelectOption[];
    errors: Record<string, string>;
}>();

const status = ref(props.unit?.status ?? 'available');

const selectClass =
    'border-input bg-transparent dark:bg-input/30 h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] outline-none';
</script>

<template>
    <div class="grid gap-6">
        <div class="grid gap-2">
            <Label for="label">Unit label</Label>
            <Input
                id="label"
                name="label"
                :default-value="unit?.label ?? ''"
                placeholder="e.g. Unit A or 101"
                required
            />
            <InputError :message="errors.label" />
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="bedrooms">Bedrooms</Label>
                <Input
                    id="bedrooms"
                    name="bedrooms"
                    type="number"
                    min="0"
                    :default-value="unit?.bedrooms ?? ''"
                />
                <InputError :message="errors.bedrooms" />
            </div>
            <div class="grid gap-2">
                <Label for="bathrooms">Bathrooms</Label>
                <Input
                    id="bathrooms"
                    name="bathrooms"
                    type="number"
                    min="0"
                    step="0.5"
                    :default-value="unit?.bathrooms ?? ''"
                />
                <InputError :message="errors.bathrooms" />
            </div>
            <div class="grid gap-2">
                <Label for="rent_amount">Monthly rent</Label>
                <Input
                    id="rent_amount"
                    name="rent_amount"
                    type="number"
                    min="0"
                    step="0.01"
                    :default-value="unit?.rent_amount ?? ''"
                />
                <InputError :message="errors.rent_amount" />
            </div>
        </div>

        <div class="grid gap-2 sm:max-w-xs">
            <Label for="status">Status</Label>
            <select
                id="status"
                name="status"
                v-model="status"
                :class="selectClass"
            >
                <option
                    v-for="option in statuses"
                    :key="option.value"
                    :value="option.value"
                >
                    {{ option.label }}
                </option>
            </select>
            <InputError :message="errors.status" />
        </div>
    </div>
</template>
