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
