<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Download, FileText, Inbox, Search } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableRow from '@/components/TableRow.vue';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { applicationStatusBadge } from '@/lib/applicationStatus';
import {
    exportMethod as applicationsExport,
    index as applicationsIndex,
} from '@/routes/applications';
import type { Paginated } from '@/types';
import type { ApplicationStatus } from '@/types/property';

interface ApplicationRow {
    id: number;
    applicant_name: string;
    applicant_email: string;
    property_name: string;
    unit_label: string;
    submitted_at: string | null;
    status: ApplicationStatus;
    documents_count: number;
    url: string;
}

interface StatusOption {
    value: ApplicationStatus;
    label: string;
}

interface PropertyOption {
    id: number;
    name: string;
}

const props = defineProps<{
    applications: Paginated<ApplicationRow>;
    properties: PropertyOption[];
    statuses: StatusOption[];
    filters: {
        search: string;
        status: string;
        property: number | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Applications', href: '#' }],
    },
});

const ALL = 'all';

const search = ref(props.filters.search);
const status = ref(props.filters.status || ALL);
const property = ref(props.filters.property ? String(props.filters.property) : ALL);

const hasActiveFilters = computed(
    () => search.value !== '' || status.value !== ALL || property.value !== ALL,
);

function applyFilters(): void {
    router.get(
        applicationsIndex().url,
        {
            search: search.value || undefined,
            status: status.value === ALL ? undefined : status.value,
            property: property.value === ALL ? undefined : property.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['applications', 'filters'],
        },
    );
}

let searchTimeout: ReturnType<typeof setTimeout> | undefined;

watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 300);
});

watch([status, property], applyFilters);

function resetFilters(): void {
    search.value = '';
    status.value = ALL;
    property.value = ALL;
}

const dateFormatter = new Intl.DateTimeFormat('en-CA', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
});

function submittedOn(application: ApplicationRow): string {
    return application.submitted_at
        ? dateFormatter.format(new Date(application.submitted_at))
        : '—';
}

const statusLabel = computed(() =>
    status.value === ALL
        ? 'All statuses'
        : applicationStatusBadge(status.value as ApplicationStatus).label,
);

const propertyLabel = computed(() =>
    property.value === ALL
        ? 'All properties'
        : (props.properties.find((option) => String(option.id) === property.value)
              ?.name ?? 'All properties'),
);

const exportHref = computed(
    () =>
        applicationsExport({
            query: {
                search: search.value || undefined,
                status: status.value === ALL ? undefined : status.value,
                property: property.value === ALL ? undefined : property.value,
            },
        }).url,
);
</script>

<template>
    <Head title="Applications" />

    <div class="flex h-full flex-1 flex-col p-6 lg:p-10">
        <PageHeader
            eyebrow="Screening"
            title="Applications"
        />

        <p class="-mt-4 mb-6 text-sm text-muted-foreground">
            Every application across all of your units, newest first.
        </p>

        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1 sm:max-w-xs">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search by name or email"
                    class="pl-9"
                    aria-label="Search applications"
                />
            </div>

            <Select v-model="status">
                <SelectTrigger class="w-full sm:w-48" aria-label="Filter by status">
                    <SelectValue>{{ statusLabel }}</SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">All statuses</SelectItem>
                    <SelectItem
                        v-for="option in statuses"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="property">
                <SelectTrigger class="w-full sm:w-56" aria-label="Filter by property">
                    <SelectValue>{{ propertyLabel }}</SelectValue>
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ALL">All properties</SelectItem>
                    <SelectItem
                        v-for="option in properties"
                        :key="option.id"
                        :value="String(option.id)"
                    >
                        {{ option.name }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <button
                v-if="hasActiveFilters"
                type="button"
                class="text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                @click="resetFilters"
            >
                Clear
            </button>

            <a
                :href="exportHref"
                class="inline-flex items-center justify-center gap-2 rounded-md border border-border bg-card px-3 py-2 text-sm font-medium text-foreground shadow-card transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:ml-auto"
            >
                <Download class="size-4" />
                Export CSV
            </a>
        </div>

        <div
            v-if="applications.data.length === 0"
            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-border bg-card p-16 text-center shadow-card"
        >
            <div
                class="flex size-11 items-center justify-center rounded-xl bg-muted text-muted-foreground"
            >
                <Inbox class="size-5" />
            </div>
            <p class="text-sm text-muted-foreground">
                {{
                    hasActiveFilters
                        ? 'No applications match your filters.'
                        : "No applications yet. Share a unit's application link to start collecting applicants."
                }}
            </p>
        </div>

        <DataTable v-else>
            <template #head>
                <th class="px-4 py-3 font-medium">Applicant</th>
                <th class="px-4 py-3 font-medium">Property · Unit</th>
                <th class="px-4 py-3 font-medium">Submitted</th>
                <th class="px-4 py-3 font-medium">Documents</th>
                <th class="px-4 py-3 text-right font-medium">Status</th>
            </template>

            <TableRow
                v-for="application in applications.data"
                :key="application.id"
                clickable
                @click="router.visit(application.url)"
            >
                <td class="px-4 py-3">
                    <div class="flex flex-col">
                        <span class="font-medium text-foreground">
                            {{ application.applicant_name }}
                        </span>
                        <span class="text-13 text-muted-foreground">
                            {{ application.applicant_email }}
                        </span>
                    </div>
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ application.property_name }} · {{ application.unit_label }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    {{ submittedOn(application) }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    <span class="inline-flex items-center gap-1.5">
                        <FileText class="size-3.5" />
                        {{ application.documents_count }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <StatusBadge
                        :variant="applicationStatusBadge(application.status).variant"
                    >
                        {{ applicationStatusBadge(application.status).label }}
                    </StatusBadge>
                </td>
            </TableRow>
        </DataTable>

        <Pagination
            :links="applications.links"
            :from="applications.from"
            :to="applications.to"
            :total="applications.total"
        />
    </div>
</template>
