<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import PropertyController from '@/actions/App/Http/Controllers/PropertyController';
import Heading from '@/components/Heading.vue';
import PropertyFormFields from '@/components/properties/PropertyFormFields.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/properties';
import type { PropertyFormOptions } from '@/types/property';

defineProps<{
    options: PropertyFormOptions;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: index() },
            { title: 'New property', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="New property" />

    <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-4">
        <Heading
            variant="small"
            title="New property"
            description="Add a property to your portfolio."
        />

        <Form
            v-bind="PropertyController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <PropertyFormFields :options="options" :errors="errors" />

            <div class="flex items-center gap-3">
                <Button :disabled="processing">Create property</Button>
                <Button as-child variant="ghost">
                    <Link :href="index()">Cancel</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
