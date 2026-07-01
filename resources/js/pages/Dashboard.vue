<script setup lang="ts">
import { Head, Link, router, usePage, usePoll } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import IconRobot from '@/components/icons/IconRobot.vue';
import DataTable from '@/components/DataTable.vue';
import EmptyState from '@/components/EmptyState.vue';
import Eyebrow from '@/components/Eyebrow.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import StatCard from '@/components/StatCard.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import TableRow from '@/components/TableRow.vue';
import { Button } from '@/components/ui/button';
import { useNow } from '@/composables/useNow';
import { agentStatusVariant, formatAgentElapsed } from '@/lib/agentStatus';
import { applicationStatusBadge } from '@/lib/applicationStatus';
import { dashboard } from '@/routes';
import { index as applicationsIndex } from '@/routes/applications';
import { index as propertiesIndex } from '@/routes/properties';
import { index as applicantsIndex } from '@/routes/units/applicants';
import type { Paginated } from '@/types';
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

const props = defineProps<{
    stats: DashboardStats | null;
    // The landlord's agent runs, newest first, paginated 10 per page under the
    // `agentPage` query key. Empty for non-landlords (and landlords with no runs).
    agents: Paginated<AgentActivity>;
}>();

const appliedAtFormatter = new Intl.DateTimeFormat('en-CA', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
});

/** When the subject application was submitted, e.g. "Jun 30, 2026". */
function appliedAtLabel(agent: AgentActivity): string {
    return agent.subject_submitted_at
        ? appliedAtFormatter.format(new Date(agent.subject_submitted_at))
        : '—';
}

/** A run still in flight — drives the pulsing status indicator. */
function isActiveStatus(status: AgentActivity['status']): boolean {
    return status === 'pending' || status === 'processing';
}

/**
 * Navigate to an agent run's subject when its row is clicked. A run whose
 * subject has no resolvable URL is a no-op rather than a dead navigation.
 */
function openAgent(agent: AgentActivity): void {
    if (agent.url) {
        router.visit(agent.url);
    }
}

/** True while any listed run is still pending or processing. */
const hasActiveAgents = computed(() =>
    props.agents.data.some((agent) => isActiveStatus(agent.status)),
);

// A live clock drives the Elapsed column while a run is in flight, then pauses
// the instant every run settles.
const now = useNow(hasActiveAgents);

// Poll just the `agents` prop while a run is active; stop once everything
// settles so an idle dashboard makes no background requests. The reload flips
// `hasActiveAgents` to false as soon as the last run completes.
const agentsPoll = usePoll(5000, { only: ['agents'] }, { autoStart: false });

watch(
    hasActiveAgents,
    (active) => (active ? agentsPoll.start() : agentsPoll.stop()),
    { immediate: true },
);

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

                    <EmptyState
                        v-if="agents.data.length === 0"
                        :icon="IconRobot"
                    >
                        No agent activity yet. Scores appear here as
                        applications come in.
                    </EmptyState>

                    <template v-else>
                        <DataTable>
                            <template #head>
                                <!-- The application the agent analysed … -->
                                <th class="px-4 py-3 font-medium">Analysis</th>
                                <th class="px-4 py-3 font-medium">Applicant</th>
                                <th class="px-4 py-3 font-medium">Applied</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <!-- … then the agent run itself. -->
                                <th class="px-4 py-3 font-medium">Agent</th>
                                <th class="px-4 py-3 text-right font-medium">
                                    Elapsed
                                </th>
                            </template>

                            <TableRow
                                v-for="agent in agents.data"
                                :key="agent.id"
                                :clickable="!!agent.url"
                                @click="openAgent(agent)"
                            >
                                <td class="px-4 py-3">
                                    <span class="flex items-center gap-2.5">
                                        <span
                                            class="flex size-7 shrink-0 items-center justify-center rounded-md bg-ai-tint text-ai"
                                        >
                                            <IconRobot class="size-4" />
                                        </span>
                                        <span
                                            class="font-medium text-foreground"
                                        >
                                            {{
                                                agent.subject_type_label ??
                                                'Agent'
                                            }}
                                            Analysis
                                        </span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-foreground">
                                    {{ agent.subject_label ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">
                                    {{ appliedAtLabel(agent) }}
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge
                                        v-if="agent.subject_status"
                                        :variant="
                                            applicationStatusBadge(
                                                agent.subject_status,
                                            ).variant
                                        "
                                    >
                                        {{
                                            applicationStatusBadge(
                                                agent.subject_status,
                                            ).label
                                        }}
                                    </StatusBadge>
                                    <span v-else class="text-muted-foreground">
                                        —
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <StatusBadge
                                        :variant="
                                            agentStatusVariant(agent.status)
                                        "
                                    >
                                        <span
                                            class="inline-flex items-center gap-1.5"
                                        >
                                            <span
                                                v-if="
                                                    isActiveStatus(agent.status)
                                                "
                                                class="size-1.5 animate-pulse rounded-full bg-current"
                                            />
                                            {{ agent.status_label }}
                                        </span>
                                    </StatusBadge>
                                </td>
                                <td
                                    class="px-4 py-3 text-right text-muted-foreground tabular-nums"
                                >
                                    {{
                                        formatAgentElapsed(
                                            agent.started_at,
                                            agent.completed_at,
                                            now,
                                        )
                                    }}
                                </td>
                            </TableRow>
                        </DataTable>

                        <Pagination
                            :links="agents.links"
                            :from="agents.from"
                            :to="agents.to"
                            :total="agents.total"
                        />
                    </template>
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
