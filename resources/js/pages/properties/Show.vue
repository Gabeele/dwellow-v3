<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import PropertyController from '@/actions/App/Http/Controllers/PropertyController';
import UnitController from '@/actions/App/Http/Controllers/UnitController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { edit, index } from '@/routes/properties';
import { create as createUnit } from '@/routes/properties/units';
import { edit as editUnit } from '@/routes/units';
import type { Property, Unit } from '@/types/property';

const props = defineProps<{
    property: Property;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: index() },
            { title: 'Details', href: '#' },
        ],
    },
});

const isMultiUnit = props.property.rental_type === 'multi_unit';

function fullAddress(p: Property): string {
    return [p.address_line1, p.address_line2, p.city, p.region, p.postal_code]
        .filter(Boolean)
        .join(', ');
}

function destroyProperty(): void {
    if (confirm('Delete this property? This also removes all of its units.')) {
        router.delete(PropertyController.destroy.url(props.property.id));
    }
}

function destroyUnit(unit: Unit): void {
    if (confirm(`Delete unit "${unit.label}"?`)) {
        router.delete(UnitController.destroy.url(unit.id));
    }
}
</script>

<template>
    <Head :title="property.name || property.address_line1" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="property.name || property.address_line1"
                :description="fullAddress(property)"
            />
            <div class="flex items-center gap-2">
                <Button as-child variant="outline">
                    <Link :href="edit(property.id)">
                        <Pencil />
                        Edit
                    </Link>
                </Button>
                <Button variant="destructive" @click="destroyProperty">
                    <Trash2 />
                    Delete
                </Button>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <Badge variant="secondary">{{ property.type }}</Badge>
            <Badge variant="outline">{{
                isMultiUnit ? 'Split into units' : 'Rented as a whole'
            }}</Badge>
        </div>

        <!-- Whole-property rental: details live on the property itself -->
        <Card v-if="!isMultiUnit">
            <CardHeader>
                <CardTitle>Rental details</CardTitle>
            </CardHeader>
            <CardContent class="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                <div>
                    <p class="text-muted-foreground">Bedrooms</p>
                    <p>{{ property.bedrooms ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground">Bathrooms</p>
                    <p>{{ property.bathrooms ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-muted-foreground">Monthly rent</p>
                    <p>
                        {{
                            property.rent_amount
                                ? `$${property.rent_amount}`
                                : '—'
                        }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Status</p>
                    <p class="capitalize">{{ property.status }}</p>
                </div>
            </CardContent>
        </Card>

        <!-- Multi-unit rental: details live on each unit -->
        <div v-else class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-medium">Units</h2>
                <Button as-child variant="outline">
                    <Link :href="createUnit(property.id)">
                        <Plus />
                        Add unit
                    </Link>
                </Button>
            </div>

            <p
                v-if="!property.units?.length"
                class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
            >
                No units yet. Add the first one.
            </p>

            <Card v-for="unit in property.units" :key="unit.id">
                <CardContent
                    class="flex items-center justify-between gap-4 py-4"
                >
                    <div>
                        <p class="font-medium">{{ unit.label }}</p>
                        <p class="text-sm text-muted-foreground">
                            {{ unit.bedrooms ?? '—' }} bd ·
                            {{ unit.bathrooms ?? '—' }} ba ·
                            {{
                                unit.rent_amount
                                    ? `$${unit.rent_amount}/mo`
                                    : 'no rent set'
                            }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge variant="outline" class="capitalize">{{
                            unit.status
                        }}</Badge>
                        <Button as-child size="icon" variant="ghost">
                            <Link :href="editUnit(unit.id)"><Pencil /></Link>
                        </Button>
                        <Button
                            size="icon"
                            variant="ghost"
                            @click="destroyUnit(unit)"
                        >
                            <Trash2 />
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
