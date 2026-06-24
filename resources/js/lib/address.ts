/**
 * The components of a Canadian street address, in display order. Every field is
 * optional so partial addresses (and the differing payload shapes across pages)
 * can flow through the same formatter. The two street lines accept either the
 * screening payload's `line1`/`line2` or the `Property` model's
 * `address_line1`/`address_line2`. City, region, and postal code collapse onto a
 * single "locality" line.
 */
export interface AddressParts {
    line1?: string | null;
    line2?: string | null;
    address_line1?: string | null;
    address_line2?: string | null;
    city?: string | null;
    region?: string | null;
    postal_code?: string | null;
}

/**
 * Format an address as the lines you'd write on an envelope: street line 1,
 * the optional street line 2, then "City, Region, Postal" as one locality line.
 * Empty parts are dropped, so no stray commas or blank lines appear.
 */
export function formatAddressLines(parts: AddressParts): string[] {
    const line1 = parts.line1 ?? parts.address_line1;
    const line2 = parts.line2 ?? parts.address_line2;
    const localityLine = [parts.city, parts.region, parts.postal_code]
        .filter(Boolean)
        .join(', ');

    return [line1, line2, localityLine].filter(
        (line): line is string => !!line,
    );
}

/**
 * Format an address as a single comma-joined string. Equivalent to joining
 * {@link formatAddressLines} with ", ", so the part ordering lives in one place.
 */
export function formatAddress(parts: AddressParts): string {
    return formatAddressLines(parts).join(', ');
}
