<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CircleCheckBig, Sparkles, Users } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import { login, register } from '@/routes';

const features = [
    {
        icon: Sparkles,
        title: 'AI screening',
        description:
            'Surface risk and fit from every application with AI that reads the details so you don’t have to.',
    },
    {
        icon: Users,
        title: 'Applicant pipeline',
        description:
            'Keep every prospective tenant in one organized pipeline, from first inquiry to signed lease.',
    },
    {
        icon: CircleCheckBig,
        title: 'Fast decisions',
        description:
            'Get a clear, consistent recommendation in minutes and fill vacancies with confidence.',
    },
];
</script>

<template>
    <div class="bg-ambient flex min-h-screen flex-col text-foreground">
        <Head title="AI tenant screening for small landlords" />

        <header class="border-b border-border">
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
            <section class="mx-auto w-full max-w-5xl px-6 py-20 lg:py-28">
                <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                    <div>
                        <p
                            class="mb-4 text-13 font-medium tracking-wide text-muted-foreground uppercase"
                        >
                            For small landlords
                        </p>
                        <h1
                            class="text-34 leading-tight font-semibold tracking-tight"
                        >
                            AI tenant screening that pays for itself
                        </h1>
                        <p
                            class="mt-5 max-w-md text-17 leading-relaxed text-muted-foreground"
                        >
                            Dwellow reviews every applicant, flags what matters,
                            and gives you a clear recommendation — so you can
                            choose great tenants without the guesswork.
                        </p>
                        <div class="mt-8 flex items-center gap-3">
                            <Link
                                :href="register()"
                                class="rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-card transition-opacity hover:opacity-90"
                            >
                                Get started
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
                                    class="rounded-full bg-success px-3 py-1 text-xs font-medium text-primary-foreground"
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
                                            label: 'Credit signal',
                                            value: 'Strong',
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

            <section class="border-t border-border bg-card/40">
                <div class="mx-auto w-full max-w-5xl px-6 py-16">
                    <div class="grid gap-6 md:grid-cols-3">
                        <div
                            v-for="feature in features"
                            :key="feature.title"
                            class="rounded-xl border border-border bg-card p-6 shadow-card"
                        >
                            <div
                                class="flex size-10 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                            >
                                <component :is="feature.icon" :size="20" />
                            </div>
                            <h2 class="mt-4 text-17 font-semibold">
                                {{ feature.title }}
                            </h2>
                            <p
                                class="mt-2 text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ feature.description }}
                            </p>
                        </div>
                    </div>
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
