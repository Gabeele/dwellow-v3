<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';

const props = defineProps<{
    status: number;
}>();

const supportEmail = 'hello@dwellow.app';

const title = computed(
    () =>
        ({
            503: 'Service unavailable',
            500: 'Something went wrong',
            404: 'Page not found',
            403: 'Access denied',
        })[props.status] ?? 'Something went wrong',
);

const description = computed(
    () =>
        ({
            503: 'Dwellow is down for a little maintenance. Please check back in a few minutes.',
            500: 'An unexpected error occurred on our end. We’ve been notified and are looking into it.',
            404: 'The page you’re looking for doesn’t exist or may have been moved.',
            403: 'You don’t have permission to view this page. If you think this is a mistake, get in touch.',
        })[props.status] ??
        'An unexpected error occurred. Please try again, or get in touch if it keeps happening.',
);
</script>

<template>
    <Head :title="`${status} — ${title}`" />

    <div
        class="bg-ambient flex min-h-dvh flex-col px-6 py-10 text-foreground sm:px-10"
    >
        <Link
            :href="home()"
            class="flex items-center"
            aria-label="Dwellow home"
        >
            <AppLogoIcon class="size-7 shrink-0" />
            <span class="ml-2 text-17 font-semibold tracking-[-0.02em]">
                Dwellow
            </span>
        </Link>

        <div class="flex flex-1 items-center justify-center py-12">
            <div class="flex w-full max-w-md flex-col items-center text-center">
                <span
                    class="text-gauge font-semibold tracking-[-0.04em] text-foreground/15"
                >
                    {{ status }}
                </span>

                <h1
                    class="mt-2 text-28 font-semibold tracking-[-0.02em] text-foreground"
                >
                    {{ title }}
                </h1>
                <p class="mt-3 text-sm text-muted-foreground">
                    {{ description }}
                </p>

                <div class="mt-8 flex flex-col items-center gap-3 sm:flex-row">
                    <Button as-child>
                        <Link :href="home()">Back to home</Link>
                    </Button>
                    <Button as-child variant="outline">
                        <a :href="`mailto:${supportEmail}`">Contact support</a>
                    </Button>
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-muted-foreground">
            &copy; {{ new Date().getFullYear() }} Dwellow
        </p>
    </div>
</template>
