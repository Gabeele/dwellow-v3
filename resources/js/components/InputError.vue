<script setup lang="ts">
defineProps<{
    message?: string;
    id?: string;
}>();
</script>

<template>
    <div v-show="message">
        <p :id="id" class="text-13 text-destructive">
            {{ message }}
        </p>
    </div>
</template>
