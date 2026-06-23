<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * A vertical timeline wrapper. Compose {@link TimelineItem} children in
 * the default slot; the connecting rail runs down the left gutter.
 */
const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <ol :class="cn('relative flex flex-col', props.class)">
        <slot />
    </ol>
</template>
