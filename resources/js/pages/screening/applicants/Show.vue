<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Ban,
    CircleCheck,
    CreditCard,
    FileText,
    ScrollText,
    ShieldAlert,
    ShieldCheck,
    Sparkles,
    ThumbsUp,
    Trash2,
    TrendingDown,
    TrendingUp,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ApplicationController from '@/actions/App/Http/Controllers/ApplicationController';
import DocumentController from '@/actions/App/Http/Controllers/DocumentController';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import InputError from '@/components/InputError.vue';
import PageHeader from '@/components/PageHeader.vue';
import ScoreGauge from '@/components/ScoreGauge.vue';
import StatusBadge from '@/components/StatusBadge.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { applicationStatusBadge } from '@/lib/applicationStatus';
import { formatCurrency } from '@/lib/currency';
import { index } from '@/routes/properties';
import { index as applicantsIndex } from '@/routes/units/applicants';
import type {
    AnswerValue,
    Application,
    Document,
    FormSnapshotField,
    Property,
    ReferenceAnswer,
    Score,
    ScoreStatus,
    StatusOption,
    Unit,
} from '@/types/property';

const props = defineProps<{
    property: Property;
    unit: Unit;
    application: Application;
    documents: Document[];
    statuses: StatusOption[];
    otherActiveCount: number;
    // The score agent's run status (null until an agent has run) and the Score
    // payload (null until the run completes) drive the three-state Score panel.
    scoreStatus: ScoreStatus | null;
    score: Score | null;
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

// Decision dialogs — approve / decline / delete each confirm before acting.
const showApprove = ref(false);
const showDecline = ref(false);
const showDelete = ref(false);

// Approval carries three opt-out toggles: email the applicant, decline the
// other applicants still in the running for this unit, and email those too.
const approveForm = useForm({
    notify_applicant: true,
    decline_others: props.otherActiveCount > 0,
    notify_declined: true,
});

const declineForm = useForm({
    notify_applicant: true,
});

function openApprove(): void {
    approveForm.notify_applicant = true;
    approveForm.decline_others = props.otherActiveCount > 0;
    approveForm.notify_declined = true;
    showApprove.value = true;
}

function approve(): void {
    approveForm.post(ApplicationController.approve.url(props.application.id), {
        preserveScroll: true,
        onSuccess: () => {
            showApprove.value = false;
        },
    });
}

function openDecline(): void {
    declineForm.notify_applicant = true;
    showDecline.value = true;
}

function decline(): void {
    declineForm.post(ApplicationController.reject.url(props.application.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDecline.value = false;
        },
    });
}

function destroyApplication(): void {
    router.delete(ApplicationController.destroy.url(props.application.id), {
        onSuccess: () => {
            showDelete.value = false;
        },
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

const initials = computed(() =>
    [
        props.application.applicant_first_name,
        props.application.applicant_last_name,
    ]
        .map((part) => part.trim()[0] ?? '')
        .join('')
        .toUpperCase(),
);

const status = computed(() => applicationStatusBadge(props.application.status));

/**
 * The Score panel's state, derived from the agent run status and whether a
 * Score payload has been written. `scored` only once the run completes and a
 * Score exists; `failed` when the run failed (a retry will mutate the same
 * agent); `processing` while pending/processing; `idle` before any run.
 */
const scoreState = computed<'idle' | 'processing' | 'scored' | 'failed'>(() => {
    if (props.score !== null) {
        return 'scored';
    }

    if (props.scoreStatus === 'failed') {
        return 'failed';
    }

    if (
        props.scoreStatus === 'pending' ||
        props.scoreStatus === 'processing'
    ) {
        return 'processing';
    }

    return 'idle';
});

/** Tone for the fit-score badge, mirroring the gauge thresholds. */
const fitTone = computed<'success' | 'warning' | 'danger'>(() => {
    const value = props.score?.fit_score ?? 0;

    return value >= 70 ? 'success' : value >= 55 ? 'warning' : 'danger';
});

const submittedOn = computed(() =>
    props.application.submitted_at
        ? dateFormatter.format(new Date(props.application.submitted_at))
        : '—',
);

const statusChangedOn = computed(() =>
    props.application.status_changed_at
        ? dateFormatter.format(new Date(props.application.status_changed_at))
        : null,
);

const snapshot = computed<FormSnapshotField[]>(
    () => props.application.form_snapshot ?? [],
);

const answers = computed<Record<string, AnswerValue>>(
    () => props.application.answers ?? {},
);

/** A single answer as trimmed text, or null when blank/non-scalar. */
function answerText(key: string): string | null {
    const value = answers.value[key];

    if (typeof value === 'string') {
        return value.trim() === '' ? null : value.trim();
    }

    if (typeof value === 'number') {
        return String(value);
    }

    return null;
}

/** Parse a numeric answer (income, rent), tolerating "$" and commas. */
function answerNumber(key: string): number | null {
    const text = answerText(key);

    if (text === null) {
        return null;
    }

    const parsed = Number.parseFloat(text.replace(/[^0-9.]/g, ''));

    return Number.isFinite(parsed) ? parsed : null;
}

/** Format a date-only answer ("2026-08-01") without a timezone shift. */
function formatDateOnly(value: string | null): string | null {
    if (!value) {
        return null;
    }

    const [year, month, day] = value.split('-').map(Number);

    if (!year || !month || !day) {
        return null;
    }

    return dateFormatter.format(new Date(year, month - 1, day));
}

const rentAmount = computed<number | null>(() => {
    const parsed = props.unit.rent_amount
        ? Number.parseFloat(props.unit.rent_amount)
        : NaN;

    return Number.isFinite(parsed) ? parsed : null;
});

const applyingForLine = computed<string>(() => {
    const locality = [props.property.city, props.property.region]
        .filter(Boolean)
        .join(', ');

    return [
        props.unit.label,
        locality,
        rentAmount.value !== null
            ? `${formatCurrency(rentAmount.value)}/mo`
            : null,
    ]
        .filter(Boolean)
        .join(' · ');
});

const statedIncome = computed<number | null>(() =>
    answerNumber('gross_monthly_income'),
);

type Tone = 'success' | 'warning' | 'danger' | 'neutral';

/** Income-to-rent computed from the applicant's *stated* income and the unit rent. */
const incomeToRent = computed<{
    value: string;
    caption: string;
    tag: string;
    tone: Tone;
} | null>(() => {
    if (statedIncome.value === null || !rentAmount.value) {
        return null;
    }

    const ratio = statedIncome.value / rentAmount.value;
    const tone: Tone =
        ratio >= 3 ? 'success' : ratio >= 2 ? 'warning' : 'danger';
    const tag = ratio >= 3 ? 'Strong' : ratio >= 2 ? 'Okay' : 'Low';

    return {
        value: `${ratio.toFixed(1)}×`,
        caption: `${formatCurrency(statedIncome.value)}/mo stated income`,
        tag,
        tone,
    };
});

const creditRange = computed<string | null>(() =>
    answerText('credit_score_range'),
);

/** The leading word of the self-reported credit bracket, e.g. "Good (700–749)" → "Good". */
const creditShort = computed<string | null>(() => {
    if (!creditRange.value) {
        return null;
    }

    return creditRange.value.split(/[\s(]/)[0] || creditRange.value;
});

const moveIn = computed<string | null>(() =>
    formatDateOnly(answerText('desired_move_in_date')),
);

const occupants = computed<string | null>(() =>
    answerText('number_of_occupants'),
);

const employer = computed<string | null>(() => answerText('employer_name'));

const hasPhotoId = computed<boolean>(() =>
    props.documents.some((document) => document.field_key === 'photo_id'),
);

/** A compact activity timeline built from the timestamps we actually record. */
const timeline = computed(() => {
    const events: { title: string; meta: string; current?: boolean }[] = [];

    if (props.application.submitted_at) {
        events.push({
            title: 'Application submitted',
            meta: submittedOn.value,
        });
    }

    if (props.documents.length > 0) {
        events.push({
            title: `Documents uploaded (${props.documents.length})`,
            meta: submittedOn.value,
        });
    }

    if (statusChangedOn.value) {
        events.push({
            title: `Marked ${status.value.label.toLowerCase()}`,
            meta: statusChangedOn.value,
            current: true,
        });
    }

    return events;
});

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

    return kb < 1024 ? `${Math.round(kb)} KB` : `${(kb / 1024).toFixed(1)} MB`;
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
                <Button
                    variant="ghost"
                    size="sm"
                    class="text-destructive hover:text-destructive"
                    @click="showDelete = true"
                >
                    <Trash2 class="size-4" />
                    Delete
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Main column -->
            <div class="flex flex-col gap-6 lg:col-span-2">
                <!-- Hero: score (pending) + applicant identity + at-a-glance stats. -->
                <Card>
                    <CardContent
                        class="flex flex-col gap-6 sm:flex-row sm:items-center"
                    >
                        <!-- Fit score gauge — the AI Score's headline number. -->
                        <div class="flex shrink-0 flex-col items-center gap-1">
                            <ScoreGauge
                                v-if="scoreState === 'scored' && score?.fit_score !== null"
                                :score="score!.fit_score!"
                            />
                            <Skeleton
                                v-else-if="scoreState === 'processing'"
                                class="size-30 rounded-full"
                            />
                            <div
                                v-else
                                class="flex size-30 flex-col items-center justify-center rounded-full border-2 border-dashed border-border text-muted-foreground"
                            >
                                <span class="text-2xl font-semibold">—</span>
                            </div>
                            <span
                                class="text-[10px] font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Fit score
                            </span>
                        </div>

                        <div class="flex flex-1 flex-col gap-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2
                                    class="text-xl font-semibold text-foreground"
                                >
                                    {{ applicantName }}
                                </h2>
                                <StatusBadge :variant="status.variant">
                                    {{ status.label }}
                                </StatusBadge>
                            </div>
                            <p class="text-sm text-muted-foreground">
                                Applying for {{ applyingForLine }}
                            </p>

                            <div
                                class="mt-1 grid grid-cols-2 gap-4 border-t border-border pt-4 sm:grid-cols-3"
                            >
                                <div class="flex flex-col gap-0.5">
                                    <span
                                        class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                                    >
                                        Move-in
                                    </span>
                                    <span
                                        class="text-sm font-medium text-foreground"
                                    >
                                        {{ moveIn ?? '—' }}
                                    </span>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span
                                        class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                                    >
                                        Documents
                                    </span>
                                    <span
                                        class="text-sm font-medium text-foreground"
                                    >
                                        {{ documents.length }} uploaded
                                    </span>
                                </div>
                                <div class="flex flex-col gap-0.5">
                                    <span
                                        class="text-[11px] font-medium tracking-wide text-muted-foreground uppercase"
                                    >
                                        Occupants
                                    </span>
                                    <span
                                        class="text-sm font-medium text-foreground"
                                    >
                                        {{ occupants ?? '—' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Dwellow AI Score — the screening aid. Three live states:
                     processing (agent running), scored (Score present), failed
                     (run failed, a retry will mutate the same agent). -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <span
                                class="flex size-7 shrink-0 items-center justify-center rounded-full bg-ai-tint text-ai-tint-foreground"
                            >
                                <Sparkles class="size-4" />
                            </span>
                            Dwellow Score
                            <Badge
                                v-if="scoreState === 'scored'"
                                :variant="fitTone"
                                class="ml-auto"
                            >
                                {{ score!.fit_score }}/100 fit
                            </Badge>
                            <Badge
                                v-else-if="scoreState === 'processing'"
                                variant="ai"
                                class="ml-auto"
                            >
                                Scoring…
                            </Badge>
                            <Badge
                                v-else-if="scoreState === 'failed'"
                                variant="warning"
                                class="ml-auto"
                            >
                                Unavailable
                            </Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-5">
                        <!-- Processing: the agent is running; show a skeleton. -->
                        <div
                            v-if="scoreState === 'processing'"
                            class="flex flex-col gap-3"
                        >
                            <p class="text-sm text-muted-foreground">
                                Dwellow is reviewing this application against
                                permissible screening factors. This usually
                                takes a moment.
                            </p>
                            <Skeleton class="h-4 w-3/4" />
                            <Skeleton class="h-4 w-full" />
                            <Skeleton class="h-4 w-5/6" />
                        </div>

                        <!-- Failed: the run failed; a retry mutates the same agent. -->
                        <p
                            v-else-if="scoreState === 'failed'"
                            class="text-sm text-muted-foreground"
                        >
                            The Score couldn't be generated for this
                            application. dwellow will retry automatically — check
                            back shortly.
                        </p>

                        <!-- Idle: no agent has run yet for this application. -->
                        <p
                            v-else-if="scoreState === 'idle'"
                            class="text-sm text-muted-foreground"
                        >
                            No Score has been generated for this application
                            yet.
                        </p>

                        <!-- Scored: the real result. -->
                        <template v-else>
                            <p
                                v-if="score!.score_rationale"
                                class="text-sm font-medium text-foreground"
                            >
                                {{ score!.score_rationale }}
                            </p>
                            <p
                                v-if="score!.summary"
                                class="text-sm whitespace-pre-line text-muted-foreground"
                            >
                                {{ score!.summary }}
                            </p>

                            <!-- Flags — permissible concerns, emphasised. -->
                            <div
                                v-if="score!.red_flags.length"
                                class="flex flex-col gap-2"
                            >
                                <span
                                    class="text-13 font-semibold tracking-wide text-foreground uppercase"
                                >
                                    Flags
                                </span>
                                <ul class="flex flex-col gap-2">
                                    <li
                                        v-for="(flag, idx) in score!.red_flags"
                                        :key="`flag-${idx}`"
                                        class="flex items-start gap-2 rounded-md bg-warning-tint/40 px-3 py-2 text-sm text-foreground"
                                    >
                                        <TrendingDown
                                            class="mt-0.5 size-4 shrink-0 text-warning"
                                        />
                                        <span>{{ flag }}</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Strengths. -->
                            <div
                                v-if="score!.strengths.length"
                                class="flex flex-col gap-2"
                            >
                                <span
                                    class="text-13 font-semibold tracking-wide text-foreground uppercase"
                                >
                                    Strengths
                                </span>
                                <ul class="flex flex-col gap-2">
                                    <li
                                        v-for="(
                                            strength, idx
                                        ) in score!.strengths"
                                        :key="`strength-${idx}`"
                                        class="flex items-start gap-2 text-sm text-foreground"
                                    >
                                        <ThumbsUp
                                            class="mt-0.5 size-4 shrink-0 text-success"
                                        />
                                        <span>{{ strength }}</span>
                                    </li>
                                </ul>
                            </div>
                        </template>

                        <!-- Always-on framing: the Score is a screening aid. -->
                        <p
                            class="border-t border-border pt-3 text-13 text-muted-foreground"
                        >
                            The Dwellow Score is a screening aid based on
                            self-reported, unverified information — it never
                            decides for you.
                        </p>
                    </CardContent>
                </Card>

                <!-- At-a-glance metrics, drawn from the applicant's own answers. -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <!-- Income-to-rent (computed from stated income ÷ unit rent). -->
                    <Card>
                        <CardContent class="flex flex-col gap-2">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span
                                    class="flex items-center gap-1.5 text-sm font-medium text-foreground"
                                >
                                    <TrendingUp
                                        class="size-4 text-muted-foreground"
                                    />
                                    Income-to-rent
                                </span>
                                <Badge
                                    v-if="incomeToRent"
                                    :variant="incomeToRent.tone"
                                >
                                    {{ incomeToRent.tag }}
                                </Badge>
                                <Badge v-else variant="neutral">No data</Badge>
                            </div>
                            <span
                                class="text-2xl font-semibold text-foreground"
                            >
                                {{ incomeToRent?.value ?? '—' }}
                            </span>
                            <span class="text-13 text-muted-foreground">
                                {{
                                    incomeToRent?.caption ??
                                    'Stated income or unit rent missing'
                                }}
                            </span>
                        </CardContent>
                    </Card>

                    <!-- Credit score (self-reported bracket). -->
                    <Card>
                        <CardContent class="flex flex-col gap-2">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span
                                    class="flex items-center gap-1.5 text-sm font-medium text-foreground"
                                >
                                    <CreditCard
                                        class="size-4 text-muted-foreground"
                                    />
                                    Credit score
                                </span>
                                <Badge variant="neutral">Self-reported</Badge>
                            </div>
                            <span
                                class="text-2xl font-semibold text-foreground"
                            >
                                {{ creditShort ?? '—' }}
                            </span>
                            <span class="text-13 text-muted-foreground">
                                {{ creditRange ?? 'Not provided' }}
                            </span>
                        </CardContent>
                    </Card>

                    <!-- Employment (self-reported). -->
                    <Card>
                        <CardContent class="flex flex-col gap-2">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span
                                    class="flex items-center gap-1.5 text-sm font-medium text-foreground"
                                >
                                    <ScrollText
                                        class="size-4 text-muted-foreground"
                                    />
                                    Employment
                                </span>
                                <Badge variant="neutral">Self-reported</Badge>
                            </div>
                            <span
                                class="text-2xl font-semibold text-foreground"
                            >
                                {{ answerText('employment_type') ?? '—' }}
                            </span>
                            <span class="text-13 text-muted-foreground">
                                {{
                                    employer
                                        ? employer
                                        : 'Employer not provided'
                                }}
                            </span>
                        </CardContent>
                    </Card>

                    <!-- Identity & documents — uploaded, not yet verified. -->
                    <Card>
                        <CardContent class="flex flex-col gap-2">
                            <div
                                class="flex items-center justify-between gap-2"
                            >
                                <span
                                    class="flex items-center gap-1.5 text-sm font-medium text-foreground"
                                >
                                    <ShieldCheck
                                        class="size-4 text-muted-foreground"
                                    />
                                    Identity
                                </span>
                                <Badge
                                    :variant="
                                        hasPhotoId ? 'success' : 'neutral'
                                    "
                                >
                                    {{ hasPhotoId ? 'ID on file' : 'No ID' }}
                                </Badge>
                            </div>
                            <span
                                class="text-2xl font-semibold text-foreground"
                            >
                                {{ hasPhotoId ? 'Uploaded' : '—' }}
                            </span>
                            <span class="text-13 text-muted-foreground">
                                {{
                                    hasPhotoId
                                        ? 'Government ID attached — not yet verified'
                                        : 'No government ID attached'
                                }}
                            </span>
                        </CardContent>
                    </Card>
                </div>

                <!-- Full submitted application (the snapshot taken at submit time). -->
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
                            <span
                                class="text-13 font-medium text-muted-foreground"
                            >
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
                                    {{
                                        (answers[field.key] as ReferenceAnswer)
                                            .name || '—'
                                    }}
                                </span>
                                <span>
                                    Relationship:
                                    {{
                                        (answers[field.key] as ReferenceAnswer)
                                            .relationship || '—'
                                    }}
                                </span>
                                <span>
                                    Email:
                                    {{
                                        (answers[field.key] as ReferenceAnswer)
                                            .email || '—'
                                    }}
                                </span>
                                <span>
                                    Phone:
                                    {{
                                        (answers[field.key] as ReferenceAnswer)
                                            .phone || '—'
                                    }}
                                </span>
                            </div>

                            <!-- Uploaded documents for a file field. -->
                            <ul
                                v-else-if="field.type === 'file'"
                                class="flex flex-col gap-1"
                            >
                                <li
                                    v-for="document in documentsForField(
                                        field.key,
                                    )"
                                    :key="document.id"
                                    class="flex items-center gap-2 text-sm text-foreground"
                                >
                                    <FileText
                                        class="size-4 shrink-0 text-muted-foreground"
                                    />
                                    <a
                                        :href="
                                            DocumentController.download.url(
                                                document,
                                            )
                                        "
                                        class="font-medium text-primary underline-offset-4 hover:underline"
                                    >
                                        {{ document.original_name }}
                                    </a>
                                    <span
                                        v-if="formatSize(document.size)"
                                        class="text-13 text-muted-foreground"
                                    >
                                        {{ formatSize(document.size) }}
                                    </span>
                                </li>
                                <li
                                    v-if="
                                        documentsForField(field.key).length ===
                                        0
                                    "
                                    class="text-sm text-muted-foreground"
                                >
                                    —
                                </li>
                            </ul>

                            <!-- Scalar / choice / boolean answers. -->
                            <span
                                v-else
                                class="text-sm whitespace-pre-line text-foreground"
                            >
                                {{ displayAnswer(field) }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Right rail -->
            <div class="flex flex-col gap-6">
                <!-- Your decision: the landlord's real review workflow. -->
                <Card>
                    <CardHeader>
                        <CardTitle>Your decision</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-5">
                        <!-- Primary actions: approve / decline, each confirmed. -->
                        <div class="grid grid-cols-2 gap-2">
                            <Button type="button" @click="openApprove">
                                <CircleCheck class="size-4" />
                                Approve
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                @click="openDecline"
                            >
                                <Ban class="size-4" />
                                Decline
                            </Button>
                        </div>

                        <div
                            class="flex items-center gap-3 text-xs text-muted-foreground"
                        >
                            <span class="h-px flex-1 bg-border" />
                            or update status
                            <span class="h-px flex-1 bg-border" />
                        </div>

                        <form
                            class="flex flex-col gap-5"
                            @submit.prevent="saveReview"
                        >
                            <div class="grid gap-2">
                                <Label for="status">Status</Label>
                                <Select v-model="reviewForm.status">
                                    <SelectTrigger id="status" class="w-full">
                                        <SelectValue>
                                            {{
                                                applicationStatusBadge(
                                                    reviewForm.status,
                                                ).label
                                            }}
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
                                <InputError
                                    :message="reviewForm.errors.status"
                                />
                            </div>

                            <div class="grid gap-2">
                                <Label for="landlord_notes"
                                    >Private notes</Label
                                >
                                <textarea
                                    id="landlord_notes"
                                    v-model="reviewForm.landlord_notes"
                                    rows="4"
                                    placeholder="Notes only you can see…"
                                    class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors placeholder:text-muted-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none"
                                ></textarea>
                                <InputError
                                    :message="reviewForm.errors.landlord_notes"
                                />
                            </div>

                            <Button
                                type="submit"
                                :disabled="reviewForm.processing"
                            >
                                Save review
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <!-- Contact. -->
                <Card>
                    <CardHeader>
                        <CardTitle>Contact</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-4">
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted text-13 font-semibold text-foreground"
                            >
                                {{ initials || '—' }}
                            </span>
                            <div class="flex min-w-0 flex-col">
                                <span
                                    class="truncate text-sm font-medium text-foreground"
                                >
                                    {{ application.applicant_email || '—' }}
                                </span>
                                <span class="text-13 text-muted-foreground">
                                    {{
                                        application.applicant_phone ||
                                        'No phone'
                                    }}
                                </span>
                            </div>
                        </div>
                        <div
                            class="flex items-center justify-between border-t border-border pt-3 text-13"
                        >
                            <span class="text-muted-foreground">Reference</span>
                            <span class="font-mono text-foreground">
                                {{ application.public_id }}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <!-- Submitted documents. -->
                <Card>
                    <CardHeader>
                        <CardTitle>Submitted documents</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col gap-3">
                        <div
                            v-for="document in documents"
                            :key="document.id"
                            class="flex items-center gap-3"
                        >
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-md bg-muted text-muted-foreground"
                            >
                                <FileText class="size-4" />
                            </span>
                            <div class="flex min-w-0 flex-1 flex-col">
                                <a
                                    :href="
                                        DocumentController.download.url(
                                            document,
                                        )
                                    "
                                    class="truncate text-sm font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    {{ document.original_name }}
                                </a>
                                <span class="text-13 text-muted-foreground">
                                    {{ formatSize(document.size) || 'File' }}
                                </span>
                            </div>
                            <Badge variant="neutral">Uploaded</Badge>
                        </div>
                        <p
                            v-if="documents.length === 0"
                            class="text-sm text-muted-foreground"
                        >
                            No documents were uploaded with this application.
                        </p>
                    </CardContent>
                </Card>

                <!-- Activity timeline (from the timestamps we record). -->
                <Card>
                    <CardHeader>
                        <CardTitle>Activity</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ol class="flex flex-col gap-4">
                            <li
                                v-for="(event, idx) in timeline"
                                :key="idx"
                                class="flex gap-3"
                            >
                                <span
                                    class="mt-1 size-2 shrink-0 rounded-full"
                                    :class="
                                        event.current
                                            ? 'bg-primary'
                                            : 'bg-muted-foreground/40'
                                    "
                                ></span>
                                <div class="flex flex-col">
                                    <span class="text-sm text-foreground">
                                        {{ event.title }}
                                    </span>
                                    <span class="text-13 text-muted-foreground">
                                        {{ event.meta }}
                                    </span>
                                </div>
                            </li>
                            <li
                                v-if="timeline.length === 0"
                                class="text-sm text-muted-foreground"
                            >
                                Nothing recorded yet.
                            </li>
                        </ol>
                    </CardContent>
                </Card>

                <!-- Honest disclaimer: nothing here has been independently verified. -->
                <Alert>
                    <ShieldAlert />
                    <AlertTitle>Applicant-provided information</AlertTitle>
                    <AlertDescription>
                        Everything shown was submitted by the applicant and has
                        not been independently verified by dwellow.
                    </AlertDescription>
                </Alert>
            </div>
        </div>

        <!-- Approve: optional applicant email + auto-decline of the others. -->
        <ConfirmDialog
            v-model:open="showApprove"
            title="Approve this applicant?"
            :description="`${applicantName || 'This applicant'} will be marked approved.`"
            confirm-label="Approve"
            :processing="approveForm.processing"
            @confirm="approve"
        >
            <label class="flex items-start gap-2.5 text-sm text-foreground">
                <Checkbox
                    v-model="approveForm.notify_applicant"
                    class="mt-0.5"
                />
                <span>
                    Email
                    {{ application.applicant_first_name || 'the applicant' }}
                    an approval notice
                </span>
            </label>

            <template v-if="otherActiveCount > 0">
                <label class="flex items-start gap-2.5 text-sm text-foreground">
                    <Checkbox
                        v-model="approveForm.decline_others"
                        class="mt-0.5"
                    />
                    <span>
                        Decline the {{ otherActiveCount }} other
                        {{
                            otherActiveCount === 1 ? 'applicant' : 'applicants'
                        }}
                        still in the running for this unit
                    </span>
                </label>

                <label
                    v-if="approveForm.decline_others"
                    class="ml-6 flex items-start gap-2.5 text-sm text-foreground"
                >
                    <Checkbox
                        v-model="approveForm.notify_declined"
                        class="mt-0.5"
                    />
                    <span>Email those applicants a decline notice</span>
                </label>
            </template>
        </ConfirmDialog>

        <!-- Decline: confirm, with an optional email to the applicant. -->
        <ConfirmDialog
            v-model:open="showDecline"
            title="Decline this applicant?"
            :description="`Are you sure you want to decline ${applicantName || 'this applicant'}?`"
            confirm-label="Decline"
            destructive
            :processing="declineForm.processing"
            @confirm="decline"
        >
            <label class="flex items-start gap-2.5 text-sm text-foreground">
                <Checkbox
                    v-model="declineForm.notify_applicant"
                    class="mt-0.5"
                />
                <span>
                    Email
                    {{ application.applicant_first_name || 'the applicant' }}
                    a decline notice
                </span>
            </label>
        </ConfirmDialog>

        <!-- Delete: irreversible; removes the application and its documents. -->
        <ConfirmDialog
            v-model:open="showDelete"
            title="Delete application?"
            :description="`Delete ${applicantName || 'this'} application? Its uploaded documents will be removed too.`"
            confirm-label="Delete"
            destructive
            @confirm="destroyApplication"
        />
    </div>
</template>
