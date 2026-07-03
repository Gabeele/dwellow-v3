<script setup lang="ts">
import { Check, Minus, TrendingDown, TrendingUp } from '@lucide/vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { CriterionAssessment, RubricRow } from '@/types/property';

/**
 * Wraps a trigger (the default slot) with a hover card detailing the Score's
 * rubric — the fixed framework criteria, each verdict, and its note. Used on the
 * applicant detail gauge and the applications table so the breakdown reads the
 * same everywhere. The trigger decides what is hovered (a gauge, a badge, …).
 */
withDefaults(
    defineProps<{ rubric: RubricRow[]; rationale?: string | null }>(),
    {
        rationale: null,
    },
);

/** Icon + tooltip tone + label per verdict (tones read on the dark tooltip). */
const assessmentMeta: Record<
    CriterionAssessment,
    { icon: typeof TrendingUp; tipTone: string; label: string }
> = {
    strong: { icon: TrendingUp, tipTone: 'text-success', label: 'Strong' },
    adequate: { icon: Check, tipTone: 'text-background', label: 'Adequate' },
    weak: { icon: TrendingDown, tipTone: 'text-warning', label: 'Weak' },
    unverified: {
        icon: Minus,
        tipTone: 'text-background/70',
        label: 'Unverified',
    },
};

/** Display labels for the fixed framework criteria. */
const criterionLabels: Record<string, string> = {
    affordability: 'Affordability',
    employment: 'Employment',
    credit: 'Credit',
    references: 'References',
    rental_history: 'Rental history',
    occupancy: 'Occupancy',
    identity: 'Identity',
    disclosures: 'Disclosures',
};

const criterionLabel = (key: string): string => criterionLabels[key] ?? key;
</script>

<template>
    <TooltipProvider :delay-duration="150">
        <Tooltip>
            <TooltipTrigger as-child>
                <slot />
            </TooltipTrigger>
            <TooltipContent class="max-w-80 space-y-2.5 px-3.5 py-3 text-left">
                <p
                    v-if="rationale"
                    class="border-b border-background/20 pb-2 text-xs font-semibold"
                >
                    {{ rationale }}
                </p>
                <ul v-if="rubric.length" class="space-y-2">
                    <li
                        v-for="row in rubric"
                        :key="row.criterion"
                        class="space-y-0.5"
                    >
                        <div class="flex items-center gap-2 text-xs">
                            <component
                                :is="assessmentMeta[row.assessment].icon"
                                :class="[
                                    'size-3.5 shrink-0',
                                    assessmentMeta[row.assessment].tipTone,
                                ]"
                            />
                            <span class="flex-1 font-medium">{{
                                criterionLabel(row.criterion)
                            }}</span>
                            <span
                                :class="[
                                    'font-semibold',
                                    assessmentMeta[row.assessment].tipTone,
                                ]"
                                >{{
                                    assessmentMeta[row.assessment].label
                                }}</span
                            >
                        </div>
                        <p
                            v-if="row.note"
                            class="pl-[1.375rem] text-[11px] leading-snug opacity-75"
                        >
                            {{ row.note }}
                        </p>
                    </li>
                </ul>
                <p v-else class="text-xs opacity-80">No breakdown available.</p>
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
