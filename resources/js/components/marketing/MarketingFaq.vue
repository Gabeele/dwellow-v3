<script setup lang="ts">
import { Plus } from '@lucide/vue';
import Eyebrow from '@/components/Eyebrow.vue';

interface FaqItem {
    question: string;
    answer: string;
}

withDefaults(
    defineProps<{
        items: FaqItem[];
        eyebrow?: string;
        heading?: string;
    }>(),
    {
        eyebrow: 'Questions',
        heading: 'The things landlords ask first',
    },
);
</script>

<template>
    <section class="border-t border-border">
        <div class="mx-auto w-full max-w-6xl px-6 py-20">
            <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:gap-16">
                <div>
                    <Eyebrow>{{ eyebrow }}</Eyebrow>
                    <h2
                        class="mt-3 text-28 leading-tight font-semibold tracking-tight"
                    >
                        {{ heading }}
                    </h2>
                    <p
                        class="mt-4 text-sm leading-relaxed text-muted-foreground"
                    >
                        Still unsure?
                        <a
                            href="mailto:hello@dwellow.app"
                            class="font-medium text-foreground underline-offset-4 hover:underline"
                        >
                            Email us
                        </a>
                        — a real person answers.
                    </p>
                </div>

                <dl class="divide-y divide-border border-y border-border">
                    <details
                        v-for="item in items"
                        :key="item.question"
                        class="group"
                    >
                        <summary
                            class="flex cursor-pointer list-none items-center justify-between gap-4 py-5 text-left"
                        >
                            <dt class="text-sm font-medium text-foreground">
                                {{ item.question }}
                            </dt>
                            <Plus
                                :size="18"
                                class="shrink-0 text-muted-foreground transition-transform duration-200 group-open:rotate-45"
                            />
                        </summary>
                        <dd
                            class="-mt-1 pr-8 pb-5 text-sm leading-relaxed text-muted-foreground"
                        >
                            {{ item.answer }}
                        </dd>
                    </details>
                </dl>
            </div>
        </div>
    </section>
</template>
