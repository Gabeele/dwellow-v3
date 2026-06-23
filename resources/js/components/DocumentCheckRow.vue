<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import type { BadgeVariants } from '@/components/ui/badge';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

/**
 * The verification state of a document check.
 */
export type DocumentCheckState = 'verified' | 'partial' | 'missing';

/**
 * A single document-check row: the document name, supporting metadata,
 * a neutral type tag, and a verification {@link StatusBadge}.
 */
const props = defineProps<{
    name: string;
    meta?: string;
    type?: string;
    state: DocumentCheckState;
    class?: HTMLAttributes['class'];
}>();

const STATE_BADGE: Record<
    DocumentCheckState,
    { variant: BadgeVariants['variant']; label: string }
> = {
    verified: { variant: 'success', label: 'Verified' },
    partial: { variant: 'warning', label: 'Partial' },
    missing: { variant: 'danger', label: 'Missing' },
};

const state = computed(() => STATE_BADGE[props.state]);
</script>

<template>
    <div
        :class="
            cn(
                'flex items-center justify-between gap-3 border-b border-border py-3 last:border-b-0',
                props.class,
            )
        "
    >
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-foreground">
                {{ name }}
            </p>
            <p v-if="meta" class="truncate text-xs text-muted-foreground">
                {{ meta }}
            </p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <Badge v-if="type" variant="neutral">{{ type }}</Badge>
            <StatusBadge :variant="state.variant">
                {{ state.label }}
            </StatusBadge>
        </div>
    </div>
</template>
