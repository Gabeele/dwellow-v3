<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Check, Lock, RotateCcw } from '@lucide/vue';
import { computed, reactive } from 'vue';
import ApplicationFormController from '@/actions/App/Http/Controllers/ApplicationFormController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Separator } from '@/components/ui/separator';
import { index, show } from '@/routes/properties';
import type { EditableFormSection, Property, Unit } from '@/types/property';

const props = defineProps<{
    property: Property;
    unit: Unit;
    sections: EditableFormSection[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: index() },
            { title: 'Application form', href: '#' },
        ],
    },
});

const clone = (sections: EditableFormSection[]): EditableFormSection[] =>
    sections.map((section) => ({
        ...section,
        // A locked section is always included regardless of what was saved.
        enabled: section.locked ? true : section.enabled,
        fields: section.fields.map((field) => ({ ...field })),
    }));

// Local, reactive copy drives the toggles; the form payload is just the keys.
const sections = reactive<EditableFormSection[]>(clone(props.sections));

const enabledKeys = (): string[] =>
    sections
        .filter((section) => section.locked || section.enabled)
        .map((section) => section.key);

const form = useForm<{ enabled_sections: string[] }>({
    enabled_sections: enabledKeys(),
});

const includedCount = computed<number>(
    () =>
        sections.filter((section) => section.locked || section.enabled).length,
);

const syncForm = (): void => {
    form.enabled_sections = enabledKeys();
};

const toggle = (section: EditableFormSection): void => {
    if (section.locked) {
        return;
    }

    section.enabled = !section.enabled;
    syncForm();
};

const resetToDefault = (): void => {
    sections.forEach((section) => {
        section.enabled = true;
    });
    form.clearErrors();
    syncForm();
};

const submit = (): void => {
    syncForm();
    form.put(ApplicationFormController.update.url(props.unit.id), {
        preserveScroll: true,
    });
};

const requiredCount = (section: EditableFormSection): number =>
    section.fields.filter((field) => field.required).length;
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
                    Include everything
                </Button>
            </template>
        </PageHeader>

        <p class="text-sm text-muted-foreground">
            Choose which sections applicants fill in. Toggle a whole section on
            or off — the fields inside are tuned for rental screening and stay
            consistent for every applicant.
            <span class="font-medium text-foreground"
                >{{ includedCount }} of {{ sections.length }} sections
                included.</span
            >
        </p>

        <form class="flex flex-col gap-4" @submit.prevent="submit">
            <div
                v-for="section in sections"
                :key="section.key"
                class="rounded-lg border bg-card p-5 shadow-card transition-colors"
                :class="
                    section.locked || section.enabled
                        ? 'border-border'
                        : 'border-dashed border-border'
                "
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <h2 class="text-15 font-semibold text-foreground">
                                {{ section.label }}
                            </h2>
                            <span
                                v-if="section.locked"
                                class="text-11 inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5 font-medium text-muted-foreground"
                            >
                                <Lock class="size-3" />
                                Always included
                            </span>
                        </div>
                        <p class="text-13 text-muted-foreground">
                            {{ section.description }}
                        </p>
                    </div>

                    <label
                        class="flex shrink-0 items-center gap-2 text-13 font-medium"
                        :class="
                            section.locked ? 'cursor-default' : 'cursor-pointer'
                        "
                    >
                        <span
                            :class="
                                section.locked || section.enabled
                                    ? 'text-foreground'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{
                                section.locked || section.enabled
                                    ? 'Included'
                                    : 'Hidden'
                            }}
                        </span>
                        <Checkbox
                            :model-value="section.locked || section.enabled"
                            :disabled="section.locked"
                            @update:model-value="toggle(section)"
                        />
                    </label>
                </div>

                <Separator class="my-4" />

                <div
                    class="flex flex-col gap-2"
                    :class="{
                        'opacity-50': !(section.locked || section.enabled),
                    }"
                >
                    <p
                        class="text-11 font-medium tracking-wide text-muted-foreground uppercase"
                    >
                        {{ section.fields.length }}
                        {{ section.fields.length === 1 ? 'field' : 'fields' }}
                        <span v-if="requiredCount(section)">
                            · {{ requiredCount(section) }} required
                        </span>
                    </p>
                    <ul class="grid gap-1.5 sm:grid-cols-2">
                        <li
                            v-for="field in section.fields"
                            :key="field.key"
                            class="flex items-center gap-2 text-13 text-foreground"
                        >
                            <Check class="size-3.5 text-muted-foreground" />
                            <span>{{ field.label }}</span>
                            <span
                                v-if="field.required"
                                class="text-destructive"
                                aria-label="Required"
                                >*</span
                            >
                        </li>
                    </ul>
                </div>
            </div>

            <InputError :message="form.errors.enabled_sections" />

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
