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

export interface FormSnapshotField {
    key: string;
    type: string;
    label: string;
    required: boolean;
    help: string | null;
    options: string[] | null;
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
    application_link_id: number;
    unit_id: number;
    applicant_first_name: string;
    applicant_last_name: string;
    applicant_email: string;
    applicant_phone: string;
    answers?: Record<string, AnswerValue>;
    form_snapshot?: FormSnapshotField[];
    status: ApplicationStatus;
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
    application_links?: ApplicationLink[];
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

export interface SelectOption {
    value: string;
    label: string;
}

export interface PropertyFormOptions {
    types: SelectOption[];
    rentalTypes: SelectOption[];
    statuses: SelectOption[];
}
