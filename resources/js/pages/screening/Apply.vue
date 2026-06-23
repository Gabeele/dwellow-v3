<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CircleAlert } from '@lucide/vue';
import { computed, reactive } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface FormField {
    key: string;
    type: string;
    label: string;
    required: boolean;
    help: string | null;
    options: string[] | null;
}

interface FormSection {
    key: string;
    label: string;
    description: string;
    fields: FormField[];
}

interface UnitAddress {
    line1: string;
    line2: string | null;
    city: string;
    region: string;
    postal_code: string;
    country: string;
}

interface ReferenceValue {
    name: string;
    email: string;
    phone: string;
    relationship: string;
}

type AnswerValue = string | boolean | string[] | File | null | ReferenceValue;

const props = defineProps<{
    isOpen: boolean;
    unit: {
        label: string;
        address: UnitAddress;
    };
    sections: FormSection[];
}>();

const page = usePage();

const allFields = computed<FormField[]>(() =>
    props.sections.flatMap((section) => section.fields),
);

const addressLines = computed<string[]>(() => {
    const { line1, line2, city, region, postal_code } = props.unit.address;
    const cityLine = [city, region, postal_code].filter(Boolean).join(', ');

    return [line1, line2, cityLine].filter((line): line is string => !!line);
});

const referenceDefault = (): ReferenceValue => ({
    name: '',
    email: '',
    phone: '',
    relationship: '',
});

const initialValue = (type: string): AnswerValue => {
    switch (type) {
        case 'multi_choice':
            return [];
        case 'boolean':
        case 'consent':
            return false;
        case 'reference':
            return referenceDefault();
        case 'file':
            return null;
        default:
            return '';
    }
};

const initialAnswers: Record<string, AnswerValue> = {};

for (const field of allFields.value) {
    initialAnswers[field.key] = initialValue(field.type);
}

const form = useForm<{
    answers: Record<string, AnswerValue>;
}>({
    answers: initialAnswers,
});

const error = (key: string): string | undefined =>
    form.errors[`answers.${key}` as keyof typeof form.errors];

const hasErrors = computed<boolean>(() => Object.keys(form.errors).length > 0);

// Client-side file guards mirror the server rules so applicants learn about an
// oversized or wrong-type file the moment they pick it, not after submitting.
const MAX_FILE_BYTES = 10 * 1024 * 1024;
const FILE_ACCEPT = '.pdf,.jpg,.jpeg,.png,.heic,.webp,.doc,.docx';
const fileErrors = reactive<Record<string, string | undefined>>({});

const onFileChange = (key: string, event: Event): void => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    fileErrors[key] = undefined;

    if (file) {
        if (file.size > MAX_FILE_BYTES) {
            fileErrors[key] = 'That file is too large — the limit is 10 MB.';
            target.value = '';
            form.answers[key] = null;

            return;
        }

        const allowed = FILE_ACCEPT.split(',').some((ext) =>
            file.name.toLowerCase().endsWith(ext),
        );

        if (!allowed) {
            fileErrors[key] =
                'Use a PDF, image (JPG, PNG, HEIC, WEBP), or Word document.';
            target.value = '';
            form.answers[key] = null;

            return;
        }
    }

    form.answers[key] = file;
};

const reference = (key: string): ReferenceValue =>
    form.answers[key] as ReferenceValue;

const selectedChoices = (key: string): string[] =>
    (form.answers[key] as string[]) ?? [];

const isChecked = (key: string, option: string): boolean =>
    selectedChoices(key).includes(option);

const toggleChoice = (key: string, option: string, checked: boolean): void => {
    const current = selectedChoices(key);
    form.answers[key] = checked
        ? [...current, option]
        : current.filter((value) => value !== option);
};

const textInputType = (type: string): string => {
    switch (type) {
        case 'number':
        case 'currency':
            return 'number';
        case 'date':
            return 'date';
        default:
            return 'text';
    }
};

const canSubmit = computed<boolean>(() => !form.processing);

const submit = (): void => {
    form.post(page.url, {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <div>
        <Head title="Apply" />

        <header class="flex flex-col gap-2">
            <p
                class="text-13 font-medium tracking-wide text-muted-foreground uppercase"
            >
                Rental application
            </p>
            <h1 class="text-2xl font-semibold text-foreground">
                {{ unit.label }}
            </h1>
            <p v-if="addressLines.length" class="text-sm text-muted-foreground">
                {{ addressLines.join(' · ') }}
            </p>
        </header>

        <div
            v-if="!isOpen"
            class="mt-8 rounded-lg border border-border bg-card p-6 text-center shadow-card"
        >
            <h2 class="text-lg font-medium text-foreground">
                This application is no longer accepting submissions
            </h2>
            <p class="mt-2 text-sm text-muted-foreground">
                The link you followed has been paused, has expired, or is no
                longer available. Please reach out to the landlord for an
                up-to-date application link.
            </p>
        </div>

        <form v-else class="mt-8 flex flex-col gap-8" @submit.prevent="submit">
            <p class="text-13 text-muted-foreground">
                Fields marked with
                <span class="text-destructive">*</span> are required.
            </p>

            <!-- Each section the landlord enabled, with its fields grouped. -->
            <section
                v-for="section in sections"
                :key="section.key"
                class="flex flex-col gap-4"
            >
                <div class="flex flex-col gap-1 border-b border-border pb-2">
                    <h2 class="text-15 font-semibold text-foreground">
                        {{ section.label }}
                    </h2>
                    <p
                        v-if="section.description"
                        class="text-13 text-muted-foreground"
                    >
                        {{ section.description }}
                    </p>
                </div>

                <div
                    v-for="field in section.fields"
                    :key="field.key"
                    class="flex flex-col gap-2"
                >
                    <Label :for="`field-${field.key}`" class="text-sm">
                        {{ field.label }}
                        <span v-if="field.required" class="text-destructive"
                            >*</span
                        >
                    </Label>
                    <p
                        v-if="field.help && field.type !== 'consent'"
                        class="text-13 text-muted-foreground"
                    >
                        {{ field.help }}
                    </p>

                    <!-- Free-text, number, currency and date inputs. -->
                    <Input
                        v-if="
                            [
                                'short_text',
                                'number',
                                'currency',
                                'date',
                            ].includes(field.type)
                        "
                        :id="`field-${field.key}`"
                        v-model="form.answers[field.key] as string"
                        :type="textInputType(field.type)"
                        :step="field.type === 'currency' ? '0.01' : undefined"
                    />

                    <!-- Long free-text. -->
                    <textarea
                        v-else-if="field.type === 'long_text'"
                        :id="`field-${field.key}`"
                        v-model="form.answers[field.key] as string"
                        rows="4"
                        class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive md:text-sm dark:bg-input/30"
                    ></textarea>

                    <!-- Single choice. -->
                    <div
                        v-else-if="field.type === 'single_choice'"
                        class="flex flex-col gap-2"
                    >
                        <label
                            v-for="option in field.options ?? []"
                            :key="option"
                            class="flex items-center gap-2 text-sm text-foreground"
                        >
                            <input
                                v-model="form.answers[field.key]"
                                type="radio"
                                :name="`field-${field.key}`"
                                :value="option"
                                class="size-4 accent-primary"
                            />
                            {{ option }}
                        </label>
                    </div>

                    <!-- Multiple choice. -->
                    <div
                        v-else-if="field.type === 'multi_choice'"
                        class="flex flex-col gap-2"
                    >
                        <label
                            v-for="option in field.options ?? []"
                            :key="option"
                            class="flex items-center gap-2 text-sm text-foreground"
                        >
                            <Checkbox
                                :model-value="isChecked(field.key, option)"
                                @update:model-value="
                                    (checked) =>
                                        toggleChoice(
                                            field.key,
                                            option,
                                            !!checked,
                                        )
                                "
                            />
                            {{ option }}
                        </label>
                    </div>

                    <!-- Yes / no. -->
                    <label
                        v-else-if="field.type === 'boolean'"
                        class="flex items-center gap-2 text-sm text-foreground"
                    >
                        <Checkbox
                            v-model="form.answers[field.key] as boolean"
                        />
                        Yes
                    </label>

                    <!-- Reference block. -->
                    <div
                        v-else-if="field.type === 'reference'"
                        class="grid gap-3 rounded-lg border border-border bg-card p-4 sm:grid-cols-2"
                    >
                        <div class="grid gap-2">
                            <Label
                                :for="`field-${field.key}-name`"
                                class="text-13"
                            >
                                Name
                                <span
                                    v-if="field.required"
                                    class="text-destructive"
                                    >*</span
                                >
                            </Label>
                            <Input
                                :id="`field-${field.key}-name`"
                                v-model="reference(field.key).name"
                            />
                            <InputError :message="error(`${field.key}.name`)" />
                        </div>
                        <div class="grid gap-2">
                            <Label
                                :for="`field-${field.key}-relationship`"
                                class="text-13"
                            >
                                Relationship
                            </Label>
                            <Input
                                :id="`field-${field.key}-relationship`"
                                v-model="reference(field.key).relationship"
                            />
                            <InputError
                                :message="error(`${field.key}.relationship`)"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label
                                :for="`field-${field.key}-email`"
                                class="text-13"
                            >
                                Email
                            </Label>
                            <Input
                                :id="`field-${field.key}-email`"
                                v-model="reference(field.key).email"
                                type="email"
                            />
                            <InputError
                                :message="error(`${field.key}.email`)"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label
                                :for="`field-${field.key}-phone`"
                                class="text-13"
                            >
                                Phone
                            </Label>
                            <Input
                                :id="`field-${field.key}-phone`"
                                v-model="reference(field.key).phone"
                                type="tel"
                            />
                            <InputError
                                :message="error(`${field.key}.phone`)"
                            />
                        </div>
                    </div>

                    <!-- File upload. -->
                    <input
                        v-else-if="field.type === 'file'"
                        :id="`field-${field.key}`"
                        type="file"
                        :accept="FILE_ACCEPT"
                        class="text-sm text-foreground file:mr-3 file:rounded-md file:border file:border-border file:bg-transparent file:px-3 file:py-1.5 file:text-sm file:font-medium"
                        @change="(event) => onFileChange(field.key, event)"
                    />

                    <!-- Consent acknowledgement. -->
                    <label
                        v-else-if="field.type === 'consent'"
                        class="flex items-start gap-2 text-sm text-foreground"
                    >
                        <Checkbox
                            v-model="form.answers[field.key] as boolean"
                            class="mt-0.5"
                        />
                        <span>{{ field.help ?? field.label }}</span>
                    </label>

                    <InputError
                        :message="fileErrors[field.key] ?? error(field.key)"
                    />
                </div>
            </section>

            <!-- Surfaced after a rejected submit so nothing fails silently. -->
            <div
                v-if="hasErrors"
                class="flex items-start gap-2 rounded-lg border border-destructive/40 bg-destructive/5 p-3 text-13 text-destructive"
            >
                <CircleAlert class="mt-0.5 size-4 shrink-0" />
                <span>
                    Some answers need a closer look — check the highlighted
                    fields above and try again.
                </span>
            </div>

            <div class="pt-2">
                <Button :disabled="!canSubmit" type="submit">
                    {{ form.processing ? 'Submitting…' : 'Submit application' }}
                </Button>
            </div>
        </form>
    </div>
</template>
