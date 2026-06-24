<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Check, Copy, Pause, SlidersHorizontal, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import ApplicationLinkController from '@/actions/App/Http/Controllers/ApplicationLinkController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { index as applicantsIndex } from '@/routes/units/applicants';
import { edit as formEdit } from '@/routes/units/form';
import type { ApplicationLink, Unit } from '@/types/property';

const props = defineProps<{
    unit: Unit;
}>();

/** Every unit has exactly one link; the server provisions it. */
const link = computed<ApplicationLink | null>(
    () => props.unit.application_link ?? null,
);

const copied = ref(false);

function applicantLabel(count: number | undefined): string {
    const n = count ?? 0;

    return `${n} applicant${n === 1 ? '' : 's'}`;
}

/** Flip the link on or off; the public page handles the closed state for us. */
function setAccepting(value: boolean): void {
    if (!link.value) {
        return;
    }

    router.put(
        ApplicationLinkController.update.url(link.value.id),
        { is_accepting: value },
        { preserveScroll: true },
    );
}

async function copyUrl(): Promise<void> {
    if (!link.value) {
        return;
    }

    await navigator.clipboard.writeText(link.value.public_url);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}
</script>

<template>
    <div
        class="flex flex-col gap-4 rounded-lg border border-border bg-muted/30 p-4"
    >
        <!-- Header: title + on/off switch -->
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex flex-col gap-0.5">
                <span class="text-sm font-medium">Application link</span>
                <p class="text-xs text-muted-foreground">
                    Share one link — applicants apply straight from it, no
                    account needed.
                </p>
            </div>
            <label
                v-if="link"
                class="flex shrink-0 items-center gap-2"
                :title="
                    link.is_accepting
                        ? 'Accepting applications'
                        : 'Not accepting applications'
                "
            >
                <span class="text-xs font-medium text-muted-foreground">
                    {{ link.is_accepting ? 'On' : 'Off' }}
                </span>
                <Switch
                    :model-value="link.is_accepting"
                    @update:model-value="(v) => setAccepting(Boolean(v))"
                />
            </label>
        </div>

        <!-- Configure which fields this unit's applicants must fill in -->
        <Link
            :href="formEdit(unit.id)"
            class="flex items-center justify-between gap-3 rounded-md border border-border bg-card px-3 py-2.5 text-sm transition-colors hover:border-foreground/30 hover:bg-muted/50"
        >
            <span class="flex items-center gap-2 font-medium text-foreground">
                <SlidersHorizontal class="size-4 text-muted-foreground" />
                Customize application form
            </span>
            <span class="hidden text-xs text-muted-foreground sm:inline">
                Edit the fields applicants fill in
            </span>
        </Link>

        <template v-if="link">
            <!-- ON: the shareable URL -->
            <div
                v-if="link.is_accepting"
                class="flex flex-col gap-2 sm:flex-row sm:items-center"
            >
                <Input
                    :model-value="link.public_url"
                    readonly
                    class="font-mono text-xs"
                    @focus="
                        (e: FocusEvent) =>
                            (e.target as HTMLInputElement).select()
                    "
                />
                <Button type="button" class="sm:w-auto" @click="copyUrl">
                    <Check v-if="copied" />
                    <Copy v-else />
                    {{ copied ? 'Copied' : 'Copy link' }}
                </Button>
            </div>

            <!-- OFF: paused state, URL hidden -->
            <div
                v-else
                class="flex flex-col items-center gap-1 rounded-md border border-dashed border-border bg-card px-4 py-6 text-center"
            >
                <span
                    class="flex items-center gap-2 text-sm font-medium text-foreground"
                >
                    <Pause class="size-4 text-muted-foreground" />
                    Link paused
                </span>
                <p class="text-xs text-muted-foreground">
                    Anyone who opens it sees a “not accepting applications”
                    message. Turn it on to start sharing again.
                </p>
            </div>

            <!-- Applicant count, always available -->
            <Link
                :href="applicantsIndex(unit.id)"
                class="flex items-center gap-1.5 self-start text-xs text-muted-foreground transition-colors hover:text-foreground"
            >
                <Users class="size-3.5" />
                {{ applicantLabel(unit.applications_count) }}
            </Link>
        </template>

        <!-- Safety net: the link should always exist once provisioned -->
        <p
            v-else
            class="rounded-md border border-dashed border-border bg-card px-4 py-6 text-center text-sm text-muted-foreground"
        >
            Setting up this unit's link… reload the page if it doesn't appear.
        </p>
    </div>
</template>
