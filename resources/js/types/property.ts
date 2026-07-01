export interface ApplicationLink {
    id: number;
    unit_id: number;
    token: string;
    label: string | null;
    is_accepting: boolean;
    expires_at: string | null;
    revoked_at: string | null;
    applications_count?: number;
    public_url: string;
    created_at: string;
    updated_at: string;
}

export type ApplicationStatus = 'new' | 'reviewing' | 'approved' | 'rejected';

/**
 * One entry on an application's activity timeline: what happened, who caused it
 * (`causer` is null for system/AI events, flagged by `is_system`), and when.
 */
export interface Activity {
    id: number;
    type: string;
    description: string;
    is_system: boolean;
    causer: string | null;
    created_at: string | null;
}

export interface FormField {
    key: string;
    type: string;
    label: string;
    required: boolean;
    help: string | null;
    options: string[] | null;
}

/** The same field shape, as captured in an application's immutable snapshot. */
export type FormSnapshotField = FormField;

export interface FormSection {
    key: string;
    label: string;
    description: string;
    fields: FormField[];
}

/** A section in the form builder, which can toggle and lock sections. */
export interface EditableFormSection extends FormSection {
    locked: boolean;
    enabled: boolean;
}

export interface UnitAddress {
    line1: string;
    line2: string | null;
    city: string;
    region: string;
    postal_code: string;
    country: string;
}

/** The public-facing unit shape shown on the applicant flow. */
export interface PublicUnit {
    label: string;
    address: UnitAddress;
}

export interface ReferenceAnswer {
    name: string;
    email: string;
    phone: string;
    relationship: string;
}

export type AnswerValue =
    | string
    | number
    | boolean
    | string[]
    | ReferenceAnswer
    | null;

export interface Document {
    id: number;
    application_id: number;
    field_key: string;
    disk: string;
    path: string;
    original_name: string;
    mime_type: string | null;
    size: number | null;
    created_at: string;
    updated_at: string;
}

export interface Application {
    id: number;
    public_id: string;
    application_link_id: number;
    unit_id: number;
    applicant_first_name: string;
    applicant_last_name: string;
    applicant_email: string;
    applicant_phone: string;
    answers?: Record<string, AnswerValue>;
    form_snapshot?: FormSnapshotField[];
    status: ApplicationStatus;
    status_changed_at: string | null;
    landlord_notes: string | null;
    documents_count?: number;
    submitted_at: string | null;
    created_at: string;
    updated_at: string;
}

/** The status of the agent run that produces an application's Score. */
export type ScoreStatus = 'pending' | 'processing' | 'completed' | 'failed';

/**
 * A single row in the dashboard "Agents" activity table. Each describes one
 * polymorphic agent run scoped to the landlord's subjects: the type and
 * subject label identify it, the status drives the badge, the timestamps feed
 * the elapsed timer, and `url` links through to the subject's detail page.
 */
export interface AgentActivity {
    id: number;
    type: string;
    type_label: string;
    /** The kind of subject the agent ran against, e.g. "Application". */
    subject_type_label: string | null;
    /** The subject application's own workflow status (new/reviewing/…). */
    subject_status: ApplicationStatus | null;
    subject_status_label: string | null;
    /** When the subject application was submitted. */
    subject_submitted_at: string | null;
    /** The fit score the run produced, or null until it completes. */
    fit_score: number | null;
    status: ScoreStatus;
    status_label: string;
    subject_label: string | null;
    url: string | null;
    created_at: string | null;
    started_at: string | null;
    completed_at: string | null;
}

/** The verdict a single scoring-framework criterion receives. */
export type CriterionAssessment = 'strong' | 'adequate' | 'weak' | 'unverified';

/**
 * One row of the scoring rubric: a fixed framework criterion (e.g. `affordability`),
 * its verdict, and a very short note giving the concrete reason. Every Score grades
 * the same criteria in the same order, so rubrics are comparable across applications.
 */
export interface RubricRow {
    criterion: string;
    assessment: CriterionAssessment;
    note: string;
}

/**
 * The AI-produced Score for an application. Present only once the score agent
 * completes; the holistic `fit_score` (0–100) is accompanied by a one-sentence
 * rationale, an analytical summary, the `rubric` (the fixed framework graded,
 * showing where the number comes from), permissible-only Flags, and strengths.
 */
export interface Score {
    fit_score: number | null;
    score_rationale: string | null;
    summary: string | null;
    rubric: RubricRow[];
    red_flags: string[];
    strengths: string[];
}

export interface Unit {
    id: number;
    property_id: number;
    label: string;
    bedrooms: number | null;
    bathrooms: string | null;
    rent_amount: string | null;
    status: string;
    applications_count?: number;
    /** Every unit has exactly one shareable application link. */
    application_link?: ApplicationLink | null;
    created_at: string;
    updated_at: string;
}

export interface Property {
    id: number;
    landlord_id: number;
    name: string | null;
    address_line1: string;
    address_line2: string | null;
    city: string;
    region: string;
    postal_code: string;
    country: string;
    type: string;
    rental_type: string;
    bedrooms: number | null;
    bathrooms: string | null;
    rent_amount: string | null;
    status: string;
    units_count?: number;
    occupied_units_count?: number;
    available_units_count?: number;
    units?: Unit[];
    created_at: string;
    updated_at: string;
}

/** The compact Score shown on an application row's hoverable fit-score badge. */
export interface ApplicationRowScore {
    fit_score: number | null;
    score_rationale: string | null;
    rubric: RubricRow[];
}

export interface ApplicationRow {
    id: number;
    applicant_name: string;
    applicant_email: string;
    /** Only present on the portfolio-wide list, which spans properties. */
    property_name?: string;
    unit_label: string;
    submitted_at: string | null;
    status: ApplicationStatus;
    documents_count: number;
    /** The AI fit Score, or null until the scoring agent has produced one. */
    score: ApplicationRowScore | null;
    url: string;
}

export interface StatusOption {
    value: ApplicationStatus;
    label: string;
}

export interface PropertyOption {
    id: number;
    name: string;
}

export interface SelectOption {
    value: string;
    label: string;
}

export interface PropertyFormOptions {
    types: SelectOption[];
    rentalTypes: SelectOption[];
    statuses: SelectOption[];
}
