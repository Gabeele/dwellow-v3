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
import type { Property, PropertyFormOptions } from '@/types/property';

const props = defineProps<{
    property?: Property;
    options: PropertyFormOptions;
    errors: Record<string, string>;
}>();

const rentalType = ref(props.property?.rental_type ?? 'whole');
const type = ref(props.property?.type ?? 'other');
const status = ref(props.property?.status ?? 'available');

const typeLabel = computed(
    () =>
        props.options.types.find((option) => option.value === type.value)
            ?.label,
);
const rentalTypeLabel = computed(
    () =>
        props.options.rentalTypes.find(
            (option) => option.value === rentalType.value,
        )?.label,
);
const statusLabel = computed(
    () =>
        props.options.statuses.find((option) => option.value === status.value)
            ?.label,
);
</script>

<template>
    <div class="grid gap-6">
        <div class="grid gap-2">
            <Label for="name" class="text-sm"
                >Name
                <span class="text-muted-foreground">(optional)</span></Label
            >
            <Input
                id="name"
                name="name"
                :default-value="property?.name ?? ''"
                placeholder="e.g. Maple Street Duplex"
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="address_line1" class="text-sm">Address line 1</Label>
            <Input
                id="address_line1"
                name="address_line1"
                :default-value="property?.address_line1 ?? ''"
                required
            />
            <InputError :message="errors.address_line1" />
        </div>

        <div class="grid gap-2">
            <Label for="address_line2" class="text-sm"
                >Address line 2
                <span class="text-muted-foreground">(optional)</span></Label
            >
            <Input
                id="address_line2"
                name="address_line2"
                :default-value="property?.address_line2 ?? ''"
            />
            <InputError :message="errors.address_line2" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="city" class="text-sm">City</Label>
                <Input
                    id="city"
                    name="city"
                    :default-value="property?.city ?? ''"
                    required
                />
                <InputError :message="errors.city" />
            </div>
            <div class="grid gap-2">
                <Label for="region" class="text-sm">Province / region</Label>
                <Input
                    id="region"
                    name="region"
                    :default-value="property?.region ?? ''"
                    required
                />
                <InputError :message="errors.region" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="postal_code" class="text-sm">Postal code</Label>
                <Input
                    id="postal_code"
                    name="postal_code"
                    :default-value="property?.postal_code ?? ''"
                    required
                />
                <InputError :message="errors.postal_code" />
            </div>
            <div class="grid gap-2">
                <Label for="country" class="text-sm">Country</Label>
                <Input
                    id="country"
                    name="country"
                    :default-value="property?.country ?? 'CA'"
                    maxlength="2"
                    required
                />
                <InputError :message="errors.country" />
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="type" class="text-sm">Property type</Label>
                <input type="hidden" name="type" :value="type" />
                <Select v-model="type">
                    <SelectTrigger id="type" class="w-full">
                        <SelectValue placeholder="Select a type">
                            {{ typeLabel }}
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in options.types"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.type" />
            </div>
            <div class="grid gap-2">
                <Label for="rental_type" class="text-sm">Rental type</Label>
                <input type="hidden" name="rental_type" :value="rentalType" />
                <Select v-model="rentalType">
                    <SelectTrigger id="rental_type" class="w-full">
                        <SelectValue placeholder="Select a rental type">
                            {{ rentalTypeLabel }}
                        </SelectValue>
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in options.rentalTypes"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.rental_type" />
            </div>
        </div>

        <!-- Rentable details only apply when the whole property is rented; for
             multi-unit properties these live on each unit instead. -->
        <div
            v-if="rentalType === 'whole'"
            class="grid gap-6 rounded-lg border border-dashed border-border p-4"
        >
            <p class="text-sm text-muted-foreground">
                Rental details for the whole property
            </p>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="bedrooms" class="text-sm">Bedrooms</Label>
                    <Input
                        id="bedrooms"
                        name="bedrooms"
                        type="number"
                        min="0"
                        :default-value="property?.bedrooms ?? ''"
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
                        :default-value="property?.bathrooms ?? ''"
                    />
                    <InputError :message="errors.bathrooms" />
                </div>
                <div class="grid gap-2">
                    <Label for="rent_amount" class="text-sm"
                        >Monthly rent</Label
                    >
                    <Input
                        id="rent_amount"
                        name="rent_amount"
                        type="number"
                        min="0"
                        step="0.01"
                        :default-value="property?.rent_amount ?? ''"
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
                            v-for="option in options.statuses"
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
    </div>
</template>
