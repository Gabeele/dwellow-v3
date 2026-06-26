<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Building2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DataTable from '@/components/DataTable.vue';
import EmptyState from '@/components/EmptyState.vue';
import FilterTabs from '@/components/FilterTabs.vue';
import type { FilterTab } from '@/components/FilterTabs.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableRow from '@/components/TableRow.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    availableSpaces,
    occupiedSpaces,
    propertyOccupancy,
    spaceCount,
} from '@/lib/occupancy';
import type { OccupancyStatus } from '@/lib/occupancy';
import { create, index, show } from '@/routes/properties';
import type { Property } from '@/types/property';

const props = defineProps<{
    properties: Property[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Properties', href: index() }],
    },
});

const totalUnits = computed(() =>
    props.properties.reduce((sum, property) => sum + spaceCount(property), 0),
);

const totalOccupied = computed(() =>
    props.properties.reduce(
        (sum, property) => sum + occupiedSpaces(property),
        0,
    ),
);

const totalAvailable = computed(() =>
    props.properties.reduce(
        (sum, property) => sum + availableSpaces(property),
        0,
    ),
);

type TabValue = 'all' | OccupancyStatus;

const TAB_VALUES: readonly TabValue[] = [
    'all',
    'occupied',
    'available',
    'unavailable',
];

/**
 * Seed the active filter from a `?status=` query param so links from
 * elsewhere (e.g. the dashboard portfolio cards) can deep-link straight
 * into a filtered table. Falls back to "all" for missing or unknown values.
 */
function initialTab(): TabValue {
    const query = usePage().url.split('?')[1] ?? '';
    const status = new URLSearchParams(query).get('status');

    return TAB_VALUES.includes(status as TabValue)
        ? (status as TabValue)
        : 'all';
}

const activeTab = ref<TabValue>(initialTab());

/**
 * Keep the URL in sync with the active filter so the table view is
 * shareable and matches deep links from the dashboard. Filtering is
 * entirely client-side, so we rewrite the URL via the History API
 * (preserving Inertia's page state) rather than issuing a server visit.
 */
watch(activeTab, (value) => {
    const url = index({
        query: { status: value === 'all' ? undefined : value },
    }).url;

    window.history.replaceState(window.history.state, '', url);
});

function countByStatus(status: OccupancyStatus): number {
    return props.properties.filter(
        (property) => propertyOccupancy(property) === status,
    ).length;
}

const tabs = computed<FilterTab[]>(() => [
    { value: 'all', label: 'All', count: props.properties.length },
    { value: 'occupied', label: 'Occupied', count: countByStatus('occupied') },
    {
        value: 'available',
        label: 'Available',
        count: countByStatus('available'),
    },
    {
        value: 'unavailable',
        label: 'Unavailable',
        count: countByStatus('unavailable'),
    },
]);

const filteredProperties = computed(() => {
    if (activeTab.value === 'all') {
        return props.properties;
    }

    return props.properties.filter(
        (property) => propertyOccupancy(property) === activeTab.value,
    );
});

function initials(property: Property): string {
    const source = property.name || property.address_line1 || '';

    return (
        source
            .split(/\s+/)
            .filter(Boolean)
            .slice(0, 2)
            .map((word) => word[0]?.toUpperCase() ?? '')
            .join('') || '?'
    );
}

function primaryText(property: Property): string {
    return property.name || property.address_line1;
}

function cityLine(property: Property): string {
    return [property.city, property.region].filter(Boolean).join(', ');
}

function spacesLabel(property: Property): string {
    if (property.rental_type === 'multi_unit') {
        const count = property.units_count ?? 0;

        return `${count} ${count === 1 ? 'unit' : 'units'}`;
    }

    return 'Whole';
}

function rentOrType(property: Property): string {
    if (property.rental_type === 'whole' && property.rent_amount) {
        return `$${property.rent_amount}`;
    }

    return property.type;
}

function openProperty(property: Property): void {
    router.visit(show(property.id));
}
</script>

<template>
    <Head title="Properties" />

    <div class="flex h-full flex-1 flex-col p-6 lg:p-10">
        <PageHeader eyebrow="Portfolio" title="Properties">
            <template #actions>
                <Button as-child>
                    <Link :href="create()">Add property</Link>
                </Button>
            </template>
        </PageHeader>

        <EmptyState
            v-if="properties.length === 0"
            :icon="Building2"
            tone="primary"
        >
            You haven't added any properties yet.
            <template #action>
                <Button as-child variant="outline">
                    <Link :href="create()">Add property</Link>
                </Button>
            </template>
        </EmptyState>

        <div v-else class="flex flex-col gap-6">
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Properties" :value="properties.length" />
                <StatCard
                    label="Units"
                    :value="totalUnits"
                    context="Rentable spaces"
                />
                <StatCard
                    label="Occupied"
                    :value="totalOccupied"
                    tone="success"
                />
                <StatCard
                    label="Available"
                    :value="totalAvailable"
                    tone="warning"
                />
            </div>

            <FilterTabs v-model="activeTab" :tabs="tabs" />

            <DataTable>
                <template #head>
                    <th class="px-4 py-3 font-medium">Property</th>
                    <th class="px-4 py-3 font-medium">Spaces</th>
                    <th class="px-4 py-3 font-medium">Rent / Type</th>
                    <th class="px-4 py-3 text-right font-medium">Status</th>
                </template>

                <TableRow
                    v-for="property in filteredProperties"
                    :key="property.id"
                    clickable
                    @click="openProperty(property)"
                >
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <Avatar>
                                <AvatarFallback class="text-xs font-medium">
                                    {{ initials(property) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="flex flex-col">
                                <span class="font-medium text-foreground">
                                    {{ primaryText(property) }}
                                </span>
                                <span class="text-13 text-muted-foreground">
                                    {{ cityLine(property) }}
                                </span>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">
                        {{ spacesLabel(property) }}
                    </td>
                    <td class="px-4 py-3 text-muted-foreground">
                        {{ rentOrType(property) }}
                    </td>
                    <td class="px-4 py-3 text-right">
                        <StatusBadge :status="propertyOccupancy(property)" />
                    </td>
                </TableRow>
            </DataTable>
        </div>
    </div>
</template>
