<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { cn } from '@/lib/utils';

/**
 * A circular SVG progress gauge displaying a 0-100 score as a large
 * numeral. The arc and numeral colour shift by threshold: green at 70+,
 * amber at 55-69, and red below 55.
 *
 * With `animate`, the gauge sweeps from 0 to the score on mount (after
 * `delay` ms), passing through the threshold colours on the way — the
 * judgment forming in front of you. Respects prefers-reduced-motion.
 */
const props = withDefaults(
    defineProps<{
        score: number;
        animate?: boolean;
        delay?: number;
        class?: HTMLAttributes['class'];
    }>(),
    { animate: false, delay: 0 },
);

const RADIUS = 52;
const STROKE = 8;
const SIZE = (RADIUS + STROKE) * 2;
const CENTER = SIZE / 2;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

const target = computed(() => Math.min(100, Math.max(0, props.score)));

/** The value currently painted; equals `target` unless a sweep is running. */
const displayed = ref(props.animate ? 0 : target.value);

let rafId = 0;
let delayId = 0;

/**
 * Tween `displayed` to `to` with easeOutCubic over ~1.1s: the sweep
 * sprints through the low (red/amber) band and settles into the verdict,
 * ticking whole numbers so the numeral reads as counting.
 */
function runSweep(to: number): void {
    const from = displayed.value;
    const duration = 1100;
    let startedAt: number | null = null;

    const frame = (timestamp: number): void => {
        startedAt ??= timestamp;
        const progress = Math.min(1, (timestamp - startedAt) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);
        displayed.value = Math.round(from + (to - from) * eased);

        if (progress < 1) {
            rafId = window.requestAnimationFrame(frame);
        }
    };

    rafId = window.requestAnimationFrame(frame);
}

onMounted(() => {
    if (!props.animate) {
        return;
    }

    const reducedMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    if (reducedMotion) {
        displayed.value = target.value;

        return;
    }

    delayId = window.setTimeout(() => runSweep(target.value), props.delay);
});

onBeforeUnmount(() => {
    window.clearTimeout(delayId);
    window.cancelAnimationFrame(rafId);
});

/* A score that changes after mount (e.g. a rescore) snaps — the sweep is
   a first-reveal gesture only. */
watch(target, (value) => {
    displayed.value = value;
});

const clamped = computed(() => displayed.value);

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
                :class="[strokeClass, 'transition-colors duration-300']"
                stroke="currentColor"
            />
        </svg>
        <span
            :class="
                cn(
                    'absolute inset-0 flex items-center justify-center text-gauge font-semibold tabular-nums transition-colors duration-300',
                    strokeClass,
                )
            "
        >
            {{ Math.round(clamped) }}
        </span>
    </div>
</template>
