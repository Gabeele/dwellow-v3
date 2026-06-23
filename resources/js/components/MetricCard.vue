<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * A denser detail-page metric tile: an uppercase label with an optional
 * trailing `tag` (e.g. a {@link StatusBadge}), a large value, and an
 * optional fine-print `detail` line.
 */
const props = defineProps<{
    label: string;
    value: string | number;
    detail?: string;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div
        :class="
            cn(
                'rounded-lg border border-border bg-card p-4 shadow-card',
                props.class,
            )
        "
    >
        <div class="flex items-center justify-between gap-2">
            <span
                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
            >
                {{ label }}
            </span>
            <slot name="tag" />
        </div>
        <p class="mt-1 text-28 font-semibold text-foreground">
            {{ value }}
        </p>
        <p v-if="detail" class="mt-1 text-xs text-muted-foreground">
            {{ detail }}
        </p>
    </div>
</template>
