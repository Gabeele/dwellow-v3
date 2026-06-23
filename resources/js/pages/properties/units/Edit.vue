<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import UnitController from '@/actions/App/Http/Controllers/UnitController';
import Eyebrow from '@/components/Eyebrow.vue';
import UnitFormFields from '@/components/properties/UnitFormFields.vue';
import { Button } from '@/components/ui/button';
import { index, show } from '@/routes/properties';
import type { Property, SelectOption, Unit } from '@/types/property';

defineProps<{
    property: Property;
    unit: Unit;
    statuses: SelectOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: index() },
            { title: 'Edit unit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Edit unit" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-6 lg:p-10">
        <div class="flex flex-col gap-2">
            <Eyebrow>Edit unit</Eyebrow>
            <h1 class="text-2xl font-semibold tracking-[-0.02em]">
                {{ unit.label }}
            </h1>
            <p class="text-sm text-muted-foreground">
                {{ property.name || property.address_line1 }}
            </p>
        </div>

        <Form
            v-bind="UnitController.update.form(unit.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="rounded-2xl border border-border bg-card p-6">
                <UnitFormFields
                    :unit="unit"
                    :statuses="statuses"
                    :errors="errors"
                />
            </div>

            <div class="flex items-center gap-3">
                <Button :disabled="processing">Save changes</Button>
                <Button as-child variant="ghost">
                    <Link :href="show(property.id)">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
