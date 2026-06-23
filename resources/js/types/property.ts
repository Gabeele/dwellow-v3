export interface Unit {
    id: number;
    property_id: number;
    label: string;
    bedrooms: number | null;
    bathrooms: string | null;
    rent_amount: string | null;
    status: string;
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
