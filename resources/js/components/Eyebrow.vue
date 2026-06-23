<script setup lang="ts">
/**
 * Small uppercase monospace label (Geist Mono) used as a section "eyebrow"
 * above headings and inside cards, per the design scheme.
 */
</script>

<template>
    <span
        class="font-mono text-[10px] font-medium tracking-[0.12em] text-muted-foreground uppercase"
    >
        <slot />
    </span>
</template>
