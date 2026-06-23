<script setup lang="ts">
import { Check, Inbox, MessageSquare, X } from '@lucide/vue';
import type { HTMLAttributes } from 'vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

/**
 * A decision action cluster for the review flow: request more info,
 * open the review queue, approve, or decline. Each action emits a named
 * event so the host page can wire its own handlers.
 */
const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();

const emit = defineEmits<{
    requestInfo: [];
    reviewQueue: [];
    approve: [];
    decline: [];
}>();
</script>

<template>
    <div :class="cn('flex flex-wrap items-center gap-2', props.class)">
        <Button variant="outline" @click="emit('requestInfo')">
            <MessageSquare />
            Request info
        </Button>
        <Button variant="ghost" @click="emit('reviewQueue')">
            <Inbox />
            Review queue
        </Button>
        <div class="ml-auto flex items-center gap-2">
            <Button variant="destructive" @click="emit('decline')">
                <X />
                Decline
            </Button>
            <Button
                class="bg-success text-success-foreground hover:bg-success/90"
                @click="emit('approve')"
            >
                <Check />
                Approve
            </Button>
        </div>
    </div>
</template>
