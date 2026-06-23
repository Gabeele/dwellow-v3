<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    CircleCheckBig,
    Mail,
    MapPin,
    ShieldCheck,
} from '@lucide/vue';
import { computed } from 'vue';
import { formatAddressLines } from '@/lib/address';

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

const addressLines = computed<string[]>(() =>
    formatAddressLines(props.unit.address),
);
</script>

<template>
    <div class="space-y-6">
        <Head title="Application submitted" />

        <div class="rounded-lg border border-border bg-card p-8 shadow-card">
            <div class="text-center">
                <div
                    class="mx-auto flex size-12 items-center justify-center rounded-full bg-success/10 text-success"
                >
                    <CircleCheckBig class="size-6" />
                </div>

                <h1 class="mt-4 text-xl font-semibold text-foreground">
                    Your application is in
                </h1>
                <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                    Thanks for applying. The landlord has everything they need
                    to review your application — there's nothing more for you to
                    do right now.
                </p>
            </div>

            <div
                class="mt-6 flex items-start gap-3 rounded-md border border-border bg-muted/40 p-4 text-left"
            >
                <MapPin class="mt-0.5 size-5 shrink-0 text-muted-foreground" />
                <div>
                    <p class="text-sm font-medium text-foreground">
                        {{ unit.label }}
                    </p>
                    <p
                        v-if="addressLines.length"
                        class="mt-0.5 text-13 text-muted-foreground"
                    >
                        {{ addressLines.join(' · ') }}
                    </p>
                </div>
            </div>

            <div
                v-if="reference"
                class="mt-4 flex flex-col gap-1 rounded-md border border-border bg-muted/40 p-4 text-center sm:flex-row sm:items-center sm:justify-between sm:text-left"
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
            <p
                v-if="reference"
                class="mt-2 text-center text-13 text-muted-foreground sm:text-left"
            >
                Keep this in case you need to follow up with the landlord.
            </p>
        </div>

        <div class="rounded-lg border border-border bg-card p-6 shadow-card">
            <h2 class="text-sm font-semibold text-foreground">
                What happens next
            </h2>

            <ol class="mt-4 space-y-4">
                <li class="flex items-start gap-3">
                    <span
                        class="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"
                    >
                        <CircleCheckBig class="size-4" />
                    </span>
                    <div>
                        <p class="text-sm font-medium text-foreground">
                            Application received
                        </p>
                        <p class="mt-0.5 text-13 text-muted-foreground">
                            We've recorded your answers and any documents you
                            attached.
                        </p>
                    </div>
                </li>
                <li class="flex items-start gap-3">
                    <span
                        class="flex size-8 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"
                    >
                        <Mail class="size-4" />
                    </span>
                    <div>
                        <p class="text-sm font-medium text-foreground">
                            The landlord reviews it
                        </p>
                        <p class="mt-0.5 text-13 text-muted-foreground">
                            They'll go through your application and reach out by
                            email if they'd like to move forward. Replies come
                            straight from the landlord — not from dwellow.
                        </p>
                    </div>
                </li>
            </ol>
        </div>

        <div
            class="flex items-start gap-3 rounded-lg border border-border bg-muted/40 p-4"
        >
            <ShieldCheck class="mt-0.5 size-5 shrink-0 text-success" />
            <p class="text-13 text-muted-foreground">
                Your documents were uploaded securely and are shared only with
                this landlord. dwellow never runs a credit or background check —
                the only information shared is what you provided here.
            </p>
        </div>

        <p class="text-center text-13 text-muted-foreground">
            You can safely close this page now.
        </p>
    </div>
</template>
