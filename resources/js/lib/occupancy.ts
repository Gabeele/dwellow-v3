import type { BadgeVariants } from '@/components/ui/badge';
import type { Property } from '@/types/property';

/**
 * The set of occupancy states a unit can be in, viewed through the
 * income lens used across the dashboard.
 */
export type OccupancyStatus = 'occupied' | 'available' | 'unavailable';

type OccupancyBadgeVariant = Extract<
    BadgeVariants['variant'],
    'success' | 'warning' | 'neutral'
>;

/**
 * The display descriptor for an occupancy status: which {@link Badge}
 * variant tints it and the human-readable label to render.
 */
export interface OccupancyBadge {
    variant: OccupancyBadgeVariant;
    label: string;
}

const OCCUPANCY_BADGES: Record<OccupancyStatus, OccupancyBadge> = {
    occupied: { variant: 'success', label: 'Occupied' },
    available: { variant: 'warning', label: 'Available' },
    unavailable: { variant: 'neutral', label: 'Unavailable' },
};

/**
 * Map an occupancy status string to its badge variant and label.
 * Unknown values fall back to the neutral "Unavailable" descriptor.
 */
export function occupancyBadge(status: string): OccupancyBadge {
    return (
        OCCUPANCY_BADGES[status as OccupancyStatus] ??
        OCCUPANCY_BADGES.unavailable
    );
}

/**
 * Resolve a property's occupancy through the rentable-space model.
 *
 * A `whole` rental is a single rentable space whose occupancy mirrors the
 * property's own status. A `multi_unit` rental is occupied only once every
 * one of its units is taken: with no units it is unavailable, with at least
 * one vacancy it is available, otherwise occupied.
 */
export function propertyOccupancy(property: Property): OccupancyStatus {
    if (property.rental_type === 'multi_unit') {
        if ((property.units_count ?? 0) === 0) {
            return 'unavailable';
        }

        return (property.available_units_count ?? 0) > 0
            ? 'available'
            : 'occupied';
    }

    return OCCUPANCY_BADGES[property.status as OccupancyStatus]
        ? (property.status as OccupancyStatus)
        : 'unavailable';
}
