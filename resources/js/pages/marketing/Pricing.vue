<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Check, Info } from '@lucide/vue';
import Diamond from '@/components/Diamond.vue';
import Eyebrow from '@/components/Eyebrow.vue';
import MarketingFaq from '@/components/marketing/MarketingFaq.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

interface Plan {
    name: string;
    price: string;
    cadence: string;
    tagline: string;
    cta: { label: string; href: string };
    highlighted: boolean;
    badge?: string;
    anchor?: string;
    features: string[];
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
    plans: Plan[];
    faq: FaqItem[];
}>();

function isExternal(href: string): boolean {
    return href.startsWith('mailto:') || href.startsWith('http');
}
</script>

<template>
    <PublicLayout :title="props.seo.title" background="ambient-warm">
        <!-- Header -->
        <section
            class="mx-auto w-full max-w-6xl px-6 pt-16 pb-10 text-center lg:pt-24"
        >
            <div class="mx-auto flex max-w-2xl flex-col items-center">
                <Eyebrow>Pricing</Eyebrow>
                <h1
                    class="mt-4 text-34 leading-tight font-semibold tracking-tight text-balance lg:text-[2.5rem]"
                >
                    Honest pricing for honest screening
                </h1>
                <p
                    class="mt-5 max-w-xl text-17 leading-relaxed text-muted-foreground"
                >
                    No per-applicant fees, no charging renters to apply. You pay
                    on the landlord side, where the value is.
                </p>

                <div
                    class="mt-7 inline-flex items-center gap-2.5 rounded-full border border-success/30 bg-success-tint px-4 py-2 text-13 font-medium text-success-tint-foreground"
                >
                    <Diamond class="text-success" :size="7" />
                    Dwellow is in beta — everything below is free today
                </div>
            </div>
        </section>

        <!-- Plans -->
        <section class="mx-auto w-full max-w-6xl px-6 pb-8">
            <div class="grid gap-6 lg:grid-cols-3">
                <div
                    v-for="plan in plans"
                    :id="plan.anchor"
                    :key="plan.name"
                    class="relative flex flex-col rounded-2xl border bg-card p-7 shadow-card"
                    :class="
                        plan.highlighted
                            ? 'border-success/40 ring-1 ring-success/30 lg:-mt-3 lg:mb-3'
                            : 'border-border'
                    "
                >
                    <div
                        v-if="plan.badge"
                        class="absolute -top-3 left-7 inline-flex items-center gap-1.5 rounded-full bg-success px-3 py-1 text-xs font-medium text-success-foreground shadow-card"
                    >
                        <Check :size="13" />
                        {{ plan.badge }}
                    </div>

                    <h2 class="text-17 font-semibold">{{ plan.name }}</h2>
                    <p
                        class="mt-1 text-13 leading-relaxed text-muted-foreground"
                    >
                        {{ plan.tagline }}
                    </p>

                    <div class="mt-6 flex items-baseline gap-2">
                        <span
                            class="text-34 font-semibold tracking-tight tabular-nums"
                        >
                            {{ plan.price }}
                        </span>
                        <span
                            class="font-mono text-[11px] tracking-wide text-muted-foreground uppercase"
                        >
                            {{ plan.cadence }}
                        </span>
                    </div>

                    <component
                        :is="isExternal(plan.cta.href) ? 'a' : Link"
                        :href="plan.cta.href"
                        class="mt-6 inline-flex items-center justify-center gap-2 rounded-md px-5 py-2.5 text-sm font-medium transition-all"
                        :class="
                            plan.highlighted
                                ? 'bg-primary text-primary-foreground shadow-card hover:opacity-90'
                                : 'border border-border text-foreground hover:bg-background'
                        "
                    >
                        {{ plan.cta.label }}
                        <ArrowRight :size="16" />
                    </component>

                    <ul class="mt-7 space-y-3 border-t border-border pt-6">
                        <li
                            v-for="feature in plan.features"
                            :key="feature"
                            class="flex items-start gap-2.5 text-13 leading-relaxed text-foreground"
                        >
                            <Check
                                :size="16"
                                class="mt-0.5 shrink-0 text-success"
                            />
                            <span>{{ feature }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <p
                class="mx-auto mt-8 flex max-w-2xl items-start justify-center gap-2 text-center text-13 leading-relaxed text-muted-foreground"
            >
                <Info :size="15" class="mt-0.5 shrink-0" />
                <span>
                    Prices shown are what Dwellow will launch with. Beta
                    accounts get plenty of notice before anything changes — and
                    your properties, links and history always come with you.
                </span>
            </p>
        </section>

        <!-- FAQ -->
        <MarketingFaq
            :items="faq"
            eyebrow="Pricing questions"
            heading="What you're actually paying for"
        />
    </PublicLayout>
</template>
