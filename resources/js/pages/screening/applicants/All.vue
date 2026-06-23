<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHeader from '@/components/PageHeader.vue';
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
</script>

<template>
    <Head title="Applications" />

    <div class="flex h-full flex-1 flex-col p-6 lg:p-10">
        <PageHeader eyebrow="Screening" title="Applications" />

        <ul class="flex flex-col gap-2">
            <li
                v-for="application in applications.data"
                :key="application.id"
            >
                <a :href="application.url" class="text-sm">
                    {{ application.applicant_name }} — {{ application.property_name }} · {{ application.unit_label }}
                </a>
            </li>
        </ul>
    </div>
</template>
