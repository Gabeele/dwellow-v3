<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FileText, Users } from '@lucide/vue';
import { computed } from 'vue';
import DataTable from '@/components/DataTable.vue';
import EmptyState from '@/components/EmptyState.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableRow from '@/components/TableRow.vue';
import { applicationStatusBadge } from '@/lib/applicationStatus';
import { show as showApplicant } from '@/routes/applicants';
import { index, show } from '@/routes/properties';
import type { Paginated } from '@/types';
import type { ApplicationRow, Property, Unit } from '@/types/property';

const props = defineProps<{
    property: Property;
    unit: Unit;
    applications: Paginated<ApplicationRow>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: index() },
            { title: 'Applicants', href: '#' },
        ],
    },
});

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

const unitContext = computed(() =>
    [props.property.name || props.property.address_line1, props.unit.label]
        .filter(Boolean)
        .join(' · '),
);
</script>

<template>
    <Head :title="`Applicants — ${unit.label}`" />

    <div class="flex h-full flex-1 flex-col p-6 lg:p-10">
        <PageHeader
            eyebrow="Screening"
            :title="`Applicants — ${unit.label}`"
            :back="{ label: 'Back to property', href: show(property.id) }"
        />

        <p class="-mt-4 mb-6 text-sm text-muted-foreground">
            {{ unitContext }}
        </p>

        <EmptyState v-if="applications.data.length === 0" :icon="Users">
            No one has applied for this unit yet. Share its application link to
            start collecting applicants.
        </EmptyState>

        <DataTable v-else>
            <template #head>
                <th class="px-4 py-3 font-medium">Applicant</th>
                <th class="px-4 py-3 font-medium">Submitted</th>
                <th class="px-4 py-3 font-medium">Documents</th>
                <th class="px-4 py-3 text-right font-medium">Status</th>
            </template>

            <TableRow
                v-for="application in applications.data"
                :key="application.id"
                clickable
                @click="$inertia.visit(showApplicant(application.id).url)"
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
                    {{ submittedOn(application) }}
                </td>
                <td class="px-4 py-3 text-muted-foreground">
                    <span class="inline-flex items-center gap-1.5">
                        <FileText class="size-3.5" />
                        {{ application.documents_count ?? 0 }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <StatusBadge
                        :variant="
                            applicationStatusBadge(application.status).variant
                        "
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
