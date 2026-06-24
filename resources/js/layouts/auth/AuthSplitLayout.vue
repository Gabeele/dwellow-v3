<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="grid min-h-dvh lg:grid-cols-2">
        <div class="bg-ambient flex flex-col px-6 py-10 sm:px-10">
<Link
                :href="home()"
                class="flex items-center lg:hidden"
                aria-label="Dwellow home"
            >
                <AppLogo />
            </Link>

            <div class="flex flex-1 items-center justify-center py-12">
                <div class="w-full max-w-sm">
                    <div class="flex flex-col gap-2">
                        <h1
                            v-if="title"
                            class="text-28 font-semibold tracking-[-0.02em] text-foreground"
                        >
                            {{ title }}
                        </h1>
                        <p
                            v-if="description"
                            class="text-sm text-muted-foreground"
                        >
                            {{ description }}
                        </p>
                    </div>

                    <div class="mt-8">
                        <slot />
                    </div>
                </div>
            </div>
        </div>

        <div
            class="relative hidden flex-col justify-between overflow-hidden bg-primary p-12 text-primary-foreground lg:flex"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/8 via-transparent to-success/15"
            />
            <div
                class="pointer-events-none absolute -top-24 -right-24 h-80 w-80 rounded-full bg-success/20 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -bottom-32 -left-20 h-80 w-80 rounded-full bg-ai/15 blur-3xl"
            />

<Link
                :href="home()"
                class="relative flex items-center gap-2"
                aria-label="Dwellow home"
            >
                <AppLogoIcon class="size-7 shrink-0 text-primary-foreground" />
                <span
                    class="text-17 font-semibold tracking-[-0.02em] text-primary-foreground"
                >
                    Dwellow
                </span>
            </Link>

            <div class="relative flex flex-col gap-4">
                <h2
                    class="text-34 font-semibold tracking-[-0.02em] text-primary-foreground"
                >
                    AI tenant screening for small landlords.
                </h2>
                <p class="text-base text-primary-foreground/70">
                    Vet applicants, verify income, and choose your next tenant
                    with confidence — without the enterprise overhead.
                </p>
            </div>

            <div
                class="relative text-sm text-primary-foreground/60"
                aria-hidden="true"
            >
                &copy; {{ new Date().getFullYear() }} Dwellow
            </div>
        </div>
    </div>
</template>
