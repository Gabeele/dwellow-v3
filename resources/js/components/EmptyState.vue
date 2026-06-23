<script setup lang="ts">
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        icon?: Component;
        tone?: 'muted' | 'primary';
    }>(),
    { tone: 'muted' },
);
</script>

<template>
    <div
        class="flex flex-1 flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-border bg-card p-16 text-center shadow-card"
    >
        <div
            v-if="icon"
            class="flex size-11 items-center justify-center rounded-xl"
            :class="
                tone === 'primary'
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-muted text-muted-foreground'
            "
        >
            <component :is="icon" class="size-5" />
        </div>
        <p class="text-sm text-muted-foreground">
            <slot />
        </p>
        <slot name="action" />
    </div>
</template>
