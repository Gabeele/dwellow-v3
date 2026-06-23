<script setup lang="ts">
/**
 * The signature rotated-square "diamond" mark used throughout the UI as the
 * logo glyph, nav bullets and accent dots. Colour follows the current text
 * colour (`bg-current`), so set it with a text utility, e.g. `text-success`.
 */
withDefaults(defineProps<{ size?: number }>(), { size: 8 });
</script>

<template>
    <span
        class="inline-block shrink-0 rotate-45 rounded-[1px] bg-current"
        :style="{ width: `${size}px`, height: `${size}px` }"
    />
</template>
