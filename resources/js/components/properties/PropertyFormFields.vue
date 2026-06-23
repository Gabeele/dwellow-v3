<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Property, PropertyFormOptions } from '@/types/property';

const props = defineProps<{
    property?: Property;
    options: PropertyFormOptions;
    errors: Record<string, string>;
}>();

const rentalType = ref(props.property?.rental_type ?? 'whole');
const type = ref(props.property?.type ?? 'other');
const status = ref(props.property?.status ?? 'available');

const selectClass =
    'border-input bg-transparent dark:bg-input/30 h-9 w-full rounded-md border px-3 py-1 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] outline-none';
</script>

<template>
    <div class="grid gap-6">
        <div class="grid gap-2">
            <Label for="name"
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
            <Label for="address_line1">Address line 1</Label>
            <Input
                id="address_line1"
                name="address_line1"
                :default-value="property?.address_line1 ?? ''"
                required
            />
            <InputError :message="errors.address_line1" />
        </div>

        <div class="grid gap-2">
            <Label for="address_line2"
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
                <Label for="city">City</Label>
                <Input
                    id="city"
                    name="city"
                    :default-value="property?.city ?? ''"
                    required
                />
                <InputError :message="errors.city" />
            </div>
            <div class="grid gap-2">
                <Label for="region">Province / region</Label>
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
                <Label for="postal_code">Postal code</Label>
                <Input
                    id="postal_code"
                    name="postal_code"
                    :default-value="property?.postal_code ?? ''"
                    required
                />
                <InputError :message="errors.postal_code" />
            </div>
            <div class="grid gap-2">
                <Label for="country">Country</Label>
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
                <Label for="type">Property type</Label>
                <select
                    id="type"
                    name="type"
                    v-model="type"
                    :class="selectClass"
                >
                    <option
                        v-for="option in options.types"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="errors.type" />
            </div>
            <div class="grid gap-2">
                <Label for="rental_type">Rental type</Label>
                <select
                    id="rental_type"
                    name="rental_type"
                    v-model="rentalType"
                    :class="selectClass"
                >
                    <option
                        v-for="option in options.rentalTypes"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="errors.rental_type" />
            </div>
        </div>

        <!-- Rentable details only apply when the whole property is rented; for
             multi-unit properties these live on each unit instead. -->
        <div
            v-if="rentalType === 'whole'"
            class="grid gap-6 rounded-md border border-dashed p-4"
        >
            <p class="text-sm text-muted-foreground">
                Rental details for the whole property
            </p>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="bedrooms">Bedrooms</Label>
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
                    <Label for="bathrooms">Bathrooms</Label>
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
                    <Label for="rent_amount">Monthly rent</Label>
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
                <Label for="status">Status</Label>
                <select
                    id="status"
                    name="status"
                    v-model="status"
                    :class="selectClass"
                >
                    <option
                        v-for="option in options.statuses"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
                <InputError :message="errors.status" />
            </div>
        </div>
    </div>
</template>
