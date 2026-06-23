import type { BadgeVariants } from '@/components/ui/badge';
import type { ApplicationStatus } from '@/types/property';

type ApplicationBadgeVariant = Extract<
    BadgeVariants['variant'],
    'default' | 'warning' | 'success' | 'danger'
>;

/**
 * The display descriptor for an application status: which {@link Badge}
 * variant tints it and the human-readable label to render.
 */
export interface ApplicationStatusBadge {
    variant: ApplicationBadgeVariant;
    label: string;
}

const APPLICATION_STATUS_BADGES: Record<
    ApplicationStatus,
    ApplicationStatusBadge
> = {
    new: { variant: 'default', label: 'New' },
    reviewing: { variant: 'warning', label: 'Reviewing' },
    approved: { variant: 'success', label: 'Approved' },
    rejected: { variant: 'danger', label: 'Rejected' },
};

/**
 * Map an application status string to its badge variant and label.
 * Unknown values fall back to the neutral "New" descriptor.
 */
export function applicationStatusBadge(status: string): ApplicationStatusBadge {
    return (
        APPLICATION_STATUS_BADGES[status as ApplicationStatus] ??
        APPLICATION_STATUS_BADGES.new
    );
}
