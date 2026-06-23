<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

interface UnitAddress {
    line1: string;
    line2: string | null;
    city: string;
    region: string;
    postal_code: string;
    country: string;
}

const props = defineProps<{
    unit: {
        label: string;
        address: UnitAddress;
    };
    reference: string | null;
}>();

const addressLines = computed<string[]>(() => {
    const { line1, line2, city, region, postal_code } = props.unit.address;
    const cityLine = [city, region, postal_code].filter(Boolean).join(', ');

    return [line1, line2, cityLine].filter((line): line is string => !!line);
});
</script>

<template>
    <div>
        <Head title="Application submitted" />

        <div
            class="rounded-lg border border-border bg-card p-8 text-center shadow-card"
        >
            <div
                class="mx-auto flex size-12 items-center justify-center rounded-full bg-success/10 text-success"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="size-6"
                >
                    <path d="M20 6 9 17l-5-5" />
                </svg>
            </div>

            <h1 class="mt-4 text-xl font-semibold text-foreground">
                Application submitted
            </h1>
            <p class="mt-2 text-sm text-muted-foreground">
                Thanks for applying for
                <span class="font-medium text-foreground">{{ unit.label }}</span
                ><span v-if="addressLines.length">
                    ({{ addressLines.join(' · ') }})</span
                >. The landlord has received your application and will be in
                touch.
            </p>

            <div
                v-if="reference"
                class="mx-auto mt-5 inline-flex flex-col items-center gap-1 rounded-md border border-border bg-muted/40 px-4 py-3"
            >
                <span
                    class="text-13 font-medium uppercase tracking-wide text-muted-foreground"
                >
                    Your reference
                </span>
                <span class="font-mono text-sm font-semibold text-foreground">
                    {{ reference }}
                </span>
            </div>

            <p class="mt-5 text-13 text-muted-foreground">
                You can close this page now.
            </p>
        </div>
    </div>
</template>
