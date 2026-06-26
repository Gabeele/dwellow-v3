<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * A single body row for {@link DataTable}. Cells are supplied via the
 * default slot. Mark a row `clickable` to get hover affordances and a
 * `click` event for navigation (or wrap cells in a Link via the slot).
 */
const props = defineProps<{
    clickable?: boolean;
    class?: HTMLAttributes['class'];
}>();

const emit = defineEmits<{
    click: [];
}>();

/**
 * Activate a clickable row from the keyboard. Enter and Space mirror a
 * mouse click; Space additionally prevents the default page scroll.
 */
function onKeydown(event: KeyboardEvent): void {
    if (!props.clickable) {
        return;
    }

    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        emit('click');
    }
}
</script>

<template>
    <tr
        :class="
            cn(
                'border-b border-border text-sm text-foreground last:border-b-0',
                clickable &&
                    'cursor-pointer hover:bg-accent/50 focus-visible:bg-accent/50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset',
                props.class,
            )
        "
        :role="clickable ? 'button' : undefined"
        :tabindex="clickable ? 0 : undefined"
        @click="clickable && emit('click')"
        @keydown="onKeydown"
    >
        <slot />
    </tr>
</template>
