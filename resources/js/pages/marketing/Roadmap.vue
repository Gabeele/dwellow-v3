<script setup lang="ts">
import { Check } from '@lucide/vue';
import Eyebrow from '@/components/Eyebrow.vue';
import MarketingCta from '@/components/marketing/MarketingCta.vue';
import MarketingFaq from '@/components/marketing/MarketingFaq.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';

type Status = 'shipped' | 'now' | 'next' | 'later';

interface Item {
    title: string;
    description: string;
}

interface Group {
    label: string;
    caption: string;
    status: Status;
    items: Item[];
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
    groups: Group[];
    faq: FaqItem[];
}>();

// Tone classes per status, kept as full literal strings for Tailwind's scanner.
const dotClass: Record<Status, string> = {
    shipped: 'bg-success text-success-foreground border-success',
    now: 'bg-success text-success-foreground border-success',
    next: 'bg-background text-ai border-ai',
    later: 'bg-background text-muted-foreground border-border',
};

const chipClass: Record<Status, string> = {
    shipped: 'bg-success-tint text-success-tint-foreground',
    now: 'bg-success/15 text-success',
    next: 'bg-ai-tint text-ai-tint-foreground',
    later: 'bg-muted text-muted-foreground',
};

const chipLabel: Record<Status, string> = {
    shipped: 'Shipped',
    now: 'In progress',
    next: 'Planned',
    later: 'Exploring',
};
</script>

<template>
    <PublicLayout :title="props.seo.title" background="ambient-cool">
        <!-- Header -->
        <section class="mx-auto w-full max-w-3xl px-6 pt-16 pb-10 lg:pt-24">
            <Eyebrow>Roadmap</Eyebrow>
            <h1
                class="mt-4 text-balance text-34 leading-tight font-semibold tracking-tight lg:text-[2.5rem]"
            >
                What's live, and what's coming next
            </h1>
            <p class="mt-5 text-17 leading-relaxed text-muted-foreground">
                We'd rather make screening exceptional than do ten things
                adequately. Here's the order we're building in — top is shipped,
                further down is where we're headed.
            </p>
        </section>

        <!-- Timeline -->
        <section class="mx-auto w-full max-w-3xl px-6 pb-8">
            <ol class="relative ml-2 border-l border-border sm:ml-3">
                <template v-for="group in groups" :key="group.label">
                    <!-- Group marker -->
                    <li class="relative pb-6 pl-8">
                        <span
                            class="absolute top-0.5 left-0 flex size-3 -translate-x-1/2 items-center justify-center rounded-full border-2 border-foreground bg-background"
                            aria-hidden="true"
                        />
                        <h2
                            class="text-13 font-semibold tracking-wide text-foreground uppercase"
                        >
                            {{ group.label }}
                        </h2>
                        <p class="mt-1 text-13 leading-relaxed text-muted-foreground">
                            {{ group.caption }}
                        </p>
                    </li>

                    <!-- Items -->
                    <li
                        v-for="item in group.items"
                        :key="item.title"
                        class="relative pb-7 pl-8 last:pb-2"
                    >
                        <span
                            class="absolute top-1 left-0 flex size-[18px] -translate-x-1/2 items-center justify-center rounded-full border-2 shadow-card"
                            :class="dotClass[group.status]"
                            aria-hidden="true"
                        >
                            <Check
                                v-if="group.status === 'shipped'"
                                :size="11"
                                stroke-width="3"
                            />
                            <span
                                v-else-if="group.status === 'now'"
                                class="relative flex size-1.5"
                            >
                                <span
                                    class="absolute inline-flex size-full animate-ping rounded-full bg-success-foreground opacity-70"
                                />
                                <span
                                    class="relative inline-flex size-1.5 rounded-full bg-success-foreground"
                                />
                            </span>
                            <span
                                v-else
                                class="size-1.5 rounded-full bg-current"
                            />
                        </span>

                        <div
                            class="flex flex-wrap items-center gap-x-3 gap-y-1"
                        >
                            <h3 class="text-sm font-semibold text-foreground">
                                {{ item.title }}
                            </h3>
                            <span
                                class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                                :class="chipClass[group.status]"
                            >
                                {{ chipLabel[group.status] }}
                            </span>
                        </div>
                        <p class="mt-1 text-13 leading-relaxed text-muted-foreground">
                            {{ item.description }}
                        </p>
                    </li>
                </template>
            </ol>

            <p
                class="mt-6 ml-2 text-13 text-muted-foreground sm:ml-3"
            >
                Want something that isn't here?
                <a
                    href="mailto:hello@dwellow.app"
                    class="font-medium text-foreground underline-offset-4 hover:underline"
                >
                    Tell us
                </a>
                — landlord feedback is the biggest input into this list.
            </p>
        </section>

        <!-- FAQ -->
        <MarketingFaq
            :items="faq"
            eyebrow="About the roadmap"
            heading="How we build, in the open"
        />

        <!-- CTA -->
        <MarketingCta
            heading="Get in early — and help shape what's next"
            body="The screening workflow is live and free during beta. Your feedback is the biggest input into everything above."
        />
    </PublicLayout>
</template>
