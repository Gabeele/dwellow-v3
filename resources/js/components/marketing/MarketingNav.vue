<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Menu, X } from '@lucide/vue';
import { ref } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { login, register } from '@/routes';

const links = [
    { label: 'Product', href: '/' },
    { label: 'Pricing', href: '/pricing' },
    { label: 'Docs', href: '/docs' },
    { label: 'Roadmap', href: '/roadmap' },
];

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

function isActive(href: string): boolean {
    return href === '/' ? isCurrentUrl('/') : isCurrentOrParentUrl(href);
}

const mobileOpen = ref(false);
</script>

<template>
    <header
        class="sticky top-0 z-30 border-b border-border bg-background/75 backdrop-blur-md"
    >
        <div
            class="mx-auto flex w-full max-w-6xl items-center justify-between gap-4 px-6 py-3.5"
        >
            <Link href="/" class="flex items-center" aria-label="Dwellow home">
                <AppLogo />
            </Link>

            <nav class="hidden items-center gap-1 md:flex" aria-label="Primary">
                <Link
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    class="rounded-md px-3 py-2 text-sm font-medium transition-colors"
                    :class="
                        isActive(link.href)
                            ? 'text-foreground'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    :aria-current="isActive(link.href) ? 'page' : undefined"
                >
                    {{ link.label }}
                </Link>
            </nav>

            <div class="hidden items-center gap-2 md:flex">
                <Link
                    :href="login()"
                    class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:text-foreground"
                >
                    Log in
                </Link>
                <Link
                    :href="register()"
                    class="inline-flex items-center gap-1.5 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-card transition-opacity hover:opacity-90"
                >
                    Start screening
                    <ArrowRight :size="15" />
                </Link>
            </div>

            <button
                type="button"
                class="inline-flex size-9 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-card hover:text-foreground md:hidden"
                :aria-expanded="mobileOpen"
                aria-label="Toggle menu"
                @click="mobileOpen = !mobileOpen"
            >
                <component :is="mobileOpen ? X : Menu" :size="20" />
            </button>
        </div>

        <div
            v-if="mobileOpen"
            class="border-t border-border bg-background md:hidden"
        >
            <nav
                class="mx-auto flex w-full max-w-6xl flex-col gap-1 px-6 py-4"
                aria-label="Primary mobile"
            >
                <Link
                    v-for="link in links"
                    :key="link.href"
                    :href="link.href"
                    class="rounded-md px-3 py-2.5 text-sm font-medium transition-colors"
                    :class="
                        isActive(link.href)
                            ? 'bg-card text-foreground'
                            : 'text-muted-foreground hover:bg-card hover:text-foreground'
                    "
                    @click="mobileOpen = false"
                >
                    {{ link.label }}
                </Link>
                <div
                    class="mt-3 flex flex-col gap-2 border-t border-border pt-4"
                >
                    <Link
                        :href="login()"
                        class="rounded-md border border-border px-4 py-2.5 text-center text-sm font-medium text-foreground transition-colors hover:bg-card"
                    >
                        Log in
                    </Link>
                    <Link
                        :href="register()"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md bg-primary px-4 py-2.5 text-sm font-medium text-primary-foreground shadow-card transition-opacity hover:opacity-90"
                    >
                        Start screening
                        <ArrowRight :size="15" />
                    </Link>
                </div>
            </nav>
        </div>
    </header>
</template>
