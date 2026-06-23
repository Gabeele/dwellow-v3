<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, Pencil, Plus, Trash2 } from '@lucide/vue';
import PropertyController from '@/actions/App/Http/Controllers/PropertyController';
import UnitController from '@/actions/App/Http/Controllers/UnitController';
import Diamond from '@/components/Diamond.vue';
import Eyebrow from '@/components/Eyebrow.vue';
import { Button } from '@/components/ui/button';
import { edit, index } from '@/routes/properties';
import { create as createUnit } from '@/routes/properties/units';
import { edit as editUnit } from '@/routes/units';
import type { Property, Unit } from '@/types/property';

const props = defineProps<{
    property: Property;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: index() },
            { title: 'Details', href: '#' },
        ],
    },
});

const isMultiUnit = props.property.rental_type === 'multi_unit';

function fullAddress(p: Property): string {
    return [p.address_line1, p.address_line2, p.city, p.region, p.postal_code]
        .filter(Boolean)
        .join(', ');
}

/** Tailwind colour utility for a status indicator dot. */
function statusDot(status: string): string {
    return (
        {
            available: 'bg-success',
            occupied: 'bg-ai',
            unavailable: 'bg-muted-foreground',
        }[status] ?? 'bg-muted-foreground'
    );
}

function money(value: string | null): string {
    return value ? `$${value}` : '—';
}

function destroyProperty(): void {
    if (confirm('Delete this property? This also removes all of its units.')) {
        router.delete(PropertyController.destroy.url(props.property.id));
    }
}

function destroyUnit(unit: Unit): void {
    if (confirm(`Delete unit "${unit.label}"?`)) {
        router.delete(UnitController.destroy.url(unit.id));
    }
}
</script>

<template>
    <Head :title="property.name || property.address_line1" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6 lg:p-10">
        <Button
            as-child
            variant="outline"
            size="sm"
            class="w-fit text-muted-foreground"
        >
            <Link :href="index()"><ChevronLeft />All properties</Link>
        </Button>

        <!-- HERO -->
        <div
            class="overflow-hidden rounded-2xl border border-border bg-[radial-gradient(460px_340px_at_14%_0%,#F5F9FF_0%,var(--card)_56%)]"
        >
            <div
                class="flex flex-col gap-6 p-6 lg:flex-row lg:items-start lg:justify-between lg:p-8"
            >
                <div class="flex flex-col gap-3">
                    <span class="flex items-center gap-2">
                        <Diamond :size="8" class="text-success" />
                        <Eyebrow>{{
                            isMultiUnit ? 'Multi-unit' : 'Whole rental'
                        }}</Eyebrow>
                    </span>
                    <h1
                        class="text-[26px] leading-none font-semibold tracking-[-0.03em]"
                    >
                        {{ property.name || property.address_line1 }}
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ fullAddress(property) }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button as-child variant="outline">
                        <Link :href="edit(property.id)"><Pencil />Edit</Link>
                    </Button>
                    <Button
                        variant="outline"
                        class="text-destructive"
                        @click="destroyProperty"
                    >
                        <Trash2 />Delete
                    </Button>
                </div>
            </div>

            <!-- Whole-rental details: mono micro-labels in the hero footer -->
            <div
                v-if="!isMultiUnit"
                class="grid grid-cols-2 gap-6 border-t border-border/70 px-6 py-5 sm:grid-cols-4 lg:px-8"
            >
                <div class="flex flex-col gap-1">
                    <Eyebrow>Bedrooms</Eyebrow>
                    <span class="text-sm font-medium">{{
                        property.bedrooms ?? '—'
                    }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <Eyebrow>Bathrooms</Eyebrow>
                    <span class="text-sm font-medium">{{
                        property.bathrooms ?? '—'
                    }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <Eyebrow>Monthly rent</Eyebrow>
                    <span class="text-sm font-medium">{{
                        money(property.rent_amount)
                    }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <Eyebrow>Status</Eyebrow>
                    <span
                        class="inline-flex items-center gap-2 text-sm font-medium capitalize"
                    >
                        <span
                            class="size-1.5 rounded-full"
                            :class="statusDot(property.status)"
                        />
                        {{ property.status }}
                    </span>
                </div>
            </div>
        </div>

        <!-- MULTI-UNIT: unit list -->
        <div v-if="isMultiUnit" class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold tracking-[-0.01em]">
                        Units
                    </h2>
                    <span class="font-mono text-xs text-muted-foreground">
                        {{ property.units?.length ?? 0 }}
                    </span>
                </div>
                <Button as-child variant="outline">
                    <Link :href="createUnit(property.id)"
                        ><Plus />Add unit</Link
                    >
                </Button>
            </div>

            <p
                v-if="!property.units?.length"
                class="rounded-2xl border border-dashed border-border bg-card/50 p-10 text-center text-sm text-muted-foreground"
            >
                No units yet. Add the first one.
            </p>

            <div
                v-else
                class="overflow-hidden rounded-2xl border border-border bg-card"
            >
                <div
                    v-for="unit in property.units"
                    :key="unit.id"
                    class="flex items-center gap-4 border-b border-border/70 px-5 py-4 last:border-b-0"
                >
                    <div
                        class="flex size-9 flex-none items-center justify-center rounded-full bg-muted text-xs font-semibold text-muted-foreground"
                    >
                        {{ unit.label.slice(0, 2).toUpperCase() }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium">
                            {{ unit.label }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ unit.bedrooms ?? '—' }} bd ·
                            {{ unit.bathrooms ?? '—' }} ba ·
                            {{
                                unit.rent_amount
                                    ? `${money(unit.rent_amount)}/mo`
                                    : 'no rent set'
                            }}
                        </div>
                    </div>
                    <span
                        class="inline-flex items-center gap-2 rounded-md bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground capitalize"
                    >
                        <span
                            class="size-1.5 rounded-full"
                            :class="statusDot(unit.status)"
                        />
                        {{ unit.status }}
                    </span>
                    <Button
                        as-child
                        size="icon"
                        variant="ghost"
                        class="text-muted-foreground"
                    >
                        <Link :href="editUnit(unit.id)"><Pencil /></Link>
                    </Button>
                    <Button
                        size="icon"
                        variant="ghost"
                        class="text-muted-foreground"
                        @click="destroyUnit(unit)"
                    >
                        <Trash2 />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
