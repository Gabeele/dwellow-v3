<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

/**
 * A reusable confirmation modal, replacing the browser's native `confirm()`.
 * Control visibility with `v-model:open`; the parent decides what happens on
 * `@confirm` (and is responsible for closing the dialog once its action runs).
 * Extra content — checkboxes, warnings — goes in the default slot.
 */
withDefaults(
    defineProps<{
        title: string;
        description?: string;
        confirmLabel?: string;
        cancelLabel?: string;
        destructive?: boolean;
        processing?: boolean;
        confirmDisabled?: boolean;
    }>(),
    {
        description: undefined,
        confirmLabel: 'Confirm',
        cancelLabel: 'Cancel',
        destructive: false,
        processing: false,
        confirmDisabled: false,
    },
);

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{ confirm: []; cancel: [] }>();

function onCancel(): void {
    open.value = false;
    emit('cancel');
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription v-if="description">
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <div v-if="$slots.default" class="flex flex-col gap-3">
                <slot />
            </div>

            <DialogFooter>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="processing"
                    @click="onCancel"
                >
                    {{ cancelLabel }}
                </Button>
                <Button
                    type="button"
                    :variant="destructive ? 'destructive' : 'default'"
                    :disabled="processing || confirmDisabled"
                    @click="emit('confirm')"
                >
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
