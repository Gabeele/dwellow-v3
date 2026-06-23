<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header :class="variant === 'small' ? 'space-y-1' : 'mb-8 space-y-1'">
        <h2
            :class="
                variant === 'small'
                    ? 'text-17 font-semibold tracking-tight text-foreground'
                    : 'text-22 font-semibold tracking-tight text-foreground'
            "
        >
            {{ title }}
        </h2>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </header>
</template>
