<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import Diamond from '@/components/Diamond.vue';
import { cn } from '@/lib/utils';

/**
 * A dashboard summary card: a small uppercase label, a large headline
 * value, and an optional context line. An optional status dot (rendered
 * with {@link Diamond}) can be coloured via `tone` or a custom `dotClass`.
 */
const props = defineProps<{
    label: string;
    value: string | number;
    context?: string;
    tone?: 'success' | 'warning' | 'danger' | 'ai' | 'muted';
    dotClass?: string;
    class?: HTMLAttributes['class'];
}>();

const TONE_TEXT: Record<NonNullable<typeof props.tone>, string> = {
    success: 'text-success',
    warning: 'text-warning',
    danger: 'text-destructive',
    ai: 'text-ai',
    muted: 'text-muted-foreground',
};

const showDot = props.dotClass !== undefined || props.tone !== undefined;
const dotColor = props.dotClass ?? (props.tone ? TONE_TEXT[props.tone] : '');
</script>

<template>
    <div
        :class="
            cn(
                'rounded-lg border border-border bg-card p-5 shadow-card',
                props.class,
            )
        "
    >
        <div class="flex items-center gap-2">
            <Diamond v-if="showDot" :class="dotColor" />
            <span
                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
            >
                {{ label }}
            </span>
        </div>
        <p class="mt-2 text-28 font-semibold text-foreground">
            {{ value }}
        </p>
        <p v-if="context" class="mt-1 text-sm text-muted-foreground">
            {{ context }}
        </p>
    </div>
</template>
