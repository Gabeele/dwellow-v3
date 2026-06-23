<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <div class="bg-ambient min-h-screen">
        <AppSidebar />
        <div class="flex min-h-screen flex-col pl-64">
            <main class="flex-1 px-8 py-8">
                <slot />
            </main>
        </div>
        <Toaster />
    </div>
</template>
