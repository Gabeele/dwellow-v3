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
</script>

<template>
    <tr
        :class="
            cn(
                'border-b border-border text-sm text-foreground last:border-b-0',
                clickable && 'cursor-pointer hover:bg-accent/50',
                props.class,
            )
        "
        @click="clickable && emit('click')"
    >
        <slot />
    </tr>
</template>
