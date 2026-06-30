<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Sparkles } from '@lucide/vue';
import { computed } from 'vue';
import DataTable from '@/components/DataTable.vue';
import EmptyState from '@/components/EmptyState.vue';
import Eyebrow from '@/components/Eyebrow.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableRow from '@/components/TableRow.vue';
import { Button } from '@/components/ui/button';
import { agentStatusVariant, formatAgentElapsed } from '@/lib/agentStatus';
import { dashboard } from '@/routes';
import { index as applicationsIndex } from '@/routes/applications';
import { index as propertiesIndex } from '@/routes/properties';
import { index as applicantsIndex } from '@/routes/units/applicants';
import type { AgentActivity } from '@/types/property';

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
    // Recent + active agent runs for the landlord, newest first. An empty
    // array for non-landlords (and landlords with no runs yet).
    agents: AgentActivity[];
}>();

/**
 * Navigate to an agent run's subject when its row is clicked. A run whose
 * subject has no resolvable URL is a no-op rather than a dead navigation.
 */
function openAgent(agent: AgentActivity): void {
    if (agent.url) {
        router.visit(agent.url);
    }
}

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

        <div class="flex flex-col gap-8">
            <template v-if="stats">
                <section class="flex flex-col gap-3">
                    <Eyebrow>Portfolio</Eyebrow>
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <Link
                            :href="propertiesIndex()"
                            class="rounded-lg transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <StatCard
                                label="Properties"
                                :value="stats.properties"
                            />
                        </Link>
                        <Link
                            :href="propertiesIndex()"
                            class="rounded-lg transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <StatCard label="Units" :value="stats.units" />
                        </Link>
                        <Link
                            :href="
                                propertiesIndex({
                                    query: { status: 'occupied' },
                                })
                            "
                            class="rounded-lg transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <StatCard
                                label="Occupied"
                                :value="stats.occupied"
                                tone="success"
                            />
                        </Link>
                        <Link
                            :href="
                                propertiesIndex({
                                    query: { status: 'available' },
                                })
                            "
                            class="rounded-lg transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <StatCard
                                label="Available"
                                :value="stats.available"
                                tone="warning"
                            />
                        </Link>
                    </div>
                </section>

                <section class="flex flex-col gap-3">
                    <Eyebrow>Applications</Eyebrow>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <Link
                            :href="
                                applicationsIndex({ query: { status: 'new' } })
                            "
                            class="rounded-lg transition hover:opacity-90 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <StatCard
                                label="New applications"
                                :value="stats.new_applications"
                                :tone="
                                    stats.new_applications > 0 ? 'ai' : 'muted'
                                "
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
                </section>

                <section class="flex flex-col gap-3">
                    <Eyebrow>Agents</Eyebrow>

                    <EmptyState v-if="agents.length === 0" :icon="Sparkles">
                        No agent activity yet. Scores appear here as
                        applications come in.
                    </EmptyState>

                    <DataTable v-else>
                        <template #head>
                            <th class="px-4 py-3 font-medium">Agent</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Elapsed
                            </th>
                        </template>

                        <TableRow
                            v-for="agent in agents"
                            :key="agent.id"
                            :clickable="!!agent.url"
                            @click="openAgent(agent)"
                        >
                            <td class="px-4 py-3">
                                <div class="flex flex-col">
                                    <span class="font-medium text-foreground">
                                        {{ agent.type_label }}
                                    </span>
                                    <span class="text-13 text-muted-foreground">
                                        {{ agent.subject_label ?? '—' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge
                                    :variant="agentStatusVariant(agent.status)"
                                >
                                    {{ agent.status_label }}
                                </StatusBadge>
                            </td>
                            <td
                                class="px-4 py-3 text-right text-muted-foreground"
                            >
                                {{
                                    formatAgentElapsed(
                                        agent.started_at,
                                        agent.completed_at,
                                    )
                                }}
                            </td>
                        </TableRow>
                    </DataTable>
                </section>
            </template>

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
