<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    ClipboardList,
    FileSearch,
    Link2,
    Scale,
    Send,
    ShieldCheck,
} from '@lucide/vue';
import type { Component } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { login, register } from '@/routes';

interface Step {
    title: string;
    description: string;
}

interface Feature {
    title: string;
    description: string;
}

interface RoadmapPhase {
    phase: string;
    title: string;
    items: string[];
    current: boolean;
}

defineProps<{
    steps: Step[];
    features: Feature[];
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
</script>

<template>
    <div class="bg-ambient flex min-h-screen flex-col text-foreground">
        <Head title="Tenant screening for small landlords" />

        <header class="sticky top-0 z-10 border-b border-border bg-background/80 backdrop-blur">
            <div
                class="mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-4"
            >
                <Link
                    :href="login()"
                    class="flex items-center"
                    aria-label="Dwellow home"
                >
                    <AppLogo />
                </Link>
                <nav class="flex items-center gap-2">
                    <Link
                        :href="login()"
                        class="rounded-md px-4 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-card transition-opacity hover:opacity-90"
                    >
                        Get started
                    </Link>
                </nav>
            </div>
        </header>

        <main class="flex-1">
            <!-- Hero -->
            <section class="mx-auto w-full max-w-5xl px-6 py-20 lg:py-28">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <p
                            class="mb-4 text-13 font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            For DIY landlords · 1–20 units
                        </p>
                        <h1
                            class="text-34 leading-tight font-semibold tracking-tight"
                        >
                            Screen tenants with confidence, not guesswork
                        </h1>
                        <p
                            class="mt-5 max-w-md text-17 leading-relaxed text-muted-foreground"
                        >
                            Dwellow turns every application into a clear,
                            comparable Score — reading documents, checking
                            references, and ranking applicants against your
                            criteria. No bureau accounts, no spreadsheets.
                        </p>
                        <div class="mt-8 flex items-center gap-3">
                            <Link
                                :href="register()"
                                class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-card transition-opacity hover:opacity-90"
                            >
                                Start screening
                                <ArrowRight :size="16" />
                            </Link>
                            <Link
                                :href="login()"
                                class="rounded-md border border-border px-5 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-card"
                            >
                                Log in
                            </Link>
                        </div>
                    </div>

                    <div class="relative">
                        <div
                            class="pointer-events-none absolute -inset-6 -z-10 rounded-full bg-success/10 blur-3xl"
                        />
                        <div
                            class="rounded-xl border border-border bg-gradient-to-b from-card to-card/60 p-6 shadow-card"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium">
                                        Applicant review
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        123 Maple Street, Unit 2
                                    </p>
                                </div>
                                <span
                                    class="rounded-full bg-success px-3 py-1 text-xs font-medium text-success-foreground"
                                >
                                    Recommended
                                </span>
                            </div>

                            <div class="mt-6 space-y-3">
                                <div
                                    v-for="row in [
                                        {
                                            label: 'Income verified',
                                            value: '3.2× rent',
                                        },
                                        {
                                            label: 'Rental history',
                                            value: 'No flags',
                                        },
                                        {
                                            label: 'References',
                                            value: '2 of 2 replied',
                                        },
                                    ]"
                                    :key="row.label"
                                    class="flex items-center justify-between rounded-lg border border-border px-4 py-3"
                                >
                                    <span class="text-13 text-muted-foreground">
                                        {{ row.label }}
                                    </span>
                                    <span class="text-13 font-medium">
                                        {{ row.value }}
                                    </span>
                                </div>
                            </div>

                            <div
                                class="mt-6 rounded-lg bg-background px-4 py-3 text-13 text-muted-foreground"
                            >
                                AI summary: Stable income and clean history. Low
                                risk for a 12-month lease.
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- How it works -->
            <section class="border-t border-border bg-card/40">
                <div class="mx-auto w-full max-w-5xl px-6 py-20">
                    <div class="max-w-xl">
                        <p
                            class="mb-3 text-13 font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            How it works
                        </p>
                        <h2
                            class="text-28 leading-tight font-semibold tracking-tight"
                        >
                            From listing to lease in five steps
                        </h2>
                    </div>

                    <ol class="mt-12 grid gap-px overflow-hidden rounded-xl border border-border bg-border md:grid-cols-2 lg:grid-cols-5">
                        <li
                            v-for="(step, index) in steps"
                            :key="step.title"
                            class="flex flex-col bg-card p-6"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary"
                                >
                                    <component
                                        :is="stepIcons[index]"
                                        :size="18"
                                    />
                                </span>
                                <span
                                    class="font-mono text-13 text-muted-foreground"
                                >
                                    {{
                                        String(index + 1).padStart(2, '0')
                                    }}
                                </span>
                            </div>
                            <h3 class="mt-5 text-sm font-semibold">
                                {{ step.title }}
                            </h3>
                            <p
                                class="mt-2 text-13 leading-relaxed text-muted-foreground"
                            >
                                {{ step.description }}
                            </p>
                        </li>
                    </ol>
                </div>
            </section>

            <!-- Features / benefits -->
            <section class="mx-auto w-full max-w-5xl px-6 py-20">
                <div class="max-w-xl">
                    <p
                        class="mb-3 text-13 font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        Why dwellow
                    </p>
                    <h2
                        class="text-28 leading-tight font-semibold tracking-tight"
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
                            <component
                                :is="featureIcons[index]"
                                :size="20"
                            />
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

            <!-- Roadmap -->
            <section class="border-t border-border bg-card/40">
                <div class="mx-auto w-full max-w-5xl px-6 py-20">
                    <div class="max-w-xl">
                        <p
                            class="mb-3 text-13 font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            Where we're headed
                        </p>
                        <h2
                            class="text-28 leading-tight font-semibold tracking-tight"
                        >
                            Screening today, your whole rental business next
                        </h2>
                        <p
                            class="mt-4 text-sm leading-relaxed text-muted-foreground"
                        >
                            We're making screening best-in-class first, then
                            growing into the full rental lifecycle — lease, rent,
                            maintenance, and accounting.
                        </p>
                    </div>

                    <div class="mt-12 grid gap-6 md:grid-cols-3">
                        <div
                            v-for="phase in roadmap"
                            :key="phase.phase"
                            class="relative rounded-xl border border-border bg-card p-6"
                            :class="
                                phase.current
                                    ? 'ring-1 ring-success/40'
                                    : ''
                            "
                        >
                            <div class="flex items-center gap-2">
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
                            </div>
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

            <!-- Closing CTA -->
            <section class="mx-auto w-full max-w-5xl px-6 py-20">
                <div
                    class="flex flex-col items-center gap-6 rounded-2xl border border-border bg-gradient-to-b from-card to-card/60 px-6 py-14 text-center shadow-card"
                >
                    <h2
                        class="max-w-md text-28 leading-tight font-semibold tracking-tight"
                    >
                        Ready to fill your next vacancy?
                    </h2>
                    <p class="max-w-md text-sm leading-relaxed text-muted-foreground">
                        Create your first property and start screening
                        applicants today.
                    </p>
                    <Link
                        :href="register()"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-card transition-opacity hover:opacity-90"
                    >
                        Get started
                        <ArrowRight :size="16" />
                    </Link>
                </div>
            </section>
        </main>

        <footer class="border-t border-border">
            <div
                class="mx-auto flex w-full max-w-5xl flex-col gap-4 px-6 py-8 sm:flex-row sm:items-center sm:justify-between"
            >
                <AppLogo />
                <div
                    class="flex items-center gap-6 text-13 text-muted-foreground"
                >
                    <a href="#" class="transition-colors hover:text-foreground">
                        Privacy
                    </a>
                    <a href="#" class="transition-colors hover:text-foreground">
                        Terms
                    </a>
                    <span>&copy; {{ new Date().getFullYear() }} Dwellow</span>
                </div>
            </div>
        </footer>
    </div>
</template>
