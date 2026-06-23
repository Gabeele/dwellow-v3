<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

/**
 * A single selectable tab in {@link FilterTabs}.
 */
export interface FilterTab {
    value: string;
    label: string;
    count?: number;
}

/**
 * A purely client-side horizontal tab strip. The active tab is bound via
 * `v-model` and rendered as an accent pill; per-tab counts show as a
 * faint trailing number.
 */
const props = defineProps<{
    tabs: FilterTab[];
    modelValue: string;
    class?: HTMLAttributes['class'];
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

function select(value: string): void {
    if (value !== props.modelValue) {
        emit('update:modelValue', value);
    }
}
</script>

<template>
    <div :class="cn('flex items-center gap-1', props.class)">
        <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            :class="
                cn(
                    'inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm transition-colors',
                    tab.value === modelValue
                        ? 'bg-accent font-semibold text-accent-foreground'
                        : 'text-muted-foreground hover:bg-accent/50 hover:text-foreground',
                )
            "
            @click="select(tab.value)"
        >
            <span>{{ tab.label }}</span>
            <span
                v-if="tab.count !== undefined"
                class="text-xs text-muted-foreground"
            >
                {{ tab.count }}
            </span>
        </button>
    </div>
</template>
