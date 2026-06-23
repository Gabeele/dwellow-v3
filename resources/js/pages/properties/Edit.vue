<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import PropertyController from '@/actions/App/Http/Controllers/PropertyController';
import Heading from '@/components/Heading.vue';
import PropertyFormFields from '@/components/properties/PropertyFormFields.vue';
import { Button } from '@/components/ui/button';
import { index, show } from '@/routes/properties';
import type { Property, PropertyFormOptions } from '@/types/property';

defineProps<{
    property: Property;
    options: PropertyFormOptions;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: index() },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Edit property" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading
            variant="small"
            title="Edit property"
            :description="property.name || property.address_line1"
        />

        <Form
            v-bind="PropertyController.update.form(property.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <PropertyFormFields
                :property="property"
                :options="options"
                :errors="errors"
            />

            <div class="flex items-center gap-3">
                <Button :disabled="processing">Save changes</Button>
                <Button as-child variant="ghost">
                    <Link :href="show(property.id)">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
