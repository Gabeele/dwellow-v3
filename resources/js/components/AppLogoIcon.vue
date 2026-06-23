<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();

// Dwellow six-petal mark. Ink petals/center use currentColor so the mark stays
// legible in both light and dark themes; the brand-green petals are fixed.
const petalPath = 'M32 9 C25.5 16 25.5 25 32 29 C38.5 25 38.5 16 32 9 Z';
const inkPetals = [0, 120, 240];
const greenPetals = [60, 180, 300];
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 64 64"
        fill="none"
        :class="className"
        v-bind="$attrs"
    >
        <path
            v-for="angle in inkPetals"
            :key="`ink-${angle}`"
            :transform="`rotate(${angle} 32 32)`"
            :d="petalPath"
            fill="currentColor"
        />
        <path
            v-for="angle in greenPetals"
            :key="`green-${angle}`"
            :transform="`rotate(${angle} 32 32)`"
            :d="petalPath"
            fill="#16A34A"
        />
        <circle cx="32" cy="32" r="4" fill="currentColor" />
    </svg>
</template>
