<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
</script>

<template>
    <div class="flex min-h-svh flex-col bg-background">
        <header class="border-b border-border">
            <div
                class="mx-auto flex w-full max-w-2xl items-center px-6 py-4"
            >
                <Link
                    :href="home()"
                    class="flex items-center gap-2 font-semibold text-foreground"
                >
                    <AppLogoIcon
                        class="size-7 fill-current text-foreground dark:text-white"
                    />
                    <span>dwellow</span>
                </Link>
            </div>
        </header>

        <main class="mx-auto w-full max-w-2xl flex-1 px-6 py-8 lg:py-12">
            <slot />
        </main>

        <footer class="border-t border-border">
            <div
                class="mx-auto w-full max-w-2xl px-6 py-6 text-13 text-muted-foreground"
            >
                Powered by dwellow. The details you submit are shared only with
                this landlord.
            </div>
        </footer>
    </div>
</template>
