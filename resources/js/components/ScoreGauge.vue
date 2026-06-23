<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';

/**
 * A circular SVG progress gauge displaying a 0-100 score as a large
 * numeral. The arc and numeral colour shift by threshold: green at 70+,
 * amber at 55-69, and red below 55.
 */
const props = defineProps<{
    score: number;
    class?: HTMLAttributes['class'];
}>();

const RADIUS = 52;
const STROKE = 8;
const SIZE = (RADIUS + STROKE) * 2;
const CENTER = SIZE / 2;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

const clamped = computed(() => Math.min(100, Math.max(0, props.score)));

const dashOffset = computed(() => CIRCUMFERENCE * (1 - clamped.value / 100));

const tone = computed<'success' | 'warning' | 'destructive'>(() => {
    if (clamped.value >= 70) {
        return 'success';
    }

    if (clamped.value >= 55) {
        return 'warning';
    }

    return 'destructive';
});

const strokeClass = computed(
    () =>
        ({
            success: 'text-success',
            warning: 'text-warning',
            destructive: 'text-destructive',
        })[tone.value],
);
</script>

<template>
    <div
        :class="cn('relative inline-flex shrink-0', props.class)"
        :style="{ width: `${SIZE}px`, height: `${SIZE}px` }"
    >
        <svg
            :width="SIZE"
            :height="SIZE"
            :viewBox="`0 0 ${SIZE} ${SIZE}`"
            class="-rotate-90"
        >
            <circle
                :cx="CENTER"
                :cy="CENTER"
                :r="RADIUS"
                fill="none"
                :stroke-width="STROKE"
                class="text-muted"
                stroke="currentColor"
            />
            <circle
                :cx="CENTER"
                :cy="CENTER"
                :r="RADIUS"
                fill="none"
                :stroke-width="STROKE"
                stroke-linecap="round"
                :stroke-dasharray="CIRCUMFERENCE"
                :stroke-dashoffset="dashOffset"
                :class="strokeClass"
                stroke="currentColor"
            />
        </svg>
        <span
            :class="
                cn(
                    'absolute inset-0 flex items-center justify-center text-gauge font-semibold tabular-nums',
                    strokeClass,
                )
            "
        >
            {{ Math.round(clamped) }}
        </span>
    </div>
</template>
