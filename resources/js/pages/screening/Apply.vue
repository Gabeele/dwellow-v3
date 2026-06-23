<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CircleAlert, LockKeyhole, Pencil } from '@lucide/vue';
import { computed, onMounted, reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatAddressLines } from '@/lib/address';

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

type ClosedReason = 'revoked' | 'expired' | 'not_accepting';

const props = defineProps<{
    isOpen: boolean;
    closedReason: ClosedReason | null;
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

// Each closed reason gets its own friendly, on-brand explanation so the applicant
// understands why the form isn't here and what to do next — never a dead end.
const closedCopy = computed<{ title: string; body: string }>(() => {
    switch (props.closedReason) {
        case 'revoked':
            return {
                title: 'This application link has been turned off',
                body: 'The landlord is no longer collecting applications through this link. If you were invited to apply, reach out to them for a current link.',
            };
        case 'expired':
            return {
                title: 'This application link has expired',
                body: 'This link was only open for a limited time and has since closed. Reach out to the landlord for an up-to-date application link.',
            };
        case 'not_accepting':
            return {
                title: "This listing isn't accepting applications right now",
                body: 'The landlord has paused new applications for this unit. Reach out to them to find out when it reopens.',
            };
        default:
            return {
                title: 'This application is no longer accepting submissions',
                body: 'The link you followed is no longer available. Please reach out to the landlord for an up-to-date application link.',
            };
    }
});

const addressLines = computed<string[]>(() =>
    formatAddressLines(props.unit.address),
);

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

// Honeypot: `contact_channel` is hidden from humans (bots fill every field they
// find), and `rendered_at` lets the server reject submissions returned faster
// than a person could fill the form. Both are checked in StoreApplicationRequest.
const form = useForm<{
    answers: Record<string, AnswerValue>;
    contact_channel: string;
    rendered_at: number | null;
}>({
    answers: initialAnswers,
    contact_channel: '',
    rendered_at: null,
});

// Set on mount (not at module load) to avoid an SSR/hydration time mismatch.
onMounted(() => {
    form.rendered_at = Math.floor(Date.now() / 1000);
});

const error = (key: string): string | undefined =>
    form.errors[`answers.${key}` as keyof typeof form.errors];

const hasErrors = computed<boolean>(() => Object.keys(form.errors).length > 0);

// Stable ids let each control point screen readers at its help text and error
// message via `aria-describedby`, and flag itself with `aria-invalid`.
const helpId = (key: string): string => `field-${key}-help`;
const errorId = (key: string): string => `field-${key}-error`;

const fieldError = (key: string): string | undefined =>
    fileErrors[key] ?? error(key);

const describedBy = (field: FormField): string | undefined => {
    const ids: string[] = [];

    if (field.help && field.type !== 'consent') {
        ids.push(helpId(field.key));
    }

    if (fieldError(field.key)) {
        ids.push(errorId(field.key));
    }

    return ids.length ? ids.join(' ') : undefined;
};

// Client-side file guards mirror the server rules so applicants learn about an
// oversized or wrong-type file the moment they pick it, not after submitting.
const MAX_FILE_BYTES = 10 * 1024 * 1024;
const FILE_ACCEPT = '.pdf,.jpg,.jpeg,.png,.heic,.webp,.doc,.docx';
const FILE_ACCEPT_HINT =
    'PDF, image (JPG, PNG, HEIC, WEBP), or Word document — up to 10 MB.';
const fileErrors = reactive<Record<string, string | undefined>>({});

// Keep a handle on each native file input so "Remove" can also clear the
// browser's own picked-file state (setting the model to null isn't enough).
const fileInputs: Record<string, HTMLInputElement | null> = {};

const registerFileInput = (key: string, el: unknown): void => {
    fileInputs[key] = (el as HTMLInputElement | null) ?? null;
};

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

const clearFile = (key: string): void => {
    form.answers[key] = null;
    fileErrors[key] = undefined;

    const input = fileInputs[key];

    if (input) {
        input.value = '';
    }
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

// Applicants apply on a phone, so the final step is a read-only recap of what
// they entered (and the files they attached) before anything is sent. `submit`
// only fires from the review panel; the form's own button just opens the recap.
const reviewing = ref<boolean>(false);

const openReview = (): void => {
    reviewing.value = true;
    window.scrollTo({ top: 0, behavior: 'smooth' });
};

const backToEdit = (): void => {
    reviewing.value = false;
};

const referenceLines = (key: string): string[] => {
    const value = reference(key);

    return [value.name, value.relationship, value.email, value.phone].filter(
        (line): line is string => !!line && line.trim() !== '',
    );
};

const attachedFile = (key: string): File | null => {
    const value = form.answers[key];

    return value instanceof File ? value : null;
};

const formatFileSize = (bytes: number): string => {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

// A human-readable rendering of a single answer for the recap panel. Files and
// references are rendered separately in the template (they have richer markup).
const displayValue = (field: FormField): string => {
    const value = form.answers[field.key];

    switch (field.type) {
        case 'boolean':
            return value ? 'Yes' : 'No';
        case 'consent':
            return value ? 'Acknowledged' : 'Not acknowledged';
        case 'multi_choice': {
            const choices = (value as string[]) ?? [];

            return choices.length ? choices.join(', ') : '—';
        }
        case 'currency':
            return value ? `$${value}` : '—';
        default: {
            const text = value == null ? '' : String(value).trim();

            return text === '' ? '—' : text;
        }
    }
};

const submit = (): void => {
    form.post(page.url, {
        forceFormData: true,
        preserveScroll: true,
        // A rejected submission needs the highlighted fields, so drop back to
        // the form rather than stranding the applicant on the recap.
        onError: () => {
            reviewing.value = false;
        },
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
            class="mt-8 flex flex-col items-center gap-4 rounded-lg border border-border bg-card p-8 text-center shadow-card"
        >
            <span
                class="flex size-12 items-center justify-center rounded-full bg-muted text-muted-foreground"
            >
                <LockKeyhole class="size-6" />
            </span>
            <div class="flex flex-col gap-2">
                <h2 class="text-lg font-medium text-foreground">
                    {{ closedCopy.title }}
                </h2>
                <p class="text-sm text-muted-foreground">
                    {{ closedCopy.body }}
                </p>
            </div>
        </div>

        <form
            v-else
            v-show="!reviewing"
            class="mt-8 flex flex-col gap-8"
            @submit.prevent="openReview"
        >
            <!-- Honeypot: hidden from humans and assistive tech; bots fill it. -->
            <div aria-hidden="true" class="hidden">
                <label for="contact_channel">Preferred contact channel</label>
                <input
                    id="contact_channel"
                    v-model="form.contact_channel"
                    type="text"
                    tabindex="-1"
                    autocomplete="off"
                />
            </div>

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
                        :id="helpId(field.key)"
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
                        :aria-describedby="describedBy(field)"
                        :aria-invalid="fieldError(field.key) ? true : undefined"
                    />

                    <!-- Long free-text. -->
                    <textarea
                        v-else-if="field.type === 'long_text'"
                        :id="`field-${field.key}`"
                        v-model="form.answers[field.key] as string"
                        rows="4"
                        :aria-describedby="describedBy(field)"
                        :aria-invalid="fieldError(field.key) ? true : undefined"
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
                    <div
                        v-else-if="field.type === 'file'"
                        class="flex flex-col gap-2"
                    >
                        <input
                            :id="`field-${field.key}`"
                            :ref="(el) => registerFileInput(field.key, el)"
                            type="file"
                            :accept="FILE_ACCEPT"
                            :aria-describedby="describedBy(field)"
                            :aria-invalid="
                                fieldError(field.key) ? true : undefined
                            "
                            class="text-sm text-foreground file:mr-3 file:rounded-md file:border file:border-border file:bg-transparent file:px-3 file:py-1.5 file:text-sm file:font-medium"
                            @change="(event) => onFileChange(field.key, event)"
                        />
                        <p class="text-13 text-muted-foreground">
                            {{ FILE_ACCEPT_HINT }}
                        </p>
                        <div
                            v-if="attachedFile(field.key)"
                            class="flex items-center justify-between gap-3 rounded-md border border-border bg-card px-3 py-2 text-13"
                        >
                            <span class="min-w-0 truncate text-foreground">
                                {{ attachedFile(field.key)?.name }}
                                <span class="text-muted-foreground">
                                    ({{
                                        formatFileSize(
                                            attachedFile(field.key)!.size,
                                        )
                                    }})
                                </span>
                            </span>
                            <button
                                type="button"
                                class="shrink-0 font-medium text-destructive hover:underline"
                                @click="clearFile(field.key)"
                            >
                                Remove
                            </button>
                        </div>
                    </div>

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
                        :id="errorId(field.key)"
                        :message="fieldError(field.key)"
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
                <Button type="submit"> Review application </Button>
            </div>
        </form>

        <!-- Read-only recap so the applicant can confirm before submitting. -->
        <section v-if="isOpen && reviewing" class="mt-8 flex flex-col gap-8">
            <div class="flex flex-col gap-1 border-b border-border pb-2">
                <h2 class="text-15 font-semibold text-foreground">
                    Review your application
                </h2>
                <p class="text-13 text-muted-foreground">
                    Check everything looks right. You can go back and edit any
                    answer before submitting.
                </p>
            </div>

            <div
                v-for="section in sections"
                :key="section.key"
                class="flex flex-col gap-3"
            >
                <h3 class="text-sm font-semibold text-foreground">
                    {{ section.label }}
                </h3>

                <dl class="flex flex-col divide-y divide-border">
                    <div
                        v-for="field in section.fields"
                        :key="field.key"
                        class="flex flex-col gap-1 py-2 sm:flex-row sm:gap-4"
                    >
                        <dt
                            class="text-13 text-muted-foreground sm:w-1/3 sm:shrink-0"
                        >
                            {{ field.label }}
                        </dt>
                        <dd class="text-sm text-foreground sm:w-2/3">
                            <!-- A reference is a small block of contact lines. -->
                            <ul
                                v-if="field.type === 'reference'"
                                class="flex flex-col gap-0.5"
                            >
                                <li
                                    v-for="line in referenceLines(field.key)"
                                    :key="line"
                                >
                                    {{ line }}
                                </li>
                                <li
                                    v-if="!referenceLines(field.key).length"
                                    class="text-muted-foreground"
                                >
                                    —
                                </li>
                            </ul>

                            <!-- An uploaded file shows its name and size. -->
                            <span v-else-if="field.type === 'file'">
                                <template v-if="attachedFile(field.key)">
                                    {{ attachedFile(field.key)?.name }}
                                    <span class="text-muted-foreground">
                                        ({{
                                            formatFileSize(
                                                attachedFile(field.key)!.size,
                                            )
                                        }})
                                    </span>
                                </template>
                                <span v-else class="text-muted-foreground"
                                    >No file attached</span
                                >
                            </span>

                            <span v-else>{{ displayValue(field) }}</span>
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- A rejected submit drops back to the form, so surface why here too. -->
            <div
                v-if="hasErrors"
                class="flex items-start gap-2 rounded-lg border border-destructive/40 bg-destructive/5 p-3 text-13 text-destructive"
            >
                <CircleAlert class="mt-0.5 size-4 shrink-0" />
                <span>
                    Some answers need a closer look — check the highlighted
                    fields and try again.
                </span>
            </div>

            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="form.processing"
                    @click="backToEdit"
                >
                    <Pencil class="size-4" />
                    Edit answers
                </Button>
                <Button type="button" :disabled="!canSubmit" @click="submit">
                    <Spinner v-if="form.processing" />
                    {{ form.processing ? 'Submitting…' : 'Submit application' }}
                </Button>
            </div>
        </section>
    </div>
</template>
