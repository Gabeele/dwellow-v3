import type { BadgeVariants } from '@/components/ui/badge';
import type { ScoreStatus } from '@/types/property';

type AgentBadgeVariant = Extract<
    BadgeVariants['variant'],
    'neutral' | 'ai' | 'success' | 'danger'
>;

const AGENT_STATUS_VARIANTS: Record<ScoreStatus, AgentBadgeVariant> = {
    pending: 'neutral',
    processing: 'ai',
    completed: 'success',
    failed: 'danger',
};

/**
 * Map an agent run status to its {@link Badge} variant. The human-readable
 * label travels with the row from the server (the PHP enum is authoritative),
 * so only the tint is resolved client-side. Unknown values fall back to the
 * neutral pending tint so a stray status never crashes a render.
 */
export function agentStatusVariant(status: string): AgentBadgeVariant {
    return AGENT_STATUS_VARIANTS[status as ScoreStatus] ?? 'neutral';
}

/**
 * Format how long an agent run has taken: the gap between `started_at` and
 * `completed_at`, or — while it is still running — between `started_at` and
 * `nowMs`. Returns an em dash before the run begins. `nowMs` is injectable so
 * the live-ticking timer (and tests) can drive it deterministically.
 */
export function formatAgentElapsed(
    startedAt: string | null,
    completedAt: string | null,
    nowMs: number = Date.now(),
): string {
    if (!startedAt) {
        return '—';
    }

    const start = new Date(startedAt).getTime();
    const end = completedAt ? new Date(completedAt).getTime() : nowMs;
    const seconds = Math.max(0, Math.round((end - start) / 1000));

    if (seconds < 60) {
        return `${seconds}s`;
    }

    const minutes = Math.floor(seconds / 60);
    const remainder = seconds % 60;

    return remainder ? `${minutes}m ${remainder}s` : `${minutes}m`;
}
