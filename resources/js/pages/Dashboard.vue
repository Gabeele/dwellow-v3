<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as applicationsIndex } from '@/routes/applications';
import { index as propertiesIndex } from '@/routes/properties';
import { index as applicantsIndex } from '@/routes/units/applicants';

/**
 * Portfolio summary for landlords. `null` for users who don't hold the
 * landlord role — the page then shows an honest welcome instead of numbers.
 */
interface DashboardStats {
    properties: number;
    units: number;
    occupied: number;
    available: number;
    new_applications: number;
    total_applications: number;
    busiest_unit: {
        id: number;
        label: string;
        applications_count: number;
    } | null;
}

defineProps<{
    stats: DashboardStats | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const page = usePage();

const firstName = computed(
    () => page.props.auth.user.name.trim().split(/\s+/)[0] ?? '',
);

const welcomeTitle = computed(() =>
    firstName.value ? `Welcome back, ${firstName.value}` : 'Welcome back',
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col p-6 lg:p-10">
        <PageHeader eyebrow="Dashboard" :title="welcomeTitle">
            <template v-if="stats" #actions>
                <Button as-child variant="outline">
                    <Link :href="propertiesIndex()">View properties</Link>
                </Button>
            </template>
        </PageHeader>

        <div class="flex flex-col gap-6">
            <div v-if="stats" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Properties" :value="stats.properties" />
                <StatCard
                    label="Units"
                    :value="stats.units"
                    context="Rentable spaces"
                />
                <StatCard
                    label="Occupied"
                    :value="stats.occupied"
                    tone="success"
                />
                <StatCard
                    label="Available"
                    :value="stats.available"
                    tone="warning"
                />
                <Link
                    :href="applicationsIndex({ query: { status: 'new' } })"
                    class="rounded-lg transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <StatCard
                        label="New applications"
                        :value="stats.new_applications"
                        :tone="stats.new_applications > 0 ? 'ai' : 'muted'"
                        context="Awaiting your review"
                    />
                </Link>
                <Link
                    :href="applicationsIndex()"
                    class="rounded-lg transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <StatCard
                        label="Total applications"
                        :value="stats.total_applications"
                        context="View all applications"
                    />
                </Link>
            </div>

            <div
                v-else
                class="rounded-lg border border-border bg-card p-8 shadow-card"
            >
                <h2 class="text-lg font-semibold text-foreground">
                    Welcome to Dwellow
                </h2>
                <p class="mt-2 max-w-prose text-sm text-muted-foreground">
                    Your account is all set up. Tenant screening tools will
                    appear here as they become available.
                </p>
            </div>

            <div
                v-if="stats?.busiest_unit"
                class="flex flex-col gap-4 rounded-lg border border-border bg-card p-8 shadow-card sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2 class="text-base font-semibold text-foreground">
                        Applicant activity
                    </h2>
                    <p class="mt-2 max-w-prose text-sm text-muted-foreground">
                        <strong class="text-foreground">{{
                            stats.busiest_unit.label
                        }}</strong>
                        has the most interest with
                        {{ stats.busiest_unit.applications_count }}
                        {{
                            stats.busiest_unit.applications_count === 1
                                ? 'application'
                                : 'applications'
                        }}. Jump straight to its applicants to review them.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="applicantsIndex(stats.busiest_unit.id)">
                        Review applicants
                    </Link>
                </Button>
            </div>
        </div>
    </div>
</template>
