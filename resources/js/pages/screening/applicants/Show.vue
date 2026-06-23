<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { FileText, ShieldAlert } from '@lucide/vue';
import { computed } from 'vue';
import ApplicationController from '@/actions/App/Http/Controllers/ApplicationController';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { applicationStatusBadge } from '@/lib/applicationStatus';
import { index } from '@/routes/properties';
import { index as applicantsIndex } from '@/routes/units/applicants';
import type {
    AnswerValue,
    Application,
    Document,
    FormSnapshotField,
    Property,
    ReferenceAnswer,
    Unit,
} from '@/types/property';

interface StatusOption {
    value: string;
    label: string;
}

const props = defineProps<{
    property: Property;
    unit: Unit;
    application: Application;
    documents: Document[];
    statuses: StatusOption[];
}>();

const reviewForm = useForm({
    status: props.application.status,
    landlord_notes: props.application.landlord_notes ?? '',
});

function saveReview(): void {
    reviewForm.put(ApplicationController.update.url(props.application.id), {
        preserveScroll: true,
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: index() },
            { title: 'Applicants', href: '#' },
        ],
    },
});

const dateFormatter = new Intl.DateTimeFormat('en-CA', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
});

const applicantName = computed(() =>
    `${props.application.applicant_first_name} ${props.application.applicant_last_name}`.trim(),
);

const submittedOn = computed(() =>
    props.application.submitted_at
        ? dateFormatter.format(new Date(props.application.submitted_at))
        : '—',
);

const snapshot = computed<FormSnapshotField[]>(
    () => props.application.form_snapshot ?? [],
);

const answers = computed<Record<string, AnswerValue>>(
    () => props.application.answers ?? {},
);

const status = computed(() => applicationStatusBadge(props.application.status));

/**
 * The documents uploaded for a given file field, matched by `field_key`.
 */
function documentsForField(key: string): Document[] {
    return props.documents.filter((document) => document.field_key === key);
}

function isReference(value: AnswerValue): value is ReferenceAnswer {
    return (
        typeof value === 'object' &&
        value !== null &&
        !Array.isArray(value) &&
        'relationship' in value
    );
}

/**
 * Render a scalar/array answer as readable text. File and reference fields are
 * rendered separately in the template, so they fall through to an em dash here.
 */
function displayAnswer(field: FormSnapshotField): string {
    const value = answers.value[field.key];

    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    if (Array.isArray(value)) {
        return value.length ? value.join(', ') : '—';
    }

    if (typeof value === 'string' || typeof value === 'number') {
        return String(value);
    }

    return '—';
}

function formatSize(bytes: number | null): string {
    if (!bytes) {
        return '';
    }

    const kb = bytes / 1024;

    return kb < 1024
        ? `${Math.round(kb)} KB`
        : `${(kb / 1024).toFixed(1)} MB`;
}
</script>

<template>
    <Head :title="`${applicantName} — ${unit.label}`" />

    <div class="flex h-full flex-1 flex-col p-6 lg:p-10">
        <PageHeader
            eyebrow="Applicant"
            :title="applicantName"
            :back="{
                label: 'Back to applicants',
                href: applicantsIndex(unit.id),
            }"
        >
            <template #actions>
                <StatusBadge :variant="status.variant">
                    {{ status.label }}
                </StatusBadge>
            </template>
        </PageHeader>

        <div class="flex max-w-3xl flex-col gap-6">
            <Alert>
                <ShieldAlert />
                <AlertTitle>Applicant-provided information</AlertTitle>
                <AlertDescription>
                    Everything below was submitted by the applicant and has not
                    been independently verified by dwellow.
                </AlertDescription>
            </Alert>

            <Card>
                <CardHeader>
                    <CardTitle>Review</CardTitle>
                </CardHeader>
                <CardContent>
                    <form
                        class="flex flex-col gap-5"
                        @submit.prevent="saveReview"
                    >
                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <Select v-model="reviewForm.status">
                                <SelectTrigger id="status" class="w-full sm:w-60">
                                    <SelectValue>
                                        {{ applicationStatusBadge(reviewForm.status).label }}
                                    </SelectValue>
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem
                                        v-for="option in statuses"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="reviewForm.errors.status" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="landlord_notes">Private notes</Label>
                            <textarea
                                id="landlord_notes"
                                v-model="reviewForm.landlord_notes"
                                rows="4"
                                placeholder="Notes only you can see…"
                                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                            ></textarea>
                            <InputError :message="reviewForm.errors.landlord_notes" />
                        </div>

                        <div>
                            <Button type="submit" :disabled="reviewForm.processing">
                                Save review
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Contact</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 sm:grid-cols-2">
                    <div class="flex flex-col gap-0.5">
                        <span class="text-13 text-muted-foreground">Email</span>
                        <span class="text-sm text-foreground">
                            {{ application.applicant_email || '—' }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-13 text-muted-foreground">Phone</span>
                        <span class="text-sm text-foreground">
                            {{ application.applicant_phone || '—' }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-13 text-muted-foreground">
                            Submitted
                        </span>
                        <span class="text-sm text-foreground">
                            {{ submittedOn }}
                        </span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Application</CardTitle>
                </CardHeader>
                <CardContent class="flex flex-col gap-5">
                    <div
                        v-for="field in snapshot"
                        :key="field.key"
                        class="flex flex-col gap-1 border-b border-border pb-4 last:border-0 last:pb-0"
                    >
                        <span class="text-13 font-medium text-muted-foreground">
                            {{ field.label }}
                        </span>

                        <!-- Reference block. -->
                        <div
                            v-if="
                                field.type === 'reference' &&
                                isReference(answers[field.key])
                            "
                            class="grid gap-1 text-sm text-foreground sm:grid-cols-2"
                        >
                            <span>
                                Name:
                                {{ (answers[field.key] as ReferenceAnswer).name || '—' }}
                            </span>
                            <span>
                                Relationship:
                                {{ (answers[field.key] as ReferenceAnswer).relationship || '—' }}
                            </span>
                            <span>
                                Email:
                                {{ (answers[field.key] as ReferenceAnswer).email || '—' }}
                            </span>
                            <span>
                                Phone:
                                {{ (answers[field.key] as ReferenceAnswer).phone || '—' }}
                            </span>
                        </div>

                        <!-- Uploaded documents for a file field. -->
                        <ul
                            v-else-if="field.type === 'file'"
                            class="flex flex-col gap-1"
                        >
                            <li
                                v-for="document in documentsForField(field.key)"
                                :key="document.id"
                                class="flex items-center gap-2 text-sm text-foreground"
                            >
                                <FileText
                                    class="size-4 shrink-0 text-muted-foreground"
                                />
                                <span>{{ document.original_name }}</span>
                                <span
                                    v-if="formatSize(document.size)"
                                    class="text-13 text-muted-foreground"
                                >
                                    {{ formatSize(document.size) }}
                                </span>
                            </li>
                            <li
                                v-if="documentsForField(field.key).length === 0"
                                class="text-sm text-muted-foreground"
                            >
                                —
                            </li>
                        </ul>

                        <!-- Scalar / choice / boolean answers. -->
                        <span v-else class="text-sm whitespace-pre-line text-foreground">
                            {{ displayAnswer(field) }}
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
