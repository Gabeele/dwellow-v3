<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Plus } from '@lucide/vue';
import Diamond from '@/components/Diamond.vue';
import Eyebrow from '@/components/Eyebrow.vue';
import { Button } from '@/components/ui/button';
import { create, index, show } from '@/routes/properties';
import type { Property } from '@/types/property';

defineProps<{
    properties: Property[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Properties', href: index() }],
    },
});

function cityLine(property: Property): string {
    return [property.city, property.region].filter(Boolean).join(', ');
}
</script>

<template>
    <Head title="Properties" />

    <div class="flex h-full flex-1 flex-col gap-7 p-6 lg:p-10">
        <div class="flex items-end justify-between gap-6">
            <div class="flex flex-col gap-2">
                <Eyebrow>Portfolio</Eyebrow>
                <h1
                    class="text-[28px] leading-none font-semibold tracking-[-0.03em]"
                >
                    Properties
                </h1>
                <p class="text-sm text-muted-foreground">
                    Everything you rent out, across whole-home and multi-unit
                    listings.
                </p>
            </div>
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    New property
                </Link>
            </Button>
        </div>

        <div
            v-if="properties.length === 0"
            class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-border bg-card/50 p-16 text-center"
        >
            <div
                class="flex size-11 items-center justify-center rounded-xl bg-primary text-primary-foreground"
            >
                <Building2 class="size-5" />
            </div>
            <p class="text-sm text-muted-foreground">
                You haven't added any properties yet.
            </p>
            <Button as-child variant="outline">
                <Link :href="create()">Add your first property</Link>
            </Button>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="property in properties"
                :key="property.id"
                :href="show(property.id)"
                class="group flex flex-col gap-5 rounded-2xl border border-border bg-card p-5 transition-colors hover:border-foreground/25"
            >
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-2 text-muted-foreground">
                        <Diamond :size="7" class="text-success" />
                        <span
                            class="font-mono text-[11px] tracking-[0.08em] uppercase"
                        >
                            {{ property.type }}
                        </span>
                    </span>
                    <span class="font-mono text-[11px] text-muted-foreground">
                        {{
                            property.rental_type === 'multi_unit'
                                ? `${property.units_count ?? 0} ${(property.units_count ?? 0) === 1 ? 'unit' : 'units'}`
                                : 'Whole'
                        }}
                    </span>
                </div>

                <div class="flex flex-col gap-1">
                    <span class="text-base font-semibold tracking-[-0.01em]">
                        {{ property.name || property.address_line1 }}
                    </span>
                    <span class="text-sm text-muted-foreground">{{
                        cityLine(property)
                    }}</span>
                </div>

                <div class="mt-auto border-t border-border pt-4">
                    <span
                        class="inline-flex items-center gap-2 rounded-md bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground"
                    >
                        <span
                            class="size-1.5 rounded-full"
                            :class="
                                property.rental_type === 'multi_unit'
                                    ? 'bg-foreground/40'
                                    : 'bg-success'
                            "
                        />
                        {{
                            property.rental_type === 'multi_unit'
                                ? 'Split into units'
                                : 'Rented as a whole'
                        }}
                    </span>
                </div>
            </Link>
        </div>
    </div>
</template>
