<script setup lang="ts">
/**
 * A small, friendly robot head used to mark the AI agent throughout the app.
 * Custom geometric SVG (no icon pack): rounded head, antenna, two round eyes
 * and a soft mouth. Outline follows `currentColor`; eyes are filled with it.
 * Size follows any `size-*` utility on the element.
 */
</script>

<template>
    <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="1.5"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <!-- Antenna -->
        <circle cx="12" cy="3" r="1.1" fill="currentColor" stroke="none" />
        <path d="M12 4.1V6" />
        <!-- Head -->
        <rect x="4.5" y="6" width="15" height="13" rx="3.5" />
        <!-- Side panels / ears -->
        <path d="M4.5 11v3M19.5 11v3" />
        <!-- Eyes -->
        <circle cx="9.25" cy="12" r="1.15" fill="currentColor" stroke="none" />
        <circle cx="14.75" cy="12" r="1.15" fill="currentColor" stroke="none" />
        <!-- Mouth -->
        <path d="M9.5 15.5h5" />
    </svg>
</template>
