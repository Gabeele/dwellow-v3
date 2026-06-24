<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { PaginationLink } from '@/types';

/**
 * Page controls driven by a Laravel paginator's top-level `links` array.
 * The first/last entries are the previous/next controls; the rest are page
 * numbers (and `...` gap separators). Visits preserve scroll so the table
 * stays put, and the paginator URLs already carry any active query string
 * (the controllers call `->withQueryString()`), so filters survive paging.
 */
const props = defineProps<{
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}>();

const previous = computed(() => props.links[0]);
const next = computed(() => props.links[props.links.length - 1]);
const pages = computed(() => props.links.slice(1, -1));

/** Hide entirely when everything fits on a single page. */
const hasPages = computed(() => pages.value.length > 1);
</script>

<template>
    <nav
        v-if="hasPages"
        class="mt-4 flex flex-col items-center justify-between gap-3 sm:flex-row"
        aria-label="Pagination"
    >
        <p class="text-13 text-muted-foreground">
            Showing {{ from ?? 0 }}–{{ to ?? 0 }} of {{ total }}
        </p>

        <div class="flex items-center gap-1">
            <component
                :is="previous.url ? Link : 'span'"
                :href="previous.url ?? undefined"
                :preserve-scroll="previous.url ? true : undefined"
                :preserve-state="previous.url ? true : undefined"
                :aria-disabled="previous.url ? undefined : 'true'"
                :class="
                    cn(
                        'inline-flex size-8 items-center justify-center rounded-md text-sm transition-colors',
                        previous.url
                            ? 'text-foreground hover:bg-accent hover:text-accent-foreground'
                            : 'pointer-events-none text-muted-foreground/40',
                    )
                "
                aria-label="Previous page"
            >
                <ChevronLeft class="size-4" />
            </component>

            <template v-for="(link, index) in pages" :key="index">
                <span
                    v-if="link.url === null"
                    class="inline-flex size-8 items-center justify-center text-sm text-muted-foreground"
                >
                    …
                </span>
                <component
                    :is="link.active ? 'span' : Link"
                    v-else
                    :href="link.active ? undefined : link.url"
                    :preserve-scroll="link.active ? undefined : true"
                    :preserve-state="link.active ? undefined : true"
                    :aria-current="link.active ? 'page' : undefined"
                    :class="
                        cn(
                            'inline-flex size-8 items-center justify-center rounded-md text-sm transition-colors',
                            link.active
                                ? 'bg-primary font-medium text-primary-foreground'
                                : 'text-foreground hover:bg-accent hover:text-accent-foreground',
                        )
                    "
                >
                    {{ link.label }}
                </component>
            </template>

            <component
                :is="next.url ? Link : 'span'"
                :href="next.url ?? undefined"
                :preserve-scroll="next.url ? true : undefined"
                :preserve-state="next.url ? true : undefined"
                :aria-disabled="next.url ? undefined : 'true'"
                :class="
                    cn(
                        'inline-flex size-8 items-center justify-center rounded-md text-sm transition-colors',
                        next.url
                            ? 'text-foreground hover:bg-accent hover:text-accent-foreground'
                            : 'pointer-events-none text-muted-foreground/40',
                    )
                "
                aria-label="Next page"
            >
                <ChevronRight class="size-4" />
            </component>
        </div>
    </nav>
</template>
