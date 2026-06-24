<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { register } from '@/routes';

withDefaults(
    defineProps<{
        heading?: string;
        body?: string;
        primaryLabel?: string;
    }>(),
    {
        heading: 'Fill your next vacancy with someone you trust',
        body: 'Add your first property and send an application link in minutes. Free while we are in beta.',
        primaryLabel: 'Start screening',
    },
);
</script>

<template>
    <section class="mx-auto w-full max-w-6xl px-6 py-20">
        <div
            class="bg-gradient-brand flex flex-col items-center gap-6 rounded-2xl border border-border px-6 py-16 text-center shadow-card"
        >
            <h2
                class="max-w-xl text-28 leading-tight font-semibold tracking-tight"
            >
                {{ heading }}
            </h2>
            <p class="max-w-md text-sm leading-relaxed text-muted-foreground">
                {{ body }}
            </p>
            <div class="flex flex-col items-center gap-3 sm:flex-row">
                <Link
                    :href="register()"
                    class="inline-flex items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-card transition-opacity hover:opacity-90"
                >
                    {{ primaryLabel }}
                    <ArrowRight :size="16" />
                </Link>
                <Link
                    href="/docs"
                    class="rounded-md border border-border bg-background/60 px-5 py-2.5 text-sm font-medium text-foreground transition-colors hover:bg-card"
                >
                    Read the docs
                </Link>
            </div>
        </div>
    </section>
</template>
