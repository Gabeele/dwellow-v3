<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { Ban, Check, Copy, Plus, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import ApplicationLinkController from '@/actions/App/Http/Controllers/ApplicationLinkController';
import StatusBadge from '@/components/StatusBadge.vue';
import type { BadgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { index as applicantsIndex } from '@/routes/units/applicants';
import type { ApplicationLink, Unit } from '@/types/property';

const props = defineProps<{
    unit: Unit;
}>();

const links = computed<ApplicationLink[]>(() => props.unit.application_links ?? []);

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
    if (confirm('Revoke this link? Applicants will no longer be able to apply.')) {
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
    }, 1500);
}
</script>

<template>
    <div class="flex flex-col gap-4 rounded-lg border border-border bg-muted/30 p-4">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 text-sm font-medium">
                <span>Application links</span>
                <span class="font-mono text-xs text-muted-foreground">
                    {{ links.length }}
                </span>
            </div>
            <Link
                :href="applicantsIndex(unit.id)"
                class="flex items-center gap-1.5 text-xs text-muted-foreground transition-colors hover:text-foreground"
            >
                <Users class="size-3.5" />
                {{ unit.applications_count ?? 0 }} applicant{{
                    (unit.applications_count ?? 0) === 1 ? '' : 's'
                }}
            </Link>
        </div>

        <!-- Existing links -->
        <div v-if="links.length" class="flex flex-col gap-3">
            <div
                v-for="link in links"
                :key="link.id"
                class="flex flex-col gap-3 rounded-md border border-border bg-card p-3"
            >
                <div class="flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <StatusBadge :variant="linkState(link).variant">
                            {{ linkState(link).label }}
                        </StatusBadge>
                        <span
                            v-if="link.label"
                            class="text-sm font-medium text-foreground"
                        >
                            {{ link.label }}
                        </span>
                    </div>
                    <span class="text-xs text-muted-foreground">
                        {{ link.applications_count ?? 0 }} applicant{{
                            (link.applications_count ?? 0) === 1 ? '' : 's'
                        }}
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <Input
                        :model-value="link.public_url"
                        readonly
                        class="font-mono text-xs"
                        @focus="(e: FocusEvent) => (e.target as HTMLInputElement).select()"
                    />
                    <Button
                        size="icon"
                        variant="outline"
                        type="button"
                        :title="copiedId === link.id ? 'Copied' : 'Copy link'"
                        @click="copyUrl(link)"
                    >
                        <Check v-if="copiedId === link.id" class="text-success" />
                        <Copy v-else />
                    </Button>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <label
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                        :class="isLocked(link) && 'opacity-50'"
                    >
                        <Checkbox
                            :model-value="link.is_accepting && !isLocked(link)"
                            :disabled="isLocked(link)"
                            @update:model-value="toggleAccepting(link)"
                        />
                        Accepting submissions
                    </label>
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
            </div>
        </div>

        <p v-else class="text-sm text-muted-foreground">
            No links yet. Create one to start accepting applicants.
        </p>

        <!-- Create a new link -->
        <form class="flex items-center gap-2" @submit.prevent="createLink">
            <Input
                v-model="newLabel"
                placeholder="Label (optional, e.g. Facebook post)"
                class="text-sm"
            />
            <Button type="submit" variant="outline">
                <Plus />New link
            </Button>
        </form>
    </div>
</template>
