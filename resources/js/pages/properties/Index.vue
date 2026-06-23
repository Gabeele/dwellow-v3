<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Plus } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { create, index, show } from '@/routes/properties';
import type { Property } from '@/types/property';

defineProps<{
    properties: Property[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Properties', href: index() }],
    },
});

function cityLine(property: Property): string {
    return [property.city, property.region].filter(Boolean).join(', ');
}
</script>

<template>
    <Head title="Properties" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Properties"
                description="Manage the properties you rent out."
            />
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    New property
                </Link>
            </Button>
        </div>

        <div
            v-if="properties.length === 0"
            class="flex flex-col items-center justify-center gap-3 rounded-xl border border-dashed p-12 text-center"
        >
            <Building2 class="size-8 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
                You haven't added any properties yet.
            </p>
            <Button as-child variant="outline">
                <Link :href="create()">Add your first property</Link>
            </Button>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="property in properties"
                :key="property.id"
                :href="show(property.id)"
                class="block"
            >
                <Card class="h-full transition-colors hover:border-primary">
                    <CardHeader>
                        <CardTitle>{{
                            property.name || property.address_line1
                        }}</CardTitle>
                        <p class="text-sm text-muted-foreground">
                            {{ cityLine(property) }}
                        </p>
                    </CardHeader>
                    <CardContent class="flex items-center gap-2">
                        <Badge variant="secondary">{{ property.type }}</Badge>
                        <Badge
                            v-if="property.rental_type === 'multi_unit'"
                            variant="outline"
                        >
                            {{ property.units_count ?? 0 }}
                            {{
                                (property.units_count ?? 0) === 1
                                    ? 'unit'
                                    : 'units'
                            }}
                        </Badge>
                        <Badge v-else variant="outline">Whole rental</Badge>
                    </CardContent>
                </Card>
            </Link>
        </div>
    </div>
</template>
