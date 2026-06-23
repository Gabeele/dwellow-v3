<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import Diamond from '@/components/Diamond.vue';
import { cn } from '@/lib/utils';

/**
 * A single entry in a {@link Timeline}: a coloured dot, a label, and a
 * relative time. The `tone` selects the dot colour.
 */
const props = defineProps<{
    label: string;
    time?: string;
    tone?: 'neutral' | 'info' | 'warning' | 'success';
    last?: boolean;
    class?: HTMLAttributes['class'];
}>();

const TONE_TEXT = {
    neutral: 'text-muted-foreground',
    info: 'text-ai',
    warning: 'text-warning',
    success: 'text-success',
} as const;

const dotColor = computed(() => TONE_TEXT[props.tone ?? 'neutral']);
</script>

<template>
    <li :class="cn('relative flex gap-3 pb-5 last:pb-0', props.class)">
        <div class="flex flex-col items-center">
            <Diamond :class="dotColor" :size="10" />
            <span
                v-if="!last"
                class="mt-1 w-px flex-1 bg-border"
                aria-hidden="true"
            />
        </div>
        <div class="-mt-0.5 min-w-0 pb-1">
            <p class="text-sm text-foreground">{{ label }}</p>
            <p v-if="time" class="text-xs text-muted-foreground">
                {{ time }}
            </p>
        </div>
    </li>
</template>
