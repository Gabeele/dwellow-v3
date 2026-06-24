<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { login, register } from '@/routes';

const year = new Date().getFullYear();

const columns = [
    {
        heading: 'Product',
        links: [
            { label: 'Overview', href: '/' },
            { label: 'Pricing', href: '/pricing' },
            { label: 'Roadmap', href: '/roadmap' },
            { label: 'Docs', href: '/docs' },
        ],
    },
    {
        heading: 'Get started',
        links: [
            { label: 'Create an account', href: register().url },
            { label: 'Log in', href: login().url },
            { label: 'How scoring works', href: '/docs#scoring' },
            { label: 'For property managers', href: '/pricing#enterprise' },
        ],
    },
    {
        heading: 'Company',
        links: [
            { label: 'Privacy', href: '/docs#privacy' },
            { label: 'Terms', href: '/docs#terms' },
            { label: 'Contact', href: 'mailto:hello@dwellow.app' },
            { label: 'Status', href: '/roadmap#shipped' },
        ],
    },
];
</script>

<template>
    <footer class="border-t border-border">
        <div class="mx-auto w-full max-w-6xl px-6 py-14">
            <div class="grid gap-10 md:grid-cols-[1.4fr_1fr_1fr_1fr]">
                <div class="max-w-xs">
                    <Link
                        href="/"
                        class="flex items-center"
                        aria-label="Dwellow home"
                    >
                        <AppLogo />
                    </Link>
                    <p
                        class="mt-4 text-13 leading-relaxed text-muted-foreground"
                    >
                        Evidence-based tenant screening. Every application
                        becomes one comparable Score — so you decide on facts,
                        not gut feel.
                    </p>
                </div>

                <div v-for="column in columns" :key="column.heading">
                    <p
                        class="font-mono text-[10px] font-medium tracking-[0.12em] text-muted-foreground uppercase"
                    >
                        {{ column.heading }}
                    </p>
                    <ul class="mt-4 space-y-2.5">
                        <li v-for="item in column.links" :key="item.label">
                            <a
                                v-if="
                                    item.href.startsWith('mailto:') ||
                                    item.href.includes('#')
                                "
                                :href="item.href"
                                class="text-13 text-muted-foreground transition-colors hover:text-foreground"
                            >
                                {{ item.label }}
                            </a>
                            <Link
                                v-else
                                :href="item.href"
                                class="text-13 text-muted-foreground transition-colors hover:text-foreground"
                            >
                                {{ item.label }}
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <div
                class="mt-12 flex flex-col gap-3 border-t border-border pt-6 text-13 text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
            >
                <span
                    >&copy; {{ year }} Dwellow. Built for landlords who'd rather
                    know.</span
                >
                <span class="font-mono text-[11px] tracking-wide">
                    Made for the rental world — screening today, the full
                    lifecycle next.
                </span>
            </div>
        </div>
    </footer>
</template>
