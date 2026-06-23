<script setup lang="ts">
import { Head, useForm, useHttp, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
    fields: FormField[];
}>();

const page = usePage();

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

for (const field of props.fields) {
    initialAnswers[field.key] = initialValue(field.type);
}

const form = useForm<{
    answers: Record<string, AnswerValue>;
    verification_code: string;
}>({
    answers: initialAnswers,
    verification_code: '',
});

const error = (key: string): string | undefined =>
    form.errors[`answers.${key}` as keyof typeof form.errors];

// Account-free email verification: the applicant requests a one-time code for the
// email they entered above, then enters it to prove ownership. The code request is a
// standalone XHR (useHttp) so it never reloads the page or discards the in-progress form.
const verifier = useHttp<{ email: string }>({ email: '' });
const codeSent = ref(false);

const applicantEmail = computed<string>(
    () => (form.answers.email as string | undefined)?.trim() ?? '',
);

const sendCode = (): void => {
    verifier.email = applicantEmail.value;

    verifier.post(`${page.url}/verify`, {
        onSuccess: () => {
            codeSent.value = true;
        },
    });
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

const onFileChange = (key: string, event: Event): void => {
    const target = event.target as HTMLInputElement;
    form.answers[key] = target.files?.[0] ?? null;
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
            <p class="text-13 font-medium tracking-wide text-muted-foreground uppercase">
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

        <form
            v-else
            class="mt-8 flex flex-col gap-6"
            @submit.prevent="submit"
        >
            <div
                v-for="field in fields"
                :key="field.key"
                class="flex flex-col gap-2"
            >
                <Label :for="`field-${field.key}`" class="text-sm">
                    {{ field.label }}
                    <span v-if="field.required" class="text-destructive">*</span>
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
                        ['short_text', 'number', 'currency', 'date'].includes(
                            field.type,
                        )
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
                    class="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:border-destructive dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-base shadow-xs outline-none transition-[color,box-shadow] focus-visible:ring-[3px] md:text-sm"
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
                                    toggleChoice(field.key, option, !!checked)
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
                    <Checkbox v-model="form.answers[field.key] as boolean" />
                    Yes
                </label>

                <!-- Reference block. -->
                <div
                    v-else-if="field.type === 'reference'"
                    class="grid gap-3 rounded-lg border border-border bg-card p-4 sm:grid-cols-2"
                >
                    <div class="grid gap-2">
                        <Label :for="`field-${field.key}-name`" class="text-13">
                            Name
                        </Label>
                        <Input
                            :id="`field-${field.key}-name`"
                            v-model="reference(field.key).name"
                        />
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
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`field-${field.key}-email`" class="text-13">
                            Email
                        </Label>
                        <Input
                            :id="`field-${field.key}-email`"
                            v-model="reference(field.key).email"
                            type="email"
                        />
                    </div>
                    <div class="grid gap-2">
                        <Label :for="`field-${field.key}-phone`" class="text-13">
                            Phone
                        </Label>
                        <Input
                            :id="`field-${field.key}-phone`"
                            v-model="reference(field.key).phone"
                            type="tel"
                        />
                    </div>
                </div>

                <!-- File upload. -->
                <input
                    v-else-if="field.type === 'file'"
                    :id="`field-${field.key}`"
                    type="file"
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

                <InputError :message="error(field.key)" />
            </div>

            <!-- Email verification — account-free proof the applicant owns the email. -->
            <div class="flex flex-col gap-3 rounded-lg border border-border bg-card p-4">
                <div class="flex flex-col gap-1">
                    <h2 class="text-sm font-medium text-foreground">
                        Verify your email
                    </h2>
                    <p class="text-13 text-muted-foreground">
                        We'll email a one-time code to
                        <span
                            v-if="applicantEmail"
                            class="font-medium text-foreground"
                            >{{ applicantEmail }}</span
                        ><span v-else>the email you entered above</span> to
                        confirm it's yours before you submit.
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                    <div
                        v-if="codeSent"
                        class="flex flex-1 flex-col gap-2"
                    >
                        <Label for="verification-code" class="text-13">
                            Verification code
                        </Label>
                        <Input
                            id="verification-code"
                            v-model="form.verification_code"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            placeholder="123456"
                        />
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        :disabled="!applicantEmail || verifier.processing"
                        @click="sendCode"
                    >
                        {{ codeSent ? 'Resend code' : 'Send code' }}
                    </Button>
                </div>

                <p v-if="codeSent" class="text-13 text-muted-foreground">
                    Enter the code we emailed you to finish your application.
                </p>

                <InputError :message="form.errors.verification_code" />
            </div>

            <div class="pt-2">
                <Button
                    :disabled="form.processing || !form.verification_code"
                    type="submit"
                >
                    Submit application
                </Button>
            </div>
        </form>
    </div>
</template>
