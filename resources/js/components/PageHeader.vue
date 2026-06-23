<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft } from '@lucide/vue';
import { useSlots } from 'vue';
import Eyebrow from '@/components/Eyebrow.vue';
import type { NavItem } from '@/types';

type BackLink = {
    label: string;
    href: NavItem['href'];
};

type Props = {
    title: string;
    eyebrow?: string;
    back?: BackLink;
};

defineProps<Props>();

const slots = useSlots();
</script>

<template>
    <div class="flex flex-col gap-3 pb-6">
        <div v-if="back || slots.back">
            <slot name="back">
                <Link
                    v-if="back"
                    :href="back.href"
                    class="inline-flex items-center gap-1 text-13 text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ChevronLeft class="size-4" />
                    {{ back.label }}
                </Link>
            </slot>
        </div>

        <div class="flex items-start justify-between gap-4">
            <div class="flex flex-col gap-1">
                <Eyebrow v-if="eyebrow">{{ eyebrow }}</Eyebrow>
                <h1
                    class="text-28 font-semibold tracking-tight text-foreground"
                >
                    {{ title }}
                </h1>
            </div>
            <div v-if="slots.actions" class="flex shrink-0 items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>
