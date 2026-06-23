<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown, Link2, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, reactive } from 'vue';
import PropertyController from '@/actions/App/Http/Controllers/PropertyController';
import UnitController from '@/actions/App/Http/Controllers/UnitController';
import DataTable from '@/components/DataTable.vue';
import MetricCard from '@/components/MetricCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableRow from '@/components/TableRow.vue';
import { Button } from '@/components/ui/button';
import UnitScreeningPanel from '@/components/UnitScreeningPanel.vue';
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

const isMultiUnit = computed(() => props.property.rental_type === 'multi_unit');

const units = computed<Unit[]>(() => props.property.units ?? []);

const occupiedUnits = computed(() =>
    units.value.filter((unit) => unit.status === 'occupied'),
);

const availableUnits = computed(() =>
    units.value.filter((unit) => unit.status === 'available'),
);

/** Number of rentable spaces: one per unit for multi-unit, otherwise the property itself. */
const spaceCount = computed(() => (isMultiUnit.value ? units.value.length : 1));

/** Spaces currently occupied. */
const occupiedCount = computed(() =>
    isMultiUnit.value
        ? occupiedUnits.value.length
        : props.property.status === 'occupied'
          ? 1
          : 0,
);

/** Spaces currently available to rent. */
const vacantCount = computed(() =>
    isMultiUnit.value
        ? availableUnits.value.length
        : props.property.status === 'available'
          ? 1
          : 0,
);

/** Monthly rent roll from occupied units (multi-unit) or the property rent (whole). */
const rentRoll = computed(() => {
    if (isMultiUnit.value) {
        return occupiedUnits.value.reduce(
            (sum, unit) => sum + Number(unit.rent_amount ?? 0),
            0,
        );
    }

    return Number(props.property.rent_amount ?? 0);
});

const currency = new Intl.NumberFormat('en-CA', {
    style: 'currency',
    currency: 'CAD',
    maximumFractionDigits: 0,
});

function formatCurrency(value: number): string {
    return currency.format(value);
}

function unitRent(unit: Unit): string {
    return unit.rent_amount
        ? `${formatCurrency(Number(unit.rent_amount))}/mo`
        : '—';
}

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

/** Tracks which unit rows have their screening (links) panel expanded. */
const expandedUnits = reactive<Set<number>>(new Set());

function toggleScreening(unit: Unit): void {
    if (expandedUnits.has(unit.id)) {
        expandedUnits.delete(unit.id);
    } else {
        expandedUnits.add(unit.id);
    }
}
</script>

<template>
    <Head :title="property.name || property.address_line1" />

    <div class="flex h-full flex-1 flex-col gap-8 p-6 lg:p-10">
        <PageHeader
            :title="property.name || property.address_line1"
            :eyebrow="isMultiUnit ? 'Multi-unit' : 'Whole rental'"
            :back="{ label: 'All properties', href: index() }"
        >
            <template #actions>
                <Button as-child variant="outline">
                    <Link :href="edit(property.id)"><Pencil />Edit</Link>
                </Button>
                <Button
                    variant="outline"
                    class="text-destructive"
                    @click="destroyProperty"
                >
                    <Trash2 />Delete
                </Button>
            </template>
        </PageHeader>

        <p class="-mt-4 text-sm text-muted-foreground">
            {{ fullAddress(property) }}
        </p>

        <!-- METRICS -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <template v-if="isMultiUnit">
                <MetricCard label="Units" :value="spaceCount" />
                <MetricCard label="Occupied" :value="occupiedCount" />
                <MetricCard label="Vacant" :value="vacantCount" />
                <MetricCard
                    label="Monthly rent"
                    :value="formatCurrency(rentRoll)"
                    detail="from occupied units"
                />
            </template>
            <template v-else>
                <MetricCard
                    label="Bedrooms"
                    :value="property.bedrooms ?? '—'"
                />
                <MetricCard
                    label="Bathrooms"
                    :value="property.bathrooms ?? '—'"
                />
                <MetricCard
                    label="Monthly rent"
                    :value="formatCurrency(rentRoll)"
                />
                <MetricCard label="Status" value="">
                    <template #tag>
                        <StatusBadge :status="property.status" />
                    </template>
                </MetricCard>
            </template>
        </div>

        <!-- MULTI-UNIT: unit list -->
        <div v-if="isMultiUnit" class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h2 class="text-17 font-semibold tracking-tight">Units</h2>
                    <span class="font-mono text-xs text-muted-foreground">
                        {{ units.length }}
                    </span>
                </div>
                <Button as-child variant="outline">
                    <Link :href="createUnit(property.id)">
                        <Plus />Add unit
                    </Link>
                </Button>
            </div>

            <p
                v-if="!units.length"
                class="rounded-lg border border-dashed border-border bg-card/50 p-10 text-center text-sm text-muted-foreground"
            >
                No units yet. Add the first one.
            </p>

            <DataTable v-else>
                <template #head>
                    <th class="px-4 py-3 font-medium">Label</th>
                    <th class="px-4 py-3 font-medium">Bedrooms</th>
                    <th class="px-4 py-3 font-medium">Bathrooms</th>
                    <th class="px-4 py-3 font-medium">Rent</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Screening</th>
                    <th class="px-4 py-3 text-right font-medium">Actions</th>
                </template>

                <template v-for="unit in units" :key="unit.id">
                    <TableRow>
                        <td class="px-4 py-3 font-medium">{{ unit.label }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ unit.bedrooms ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">
                            {{ unit.bathrooms ?? '—' }}
                        </td>
                        <td class="px-4 py-3 font-mono text-muted-foreground">
                            {{ unitRent(unit) }}
                        </td>
                        <td class="px-4 py-3">
                            <StatusBadge :status="unit.status" />
                        </td>
                        <td class="px-4 py-3">
                            <Button
                                size="sm"
                                variant="ghost"
                                class="text-muted-foreground"
                                @click="toggleScreening(unit)"
                            >
                                <Link2 />
                                {{ unit.application_links?.length ?? 0 }}
                                <ChevronDown
                                    class="transition-transform"
                                    :class="
                                        expandedUnits.has(unit.id) &&
                                        'rotate-180'
                                    "
                                />
                            </Button>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <Button
                                    as-child
                                    size="icon"
                                    variant="ghost"
                                    class="text-muted-foreground"
                                >
                                    <Link :href="editUnit(unit.id)">
                                        <Pencil />
                                    </Link>
                                </Button>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    class="text-muted-foreground"
                                    @click="destroyUnit(unit)"
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </td>
                    </TableRow>
                    <tr v-if="expandedUnits.has(unit.id)" class="border-b border-border last:border-b-0">
                        <td colspan="7" class="bg-muted/20 px-4 py-4">
                            <UnitScreeningPanel :unit="unit" />
                        </td>
                    </tr>
                </template>
            </DataTable>
        </div>
    </div>
</template>
