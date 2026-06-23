<script setup lang="ts">
import type { LinkComponentBaseProps, Method } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';

type Props = {
    href: LinkComponentBaseProps['href'];
    tabindex?: number;
    method?: Method;
    as?: string;
};

defineProps<Props>();
</script>

<template>
    <Link
        :href="href"
        :tabindex="tabindex"
        :method="method"
        :as="as"
        class="font-medium text-foreground underline decoration-border underline-offset-4 transition-colors duration-200 ease-out hover:text-foreground hover:decoration-foreground"
    >
        <slot />
    </Link>
</template>
