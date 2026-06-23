<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { FileText, Inbox } from '@lucide/vue';
import DataTable from '@/components/DataTable.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableRow from '@/components/TableRow.vue';
import { applicationStatusBadge } from '@/lib/applicationStatus';
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

interface PaginatedApplications {
    data: ApplicationRow[];
}

defineProps<{
    applications: PaginatedApplications;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Applications', href: '#' }],
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
                No applications yet. Share a unit's application link to start
                collecting applicants.
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
    </div>
</template>
