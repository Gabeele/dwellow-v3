<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    Check,
    ClipboardList,
    FileSearch,
    Link2,
    Minus,
    Scale,
    Send,
    ShieldCheck,
    Sparkles,
} from '@lucide/vue';
import type { Component } from 'vue';
import Diamond from '@/components/Diamond.vue';
import Eyebrow from '@/components/Eyebrow.vue';
import MarketingCta from '@/components/marketing/MarketingCta.vue';
import MarketingFaq from '@/components/marketing/MarketingFaq.vue';
import ScoreGauge from '@/components/ScoreGauge.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { register } from '@/routes';

interface Step {
    title: string;
    description: string;
}

interface Feature {
    title: string;
    description: string;
}

interface Stat {
    value: string;
    label: string;
    detail: string;
}

interface RoadmapPhase {
    phase: string;
    title: string;
    items: string[];
    current: boolean;
}

interface ComparisonRow {
    capability: string;
    cells: string[];
}

interface Comparison {
    columns: string[];
    rows: ComparisonRow[];
}

interface FaqItem {
    question: string;
    answer: string;
}

interface Seo {
    title: string;
    description: string;
    url: string;
    image: string;
}

const props = defineProps<{
    seo: Seo;
    stats: Stat[];
    steps: Step[];
    features: Feature[];
    comparison: Comparison;
    faq: FaqItem[];
    roadmap: RoadmapPhase[];
}>();

const stepIcons: Component[] = [
    Building2,
    ClipboardList,
    Link2,
    FileSearch,
    Scale,
];

const featureIcons: Component[] = [ShieldCheck, Send, Scale];

// The evidence rows shown inside the hero Score card.
const evidenceRows = [
    { label: 'Income verified', value: '3.2× rent', tone: 'success' },
    { label: 'Rental history', value: 'No flags', tone: 'success' },
    { label: 'References', value: '2 of 2 replied', tone: 'success' },
];

const trustPoints = [
    'No bureau account',
    'No spreadsheets',
    'No applicant logins',
];

function cellTone(cell: string): 'success' | 'warning' | 'muted' {
    if (cell === 'yes') {
        return 'success';
    }

    if (cell === 'partial') {
        return 'warning';
    }

    return 'muted';
}
</script>

<template>
    <PublicLayout :title="props.seo.title">
        <!-- Hero -->
        <section class="mx-auto w-full max-w-6xl px-6 pt-16 pb-20 lg:pt-24">
            <div
                class="grid items-center gap-12 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <Diamond class="text-success" />
                        <Eyebrow>For landlords · 1–20 units</Eyebrow>
                    </div>
                    <h1
                        class="mt-5 text-34 leading-[1.08] font-semibold tracking-tight text-balance lg:text-[2.75rem] lg:leading-[1.05]"
                    >
                        Screen tenants on evidence,
                        <span class="text-muted-foreground">not instinct.</span>
                    </h1>
                    <p
                        class="mt-6 max-w-md text-17 leading-relaxed text-muted-foreground"
                    >
                        Dwellow turns every application into one comparable
                        Score — reading documents, checking references, and
                        ranking applicants against your criteria. You make the
                        call; we make sure it's an informed one.
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <Link
                            :href="register()"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-card transition-opacity hover:opacity-90"
                        >
                            Start screening — free
                            <ArrowRight :size="16" />
                        </Link>
                        <Link
                            href="/docs"
                            class="rounded-md border border-border px-5 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-card"
                        >
                            See how it works
                        </Link>
                    </div>
                    <ul
                        class="mt-8 flex flex-wrap items-center gap-x-5 gap-y-2 font-mono text-[11px] tracking-wide text-muted-foreground uppercase"
                    >
                        <li
                            v-for="point in trustPoints"
                            :key="point"
                            class="flex items-center gap-1.5"
                        >
                            <Check :size="13" class="text-success" />
                            {{ point }}
                        </li>
                    </ul>
                </div>

                <!-- Signature artifact: the evidence-backed Score -->
                <div class="relative">
                    <div
                        class="pointer-events-none absolute -inset-8 -z-10 rounded-[2rem] bg-success/5 blur-3xl"
                    />
                    <div
                        class="bg-gradient-brand overflow-hidden rounded-2xl border border-border shadow-card-md"
                    >
                        <div
                            class="flex items-center justify-between border-b border-border px-6 py-4"
                        >
                            <div>
                                <p class="text-sm font-medium">
                                    Applicant review
                                </p>
                                <p
                                    class="font-mono text-[11px] tracking-wide text-muted-foreground"
                                >
                                    123 Maple Street · Unit 2
                                </p>
                            </div>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-success px-3 py-1 text-xs font-medium text-success-foreground"
                            >
                                <Check :size="13" />
                                Recommended
                            </span>
                        </div>

                        <div class="flex items-center gap-6 px-6 py-6">
                            <ScoreGauge :score="86" class="scale-[0.78]" />
                            <div class="min-w-0">
                                <Eyebrow>Match score</Eyebrow>
                                <p class="mt-1 text-sm font-medium">
                                    Strong fit for a 12-month lease
                                </p>
                                <p
                                    class="mt-1 text-13 leading-relaxed text-muted-foreground"
                                >
                                    Scored against your income, history and
                                    reference criteria.
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2 px-6">
                            <div
                                v-for="row in evidenceRows"
                                :key="row.label"
                                class="flex items-center justify-between rounded-lg border border-border bg-background/60 px-4 py-2.5"
                            >
                                <span
                                    class="flex items-center gap-2 text-13 text-muted-foreground"
                                >
                                    <Diamond class="text-success" :size="7" />
                                    {{ row.label }}
                                </span>
                                <span class="text-13 font-medium tabular-nums">
                                    {{ row.value }}
                                </span>
                            </div>
                        </div>

                        <div class="px-6 pt-4 pb-6">
                            <div
                                class="flex items-start gap-2.5 rounded-lg bg-ai-tint px-4 py-3 text-13 leading-relaxed text-ai-tint-foreground"
                            >
                                <Sparkles :size="15" class="mt-0.5 shrink-0" />
                                <span>
                                    <span class="font-medium"
                                        >AI summary —</span
                                    >
                                    Stable income and clean history. Low risk
                                    for a 12-month lease.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- The cost of guessing -->
        <section class="border-y border-border bg-card/40">
            <div class="mx-auto w-full max-w-6xl px-6 py-16">
                <div class="max-w-xl">
                    <Eyebrow>The cost of guessing</Eyebrow>
                    <h2
                        class="mt-3 text-22 leading-snug font-semibold tracking-tight"
                    >
                        Picking the wrong tenant is the most expensive mistake a
                        landlord can make.
                    </h2>
                </div>
                <dl
                    class="mt-10 grid gap-px overflow-hidden rounded-xl border border-border bg-border sm:grid-cols-3"
                >
                    <div
                        v-for="stat in stats"
                        :key="stat.label"
                        class="bg-card p-6"
                    >
                        <dt
                            class="text-34 font-semibold tracking-tight tabular-nums"
                        >
                            {{ stat.value }}
                        </dt>
                        <dd class="mt-2">
                            <p class="text-sm font-medium text-foreground">
                                {{ stat.label }}
                            </p>
                            <p
                                class="mt-1 text-13 leading-relaxed text-muted-foreground"
                            >
                                {{ stat.detail }}
                            </p>
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <!-- How it works -->
        <section class="mx-auto w-full max-w-6xl px-6 py-20">
            <div class="max-w-xl">
                <Eyebrow>How it works</Eyebrow>
                <h2
                    class="mt-3 text-28 leading-tight font-semibold tracking-tight"
                >
                    From listing to lease in five steps
                </h2>
                <p class="mt-4 text-sm leading-relaxed text-muted-foreground">
                    A linear path, not a dashboard you have to learn. Each step
                    hands off to the next automatically.
                </p>
            </div>

            <ol
                class="mt-12 grid gap-px overflow-hidden rounded-xl border border-border bg-border md:grid-cols-2 lg:grid-cols-5"
            >
                <li
                    v-for="(step, index) in steps"
                    :key="step.title"
                    class="flex flex-col bg-card p-6"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <component :is="stepIcons[index]" :size="18" />
                        </span>
                        <span class="font-mono text-13 text-muted-foreground">
                            {{ String(index + 1).padStart(2, '0') }}
                        </span>
                    </div>
                    <h3 class="mt-5 text-sm font-semibold">{{ step.title }}</h3>
                    <p
                        class="mt-2 text-13 leading-relaxed text-muted-foreground"
                    >
                        {{ step.description }}
                    </p>
                </li>
            </ol>
        </section>

        <!-- Comparison -->
        <section class="border-t border-border bg-card/40">
            <div class="mx-auto w-full max-w-6xl px-6 py-20">
                <div class="max-w-xl">
                    <Eyebrow>Dwellow vs. the alternatives</Eyebrow>
                    <h2
                        class="mt-3 text-28 leading-tight font-semibold tracking-tight"
                    >
                        Better than a spreadsheet. Simpler than a bureau.
                    </h2>
                    <p
                        class="mt-4 text-sm leading-relaxed text-muted-foreground"
                    >
                        Most landlords screen with email threads and gut feel,
                        or pay for clunky legacy services. Here's where Dwellow
                        lands.
                    </p>
                </div>

                <div
                    class="mt-10 overflow-hidden rounded-xl border border-border bg-card shadow-card"
                >
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-border">
                                <th
                                    class="px-5 py-4 text-13 font-semibold text-foreground"
                                    scope="col"
                                >
                                    Capability
                                </th>
                                <th
                                    v-for="(column, i) in comparison.columns"
                                    :key="column"
                                    class="px-3 py-4 text-center text-13 font-semibold"
                                    :class="
                                        i === 0
                                            ? 'text-foreground'
                                            : 'text-muted-foreground'
                                    "
                                    scope="col"
                                >
                                    <span
                                        v-if="i === 0"
                                        class="inline-flex items-center gap-1.5"
                                    >
                                        <Diamond
                                            class="text-success"
                                            :size="7"
                                        />
                                        {{ column }}
                                    </span>
                                    <template v-else>{{ column }}</template>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="row in comparison.rows"
                                :key="row.capability"
                                class="border-b border-border last:border-0"
                            >
                                <th
                                    class="px-5 py-3.5 text-13 font-normal text-foreground"
                                    scope="row"
                                >
                                    {{ row.capability }}
                                </th>
                                <td
                                    v-for="(cell, i) in row.cells"
                                    :key="i"
                                    class="px-3 py-3.5"
                                    :class="i === 0 ? 'bg-success-tint/40' : ''"
                                >
                                    <div class="flex justify-center">
                                        <Check
                                            v-if="cellTone(cell) === 'success'"
                                            :size="17"
                                            class="text-success"
                                        />
                                        <span
                                            v-else-if="
                                                cellTone(cell) === 'warning'
                                            "
                                            class="text-13 font-medium text-warning"
                                        >
                                            Limited
                                        </span>
                                        <Minus
                                            v-else
                                            :size="16"
                                            class="text-muted-foreground/50"
                                        />
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="mx-auto w-full max-w-6xl px-6 py-20">
            <div class="max-w-xl">
                <Eyebrow>Why dwellow</Eyebrow>
                <h2
                    class="mt-3 text-28 leading-tight font-semibold tracking-tight"
                >
                    Built for the way small landlords actually work
                </h2>
            </div>

            <div class="mt-12 grid gap-6 md:grid-cols-3">
                <div
                    v-for="(feature, index) in features"
                    :key="feature.title"
                    class="rounded-xl border border-border bg-card p-6 shadow-card"
                >
                    <div
                        class="flex size-10 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                    >
                        <component :is="featureIcons[index]" :size="20" />
                    </div>
                    <h3 class="mt-4 text-17 font-semibold">
                        {{ feature.title }}
                    </h3>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        {{ feature.description }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Roadmap teaser -->
        <section class="border-t border-border bg-card/40">
            <div class="mx-auto w-full max-w-6xl px-6 py-20">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div class="max-w-xl">
                        <Eyebrow>Where we're headed</Eyebrow>
                        <h2
                            class="mt-3 text-28 leading-tight font-semibold tracking-tight"
                        >
                            Screening today, your whole rental business next
                        </h2>
                    </div>
                    <Link
                        href="/roadmap"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-foreground underline-offset-4 hover:underline"
                    >
                        See the full roadmap
                        <ArrowRight :size="15" />
                    </Link>
                </div>

                <div class="mt-12 grid gap-6 md:grid-cols-3">
                    <div
                        v-for="phase in roadmap"
                        :key="phase.phase"
                        class="relative rounded-xl border border-border bg-card p-6"
                        :class="phase.current ? 'ring-1 ring-success/40' : ''"
                    >
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="
                                phase.current
                                    ? 'bg-success/15 text-success'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            {{ phase.phase }}
                        </span>
                        <h3 class="mt-4 text-17 font-semibold">
                            {{ phase.title }}
                        </h3>
                        <ul class="mt-4 space-y-2.5">
                            <li
                                v-for="item in phase.items"
                                :key="item"
                                class="flex items-start gap-2.5 text-13 text-muted-foreground"
                            >
                                <span
                                    class="mt-1.5 size-1.5 shrink-0 rounded-full"
                                    :class="
                                        phase.current
                                            ? 'bg-success'
                                            : 'bg-border'
                                    "
                                />
                                <span>{{ item }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ -->
        <MarketingFaq :items="faq" />

        <!-- Closing CTA -->
        <MarketingCta />
    </PublicLayout>
</template>
