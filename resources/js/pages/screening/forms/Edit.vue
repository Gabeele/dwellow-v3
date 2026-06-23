<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Plus, RotateCcw, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ApplicationFormController from '@/actions/App/Http/Controllers/ApplicationFormController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { index, show } from '@/routes/properties';
import type { Property, Unit } from '@/types/property';

interface FormField {
    key: string;
    type: string;
    label: string;
    required: boolean;
    help: string | null;
    options: string[] | null;
}

interface FieldTypeOption {
    value: string;
    label: string;
    expectsOptions: boolean;
    isFileUpload: boolean;
}

const props = defineProps<{
    property: Property;
    unit: Unit;
    fields: FormField[];
    fieldTypes: FieldTypeOption[];
    defaultFields: FormField[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: index() },
            { title: 'Application form', href: '#' },
        ],
    },
});

const clone = (fields: FormField[]): FormField[] =>
    fields.map((field) => ({
        ...field,
        options: field.options ? [...field.options] : null,
    }));

const form = useForm<{ fields: FormField[] }>({ fields: clone(props.fields) });

// Stable keys for v-for so reordering preserves input focus/state.
let nextUid = 0;
const uids = ref<number[]>(form.fields.map(() => nextUid++));

const typeMeta = (type: string): FieldTypeOption | undefined =>
    props.fieldTypes.find((option) => option.value === type);

const error = (index: number, attribute: string): string | undefined =>
    form.errors[`fields.${index}.${attribute}` as keyof typeof form.errors];

const generateKey = (): string => {
    let counter = form.fields.length + 1;
    const taken = new Set(form.fields.map((field) => field.key));

    while (taken.has(`field_${counter}`)) {
        counter++;
    }

    return `field_${counter}`;
};

const addField = (): void => {
    form.fields.push({
        key: generateKey(),
        type: 'short_text',
        label: '',
        required: false,
        help: null,
        options: null,
    });
    uids.value.push(nextUid++);
};

const removeField = (index: number): void => {
    form.fields.splice(index, 1);
    uids.value.splice(index, 1);
};

const swap = (a: number, b: number): void => {
    [form.fields[a], form.fields[b]] = [form.fields[b], form.fields[a]];
    [uids.value[a], uids.value[b]] = [uids.value[b], uids.value[a]];
};

const moveUp = (index: number): void => {
    if (index > 0) {
        swap(index, index - 1);
    }
};

const moveDown = (index: number): void => {
    if (index < form.fields.length - 1) {
        swap(index, index + 1);
    }
};

const onTypeChange = (field: FormField, value: string): void => {
    field.type = value;

    if (typeMeta(value)?.expectsOptions) {
        if (!field.options || field.options.length === 0) {
            field.options = [''];
        }
    } else {
        field.options = null;
    }
};

const addOption = (field: FormField): void => {
    field.options = [...(field.options ?? []), ''];
};

const removeOption = (field: FormField, index: number): void => {
    field.options?.splice(index, 1);
};

const resetToDefault = (): void => {
    form.fields = clone(props.defaultFields);
    form.clearErrors();
    uids.value = form.fields.map(() => nextUid++);
};

const submit = (): void => {
    form.put(ApplicationFormController.update.url(props.unit.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Application form" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6 lg:p-10">
        <PageHeader
            eyebrow="Application form"
            :title="unit.label"
            :back="{
                label: property.name || property.address_line1,
                href: show(property.id),
            }"
        >
            <template #actions>
                <Button type="button" variant="outline" @click="resetToDefault">
                    <RotateCcw class="size-4" />
                    Reset to default
                </Button>
            </template>
        </PageHeader>

        <p class="text-sm text-muted-foreground">
            Customize the fields a prospective tenant fills in when applying to
            this unit. Drag-free reordering, required toggles, and choice
            options are all here.
        </p>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div
                v-for="(field, index) in form.fields"
                :key="uids[index]"
                class="rounded-lg border border-border bg-card p-5 shadow-card"
            >
                <div class="flex items-start justify-between gap-3">
                    <span
                        class="mt-2 text-13 font-medium text-muted-foreground"
                    >
                        Field {{ index + 1 }}
                    </span>
                    <div class="flex items-center gap-1">
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :disabled="index === 0"
                            aria-label="Move field up"
                            @click="moveUp(index)"
                        >
                            <ArrowUp class="size-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            :disabled="index === form.fields.length - 1"
                            aria-label="Move field down"
                            @click="moveDown(index)"
                        >
                            <ArrowDown class="size-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="text-destructive hover:text-destructive"
                            aria-label="Remove field"
                            @click="removeField(index)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>

                <div class="mt-2 grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label :for="`label-${index}`" class="text-sm">
                            Label
                        </Label>
                        <Input
                            :id="`label-${index}`"
                            v-model="field.label"
                            placeholder="e.g. First name"
                        />
                        <InputError :message="error(index, 'label')" />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`key-${index}`" class="text-sm">
                            Key
                        </Label>
                        <Input
                            :id="`key-${index}`"
                            v-model="field.key"
                            placeholder="e.g. first_name"
                            class="font-mono text-13"
                        />
                        <InputError :message="error(index, 'key')" />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`type-${index}`" class="text-sm">
                            Type
                        </Label>
                        <Select
                            :model-value="field.type"
                            @update:model-value="
                                (value) => onTypeChange(field, value as string)
                            "
                        >
                            <SelectTrigger
                                :id="`type-${index}`"
                                class="w-full"
                            >
                                <SelectValue>
                                    {{ typeMeta(field.type)?.label }}
                                </SelectValue>
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in fieldTypes"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="error(index, 'type')" />
                    </div>

                    <div class="flex items-end pb-2">
                        <label
                            class="flex items-center gap-2 text-sm text-foreground"
                        >
                            <Checkbox v-model="field.required" />
                            Required
                        </label>
                    </div>
                </div>

                <div class="mt-4 grid gap-2">
                    <Label :for="`help-${index}`" class="text-sm">
                        Help text
                        <span class="text-muted-foreground">(optional)</span>
                    </Label>
                    <Input
                        :id="`help-${index}`"
                        :model-value="field.help ?? ''"
                        placeholder="Shown beneath the field to guide the applicant"
                        @update:model-value="
                            (value) => (field.help = (value as string) || null)
                        "
                    />
                    <InputError :message="error(index, 'help')" />
                </div>

                <div
                    v-if="typeMeta(field.type)?.expectsOptions"
                    class="mt-4 grid gap-2"
                >
                    <Separator class="mb-1" />
                    <Label class="text-sm">Choice options</Label>
                    <div
                        v-for="(option, optionIndex) in field.options ?? []"
                        :key="optionIndex"
                        class="flex items-center gap-2"
                    >
                        <Input
                            v-model="field.options![optionIndex]"
                            placeholder="Option label"
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="text-destructive hover:text-destructive"
                            aria-label="Remove option"
                            @click="removeOption(field, optionIndex)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                    <InputError :message="error(index, 'options')" />
                    <div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="addOption(field)"
                        >
                            <Plus class="size-4" />
                            Add option
                        </Button>
                    </div>
                </div>
            </div>

            <InputError :message="form.errors.fields" />

            <div>
                <Button type="button" variant="outline" @click="addField">
                    <Plus class="size-4" />
                    Add field
                </Button>
            </div>

            <Separator />

            <div class="flex items-center gap-3">
                <Button :disabled="form.processing" type="submit">
                    Save form
                </Button>
                <Button as-child variant="ghost">
                    <Link :href="show(property.id)">Cancel</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
