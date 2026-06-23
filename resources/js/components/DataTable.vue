<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * A dense, slot-driven table shell. Supply column headers via the
 * `head` slot and body content (typically {@link TableRow} elements)
 * via the default slot so Phase 3 pages can compose their own columns.
 */
const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <div
        :class="
            cn(
                'overflow-hidden rounded-lg border border-border bg-card shadow-card',
                props.class,
            )
        "
    >
        <table class="w-full border-collapse text-left">
            <thead>
                <tr
                    class="border-b border-border text-xs font-medium tracking-wide text-muted-foreground uppercase"
                >
                    <slot name="head" />
                </tr>
            </thead>
            <tbody>
                <slot />
            </tbody>
        </table>
    </div>
</template>
