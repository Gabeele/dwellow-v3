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
