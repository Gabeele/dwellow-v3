<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import UnitController from '@/actions/App/Http/Controllers/UnitController';
import Eyebrow from '@/components/Eyebrow.vue';
import UnitFormFields from '@/components/properties/UnitFormFields.vue';
import { Button } from '@/components/ui/button';
import { index, show } from '@/routes/properties';
import type { Property, SelectOption } from '@/types/property';

defineProps<{
    property: Property;
    statuses: SelectOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: index() },
            { title: 'Add unit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Add unit" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-6 lg:p-10">
        <div class="flex flex-col gap-2">
            <Eyebrow>Add unit</Eyebrow>
            <h1 class="text-2xl font-semibold tracking-[-0.02em]">
                {{ property.name || property.address_line1 }}
            </h1>
        </div>

        <Form
            v-bind="UnitController.store.form(property.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="rounded-2xl border border-border bg-card p-6">
                <UnitFormFields :statuses="statuses" :errors="errors" />
            </div>

            <div class="flex items-center gap-3">
                <Button :disabled="processing">Add unit</Button>
                <Button as-child variant="ghost">
                    <Link :href="show(property.id)">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
