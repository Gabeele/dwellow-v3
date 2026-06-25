<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { computed } from 'vue';
import Eyebrow from '@/components/Eyebrow.vue';
import AnnotatedShot from '@/components/marketing/AnnotatedShot.vue';
import MarketingFaq from '@/components/marketing/MarketingFaq.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { register } from '@/routes';

interface Marker {
    n: number;
    x: number;
    y: number;
    label: string;
}

interface Guide {
    id: string;
    title: string;
    eyebrow: string;
    intro: string;
    image: string;
    imageAlt: string;
    markers: Marker[];
    steps: string[];
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
    intro: string;
    guides: Guide[];
    faq: FaqItem[];
}>();

const nav = computed(() =>
    props.guides.map((guide) => ({ id: guide.id, title: guide.title })),
);
</script>

<template>
    <PublicLayout :title="props.seo.title">
        <!-- Header -->
        <section class="border-b border-border">
            <div class="mx-auto w-full max-w-6xl px-6 pt-16 pb-12 lg:pt-20">
                <Eyebrow>Documentation</Eyebrow>
                <h1
                    class="mt-4 max-w-2xl text-balance text-34 leading-tight font-semibold tracking-tight"
                >
                    From empty account to scored shortlist
                </h1>
                <p class="mt-5 max-w-xl text-17 leading-relaxed text-muted-foreground">
                    {{ intro }}
                </p>
            </div>
        </section>

        <div class="mx-auto w-full max-w-6xl px-6 py-14">
            <div class="grid gap-12 lg:grid-cols-[200px_1fr] lg:gap-16">
                <!-- Guide nav -->
                <aside class="lg:sticky lg:top-24 lg:self-start">
                    <p
                        class="font-mono text-[10px] font-medium tracking-[0.12em] text-muted-foreground uppercase"
                    >
                        Guides
                    </p>
                    <nav class="mt-4 flex flex-col gap-1" aria-label="Guides">
                        <a
                            v-for="(item, i) in nav"
                            :key="item.id"
                            :href="`#${item.id}`"
                            class="flex items-baseline gap-2.5 rounded-md px-3 py-1.5 text-13 text-muted-foreground transition-colors hover:bg-card hover:text-foreground"
                        >
                            <span class="font-mono text-[11px] text-muted-foreground/70">
                                {{ String(i + 1).padStart(2, '0') }}
                            </span>
                            {{ item.title }}
                        </a>
                    </nav>
                </aside>

                <!-- Guides -->
                <div class="min-w-0">
                    <article
                        v-for="(guide, index) in guides"
                        :id="guide.id"
                        :key="guide.id"
                        class="scroll-mt-24"
                        :class="index > 0 ? 'mt-16 border-t border-border pt-16' : ''"
                    >
                        <Eyebrow>{{ guide.eyebrow }}</Eyebrow>
                        <h2
                            class="mt-3 text-22 leading-snug font-semibold tracking-tight"
                        >
                            {{ guide.title }}
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                            {{ guide.intro }}
                        </p>

                        <div
                            class="mt-7 grid items-start gap-8 lg:grid-cols-[0.9fr_1.1fr]"
                        >
                            <ol class="space-y-4">
                                <li
                                    v-for="(step, s) in guide.steps"
                                    :key="step"
                                    class="flex items-start gap-3 text-sm leading-relaxed text-foreground"
                                >
                                    <span
                                        class="flex size-6 shrink-0 items-center justify-center rounded-md bg-primary/10 font-mono text-[11px] font-medium text-primary"
                                    >
                                        {{ s + 1 }}
                                    </span>
                                    <span class="pt-0.5">{{ step }}</span>
                                </li>
                            </ol>

                            <AnnotatedShot
                                :src="guide.image"
                                :alt="guide.imageAlt"
                                :markers="guide.markers"
                            />
                        </div>
                    </article>

                    <!-- Inline CTA -->
                    <div
                        class="mt-16 flex flex-col items-start gap-4 rounded-xl border border-border bg-card p-6 shadow-card sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm font-semibold">Ready to try it yourself?</p>
                            <p class="mt-1 text-13 text-muted-foreground">
                                Set up your first property in minutes — free
                                while we're in beta.
                            </p>
                        </div>
                        <Link
                            :href="register()"
                            class="inline-flex shrink-0 items-center gap-2 rounded-md bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground shadow-card transition-opacity hover:opacity-90"
                        >
                            Start screening
                            <ArrowRight :size="16" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <MarketingFaq
            :items="faq"
            eyebrow="Common questions"
            heading="Quick answers before you start"
        />
    </PublicLayout>
</template>
