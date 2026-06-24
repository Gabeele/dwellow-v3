<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import MarketingFooter from '@/components/marketing/MarketingFooter.vue';
import MarketingNav from '@/components/marketing/MarketingNav.vue';

/**
 * Shared chrome for every public marketing page: ambient brand background,
 * sticky nav and footer. The document <title> and SEO meta are rendered
 * server-side from the page's `seo` prop (see app.blade.php); `title` here
 * keeps the tab label correct during client-side navigation between pages.
 */
const props = withDefaults(
    defineProps<{
        title?: string;
        background?: 'ambient' | 'ambient-cool' | 'ambient-warm';
    }>(),
    { title: '', background: 'ambient' },
);

// Full literal class names so Tailwind's source scanner keeps them.
const backgroundClass = computed(
    () =>
        ({
            ambient: 'bg-ambient',
            'ambient-cool': 'bg-ambient-cool',
            'ambient-warm': 'bg-ambient-warm',
        })[props.background],
);
</script>

<template>
    <div
        class="flex min-h-screen flex-col text-foreground"
        :class="backgroundClass"
    >
        <Head v-if="title" :title="title" />
        <a
            href="#main"
            class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:text-primary-foreground"
        >
            Skip to content
        </a>
        <MarketingNav />
        <main id="main" class="flex-1">
            <slot />
        </main>
        <MarketingFooter />
    </div>
</template>
