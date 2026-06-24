<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    Ban,
    Check,
    Copy,
    Pause,
    Play,
    Plus,
    SlidersHorizontal,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ApplicationLinkController from '@/actions/App/Http/Controllers/ApplicationLinkController';
import StatusBadge from '@/components/StatusBadge.vue';
import type { BadgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index as applicantsIndex } from '@/routes/units/applicants';
import { edit as formEdit } from '@/routes/units/form';
import type { ApplicationLink, Unit } from '@/types/property';

const props = defineProps<{
    unit: Unit;
}>();

const links = computed<ApplicationLink[]>(
    () => props.unit.application_links ?? [],
);

/**
 * The link a landlord should share by default: the newest open link, falling
 * back to the newest link of any state so there is always something to lead
 * with. Links arrive newest-first from the controller.
 */
const primaryLink = computed<ApplicationLink | undefined>(
    () =>
        links.value.find((link) => !isLocked(link) && link.is_accepting) ??
        links.value[0],
);

/** Every link that isn't the primary one, listed compactly below. */
const otherLinks = computed<ApplicationLink[]>(() =>
    links.value.filter((link) => link.id !== primaryLink.value?.id),
);

const newLabel = ref('');
const copiedId = ref<number | null>(null);

type LinkState = { label: string; variant: BadgeVariants['variant'] };

/** Derive a human label + tint mirroring the model's isOpen() precedence. */
function linkState(link: ApplicationLink): LinkState {
    if (link.revoked_at) {
        return { label: 'Revoked', variant: 'danger' };
    }

    if (link.expires_at && new Date(link.expires_at) <= new Date()) {
        return { label: 'Expired', variant: 'warning' };
    }

    if (!link.is_accepting) {
        return { label: 'Paused', variant: 'neutral' };
    }

    return { label: 'Open', variant: 'success' };
}

/** A revoked or expired link can no longer be toggled back on. */
function isLocked(link: ApplicationLink): boolean {
    return Boolean(
        link.revoked_at ||
        (link.expires_at && new Date(link.expires_at) <= new Date()),
    );
}

/** Short explanation of why a link's toggle is disabled, if it is. */
function lockReason(link: ApplicationLink): string | null {
    if (link.revoked_at) {
        return "Revoked links can't be reopened — create a new one.";
    }

    if (link.expires_at && new Date(link.expires_at) <= new Date()) {
        return 'This link has expired — create a new one to keep accepting applicants.';
    }

    return null;
}

function applicantLabel(count: number | undefined): string {
    const n = count ?? 0;

    return `${n} applicant${n === 1 ? '' : 's'}`;
}

function createLink(): void {
    router.post(
        ApplicationLinkController.store.url(props.unit.id),
        { label: newLabel.value || null },
        { preserveScroll: true, onSuccess: () => (newLabel.value = '') },
    );
}

function toggleAccepting(link: ApplicationLink): void {
    router.put(
        ApplicationLinkController.update.url(link.id),
        { is_accepting: !link.is_accepting },
        { preserveScroll: true },
    );
}

function revokeLink(link: ApplicationLink): void {
    if (
        confirm('Revoke this link? Applicants will no longer be able to apply.')
    ) {
        router.delete(ApplicationLinkController.destroy.url(link.id), {
            preserveScroll: true,
        });
    }
}

async function copyUrl(link: ApplicationLink): Promise<void> {
    await navigator.clipboard.writeText(link.public_url);
    copiedId.value = link.id;
    setTimeout(() => {
        if (copiedId.value === link.id) {
            copiedId.value = null;
        }
    }, 2000);
}
</script>

<template>
    <div
        class="flex flex-col gap-4 rounded-lg border border-border bg-muted/30 p-4"
    >
        <!-- Header: applicant count + form customization -->
        <div class="flex flex-wrap items-center justify-between gap-3">
            <span class="text-sm font-medium">Application links</span>
            <Link
                :href="applicantsIndex(unit.id)"
                class="flex items-center gap-1.5 text-xs text-muted-foreground transition-colors hover:text-foreground"
            >
                <Users class="size-3.5" />
                {{ applicantLabel(unit.applications_count) }}
            </Link>
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

        <!-- PRIMARY share link -->
        <div
            v-if="primaryLink"
            class="flex flex-col gap-3 rounded-md border border-border bg-card p-4"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <StatusBadge :variant="linkState(primaryLink).variant">
                        {{ linkState(primaryLink).label }}
                    </StatusBadge>
                    <span
                        v-if="primaryLink.label"
                        class="text-sm font-medium text-foreground"
                    >
                        {{ primaryLink.label }}
                    </span>
                    <span v-else class="text-sm font-medium text-foreground">
                        Share link
                    </span>
                </div>
                <span class="text-xs text-muted-foreground">
                    {{ applicantLabel(primaryLink.applications_count) }}
                </span>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <Input
                    :model-value="primaryLink.public_url"
                    readonly
                    class="font-mono text-xs"
                    @focus="
                        (e: FocusEvent) =>
                            (e.target as HTMLInputElement).select()
                    "
                />
                <Button
                    type="button"
                    class="sm:w-auto"
                    @click="copyUrl(primaryLink)"
                >
                    <Check v-if="copiedId === primaryLink.id" />
                    <Copy v-else />
                    {{ copiedId === primaryLink.id ? 'Copied' : 'Copy link' }}
                </Button>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <Button
                    size="sm"
                    variant="ghost"
                    type="button"
                    :disabled="isLocked(primaryLink)"
                    class="text-muted-foreground"
                    @click="toggleAccepting(primaryLink)"
                >
                    <Pause v-if="primaryLink.is_accepting" />
                    <Play v-else />
                    {{ primaryLink.is_accepting ? 'Pause' : 'Resume' }}
                </Button>
                <Button
                    v-if="!primaryLink.revoked_at"
                    size="sm"
                    variant="ghost"
                    type="button"
                    class="text-destructive"
                    @click="revokeLink(primaryLink)"
                >
                    <Ban />Revoke
                </Button>
            </div>

            <p
                v-if="lockReason(primaryLink)"
                class="text-xs text-muted-foreground"
            >
                {{ lockReason(primaryLink) }}
            </p>
        </div>

        <!-- Empty state: inviting call-to-action -->
        <div
            v-else
            class="flex flex-col items-center gap-3 rounded-md border border-dashed border-border bg-card p-6 text-center"
        >
            <p class="text-sm text-muted-foreground">
                Create a share link, then post it anywhere — applicants apply
                straight from the link.
            </p>
            <form
                class="flex w-full flex-col items-center gap-2 sm:flex-row sm:justify-center"
                @submit.prevent="createLink"
            >
                <Input
                    v-model="newLabel"
                    placeholder="Label (optional, e.g. Facebook post)"
                    class="text-sm sm:max-w-xs"
                />
                <Button type="submit"> <Plus />Create application link </Button>
            </form>
        </div>

        <!-- Additional links -->
        <div v-if="otherLinks.length" class="flex flex-col gap-2">
            <h3
                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
            >
                Other links
            </h3>
            <div
                v-for="link in otherLinks"
                :key="link.id"
                class="flex flex-col gap-2 rounded-md border border-border bg-card p-3"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <StatusBadge :variant="linkState(link).variant">
                            {{ linkState(link).label }}
                        </StatusBadge>
                        <span class="text-sm text-foreground">
                            {{ link.label ?? 'Share link' }}
                        </span>
                    </div>
                    <span class="text-xs text-muted-foreground">
                        {{ applicantLabel(link.applications_count) }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-1">
                    <Button
                        size="sm"
                        variant="ghost"
                        type="button"
                        class="text-muted-foreground"
                        @click="copyUrl(link)"
                    >
                        <Check
                            v-if="copiedId === link.id"
                            class="text-success"
                        />
                        <Copy v-else />
                        {{ copiedId === link.id ? 'Copied' : 'Copy' }}
                    </Button>
                    <Button
                        v-if="!isLocked(link)"
                        size="sm"
                        variant="ghost"
                        type="button"
                        class="text-muted-foreground"
                        @click="toggleAccepting(link)"
                    >
                        <Pause v-if="link.is_accepting" />
                        <Play v-else />
                        {{ link.is_accepting ? 'Pause' : 'Resume' }}
                    </Button>
                    <Button
                        v-if="!link.revoked_at"
                        size="sm"
                        variant="ghost"
                        type="button"
                        class="text-destructive"
                        @click="revokeLink(link)"
                    >
                        <Ban />Revoke
                    </Button>
                </div>
                <p
                    v-if="lockReason(link)"
                    class="text-xs text-muted-foreground"
                >
                    {{ lockReason(link) }}
                </p>
            </div>
        </div>

        <!-- Create another link (shown once at least one link exists) -->
        <form
            v-if="primaryLink"
            class="flex flex-col gap-2 sm:flex-row sm:items-center"
            @submit.prevent="createLink"
        >
            <Input
                v-model="newLabel"
                placeholder="Label (optional, e.g. Facebook post)"
                class="text-sm"
            />
            <Button type="submit" variant="outline" class="sm:w-auto">
                <Plus />New link
            </Button>
        </form>
    </div>
</template>
