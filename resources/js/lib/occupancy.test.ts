import { describe, expect, it } from 'vitest';
import {
    availableSpaces,
    occupancyBadge,
    occupiedSpaces,
    propertyOccupancy,
    rentRoll,
    spaceCount,
} from '@/lib/occupancy';
import type { Property, Unit } from '@/types/property';

/**
 * Build a Property fixture. Defaults describe an occupied whole rental; pass
 * overrides for the fields a given test cares about.
 */
function makeProperty(overrides: Partial<Property> = {}): Property {
    return {
        id: 1,
        landlord_id: 1,
        name: 'Test Property',
        address_line1: '1 Test Street',
        address_line2: null,
        city: 'Testville',
        region: 'ON',
        postal_code: 'A1A1A1',
        country: 'CA',
        type: 'house',
        rental_type: 'whole',
        bedrooms: 3,
        bathrooms: '2',
        rent_amount: '1500',
        status: 'occupied',
        created_at: '2026-01-01T00:00:00Z',
        updated_at: '2026-01-01T00:00:00Z',
        ...overrides,
    };
}

/**
 * Build a Unit fixture for a property's `units` relation.
 */
function makeUnit(overrides: Partial<Unit> = {}): Unit {
    return {
        id: 1,
        property_id: 1,
        label: 'Unit 1',
        bedrooms: 2,
        bathrooms: '1',
        rent_amount: '1000',
        status: 'occupied',
        created_at: '2026-01-01T00:00:00Z',
        updated_at: '2026-01-01T00:00:00Z',
        ...overrides,
    };
}

describe('occupancyBadge', () => {
    it('maps each known status to its own variant and label', () => {
        expect(occupancyBadge('occupied')).toEqual({
            variant: 'success',
            label: 'Occupied',
        });
        expect(occupancyBadge('available')).toEqual({
            variant: 'warning',
            label: 'Available',
        });
        expect(occupancyBadge('unavailable')).toEqual({
            variant: 'neutral',
            label: 'Unavailable',
        });
    });

    it('falls back to the neutral unavailable badge for unknown values', () => {
        // Unknown server values must never crash a badge render.
        expect(occupancyBadge('garbage')).toEqual({
            variant: 'neutral',
            label: 'Unavailable',
        });
    });
});

describe('propertyOccupancy', () => {
    it('mirrors the property status for a whole rental', () => {
        expect(propertyOccupancy(makeProperty({ status: 'occupied' }))).toBe(
            'occupied',
        );
        expect(propertyOccupancy(makeProperty({ status: 'available' }))).toBe(
            'available',
        );
    });

    it('treats an unrecognized whole-rental status as unavailable', () => {
        expect(propertyOccupancy(makeProperty({ status: 'mystery' }))).toBe(
            'unavailable',
        );
    });

    it('reports a multi-unit property with no units as unavailable', () => {
        // A property with nothing to rent cannot be occupied or available.
        expect(
            propertyOccupancy(
                makeProperty({ rental_type: 'multi_unit', units_count: 0 }),
            ),
        ).toBe('unavailable');
    });

    it('reports a multi-unit property as available while any unit is vacant', () => {
        expect(
            propertyOccupancy(
                makeProperty({
                    rental_type: 'multi_unit',
                    units_count: 3,
                    available_units_count: 1,
                }),
            ),
        ).toBe('available');
    });

    it('reports a multi-unit property as occupied only when fully let', () => {
        expect(
            propertyOccupancy(
                makeProperty({
                    rental_type: 'multi_unit',
                    units_count: 3,
                    available_units_count: 0,
                }),
            ),
        ).toBe('occupied');
    });
});

describe('space counts', () => {
    it('treats a whole rental as a single space', () => {
        const whole = makeProperty({ status: 'occupied' });

        expect(spaceCount(whole)).toBe(1);
        expect(occupiedSpaces(whole)).toBe(1);
        expect(availableSpaces(whole)).toBe(0);
    });

    it('treats a vacant whole rental as one available space', () => {
        const whole = makeProperty({ status: 'available' });

        expect(occupiedSpaces(whole)).toBe(0);
        expect(availableSpaces(whole)).toBe(1);
    });

    it('counts one space per unit for a multi-unit rental', () => {
        const multi = makeProperty({
            rental_type: 'multi_unit',
            units_count: 4,
            occupied_units_count: 3,
            available_units_count: 1,
        });

        expect(spaceCount(multi)).toBe(4);
        expect(occupiedSpaces(multi)).toBe(3);
        expect(availableSpaces(multi)).toBe(1);
    });

    it('defaults missing multi-unit aggregates to zero', () => {
        // The withCount aggregates are optional props; absent means none loaded.
        const multi = makeProperty({ rental_type: 'multi_unit' });

        expect(spaceCount(multi)).toBe(0);
        expect(occupiedSpaces(multi)).toBe(0);
        expect(availableSpaces(multi)).toBe(0);
    });
});

describe('rentRoll', () => {
    it('returns the property rent for a whole rental', () => {
        expect(rentRoll(makeProperty({ rent_amount: '1500' }))).toBe(1500);
    });

    it('treats a whole rental with no rent as zero', () => {
        expect(rentRoll(makeProperty({ rent_amount: null }))).toBe(0);
    });

    it('sums only the occupied units of a multi-unit rental', () => {
        // Vacant units produce no income, so they must not inflate the roll.
        const multi = makeProperty({
            rental_type: 'multi_unit',
            units: [
                makeUnit({ id: 1, status: 'occupied', rent_amount: '1000' }),
                makeUnit({ id: 2, status: 'available', rent_amount: '1200' }),
                makeUnit({ id: 3, status: 'occupied', rent_amount: '900' }),
            ],
        });

        expect(rentRoll(multi)).toBe(1900);
    });

    it('returns zero for a multi-unit rental with no loaded units', () => {
        expect(rentRoll(makeProperty({ rental_type: 'multi_unit' }))).toBe(0);
    });
});
