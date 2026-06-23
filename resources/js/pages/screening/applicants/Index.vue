<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FileText, Users } from '@lucide/vue';
import { computed } from 'vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableRow from '@/components/TableRow.vue';
import { applicationStatusBadge } from '@/lib/applicationStatus';
import { show as showApplicant } from '@/routes/applicants';
import { index, show } from '@/routes/properties';
import type { Application, Property, Unit } from '@/types/property';

const props = defineProps<{
    property: Property;
    unit: Unit;
    applications: Application[];
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

function applicantName(application: Application): string {
    return `${application.applicant_first_name} ${application.applicant_last_name}`.trim();
}

function submittedOn(application: Application): string {
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

        <div
            v-if="applications.length === 0"
            class="flex flex-1 flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-border bg-card p-16 text-center shadow-card"
        >
            <div
                class="flex size-11 items-center justify-center rounded-xl bg-muted text-muted-foreground"
            >
                <Users class="size-5" />
            </div>
            <p class="text-sm text-muted-foreground">
                No one has applied for this unit yet. Share its application link
                to start collecting applicants.
            </p>
        </div>

        <DataTable v-else>
            <template #head>
                <th class="px-4 py-3 font-medium">Applicant</th>
                <th class="px-4 py-3 font-medium">Submitted</th>
                <th class="px-4 py-3 font-medium">Documents</th>
                <th class="px-4 py-3 text-right font-medium">Status</th>
            </template>

            <TableRow
                v-for="application in applications"
                :key="application.id"
                clickable
                @click="$inertia.visit(showApplicant(application.id).url)"
            >
                <td class="px-4 py-3">
                    <div class="flex flex-col">
                        <span class="font-medium text-foreground">
                            {{ applicantName(application) }}
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
                        :variant="applicationStatusBadge(application.status).variant"
                    >
                        {{ applicationStatusBadge(application.status).label }}
                    </StatusBadge>
                </td>
            </TableRow>
        </DataTable>
    </div>
</template>
