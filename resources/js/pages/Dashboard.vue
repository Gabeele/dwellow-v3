<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as propertiesIndex } from '@/routes/properties';

/**
 * Portfolio summary for landlords. `null` for users who don't hold the
 * landlord role — the page then shows an honest welcome instead of numbers.
 */
interface DashboardStats {
    properties: number;
    units: number;
    occupied: number;
    available: number;
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
                class="rounded-lg border border-dashed border-border bg-card p-8 shadow-card"
            >
                <h2 class="text-base font-semibold text-foreground">
                    Screening home — coming soon
                </h2>
                <p class="mt-2 max-w-prose text-sm text-muted-foreground">
                    This space is reserved for your screening workflow:
                    applications, background checks, and decisions at a glance.
                    For now, manage your portfolio from the properties area.
                </p>
            </div>
        </div>
    </div>
</template>
