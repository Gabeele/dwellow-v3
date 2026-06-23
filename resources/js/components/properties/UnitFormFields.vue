<script setup lang="ts">
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { SelectOption, Unit } from '@/types/property';

const props = defineProps<{
    unit?: Unit;
    statuses: SelectOption[];
    errors: Record<string, string>;
}>();

const status = ref(props.unit?.status ?? 'available');

const statusLabel = computed(
    () => props.statuses.find((option) => option.value === status.value)?.label,
);
</script>

<template>
    <div class="grid gap-6">
        <div class="grid gap-2">
            <Label for="label" class="text-sm">Unit label</Label>
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
                <Label for="bedrooms" class="text-sm">Bedrooms</Label>
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
                <Label for="bathrooms" class="text-sm">Bathrooms</Label>
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
                <Label for="rent_amount" class="text-sm">Monthly rent</Label>
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
            <Label for="status" class="text-sm">Status</Label>
            <input type="hidden" name="status" :value="status" />
            <Select v-model="status">
                <SelectTrigger id="status" class="w-full">
                    <SelectValue placeholder="Select a status">
                        {{ statusLabel }}
                    </SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in statuses"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors.status" />
        </div>
    </div>
</template>
