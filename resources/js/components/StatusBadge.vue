<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import type { BadgeVariants } from '@/components/ui/badge';
import { Badge } from '@/components/ui/badge';
import { occupancyBadge } from '@/lib/occupancy';

/**
 * A status pill built on the {@link Badge} primitive. Pass `status`
 * (an OccupancyStatus value) to derive the tint + label automatically,
 * or pass an explicit `variant` and supply the content via the slot.
 */
const props = defineProps<{
    status?: string;
    variant?: BadgeVariants['variant'];
    class?: HTMLAttributes['class'];
}>();

const resolved = computed(() =>
    props.status !== undefined ? occupancyBadge(props.status) : null,
);

const variant = computed<BadgeVariants['variant']>(
    () => props.variant ?? resolved.value?.variant ?? 'neutral',
);
</script>

<template>
    <Badge :variant="variant" :class="props.class">
        <slot>{{ resolved?.label }}</slot>
    </Badge>
</template>
