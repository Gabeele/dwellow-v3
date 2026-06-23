<script setup lang="ts">
import Diamond from '@/components/Diamond.vue';
</script>

<template>
    <div
        class="flex aspect-square size-7 items-center justify-center rounded-[7px] bg-primary text-primary-foreground"
    >
        <Diamond :size="10" />
    </div>
    <div class="ml-1 grid flex-1 text-left">
        <span
            class="truncate text-[17px] leading-tight font-semibold tracking-[-0.02em]"
        >
            Dwellow
        </span>
    </div>
</template>
