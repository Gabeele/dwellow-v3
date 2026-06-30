import { describe, expect, it } from 'vitest';
import { agentStatusVariant, formatAgentElapsed } from '@/lib/agentStatus';

describe('agentStatusVariant', () => {
    it('maps each known status to its own tint', () => {
        expect(agentStatusVariant('pending')).toBe('neutral');
        expect(agentStatusVariant('processing')).toBe('ai');
        expect(agentStatusVariant('completed')).toBe('success');
        expect(agentStatusVariant('failed')).toBe('danger');
    });

    it('falls back to the neutral tint for unknown values', () => {
        // An unexpected server status must never crash a badge render.
        expect(agentStatusVariant('garbage')).toBe('neutral');
    });
});

describe('formatAgentElapsed', () => {
    it('returns an em dash before the run has started', () => {
        expect(formatAgentElapsed(null, null)).toBe('—');
    });

    it('formats sub-minute completed runs in seconds', () => {
        expect(
            formatAgentElapsed(
                '2026-06-30T10:00:00Z',
                '2026-06-30T10:00:42Z',
            ),
        ).toBe('42s');
    });

    it('formats whole-minute completed runs without a seconds remainder', () => {
        expect(
            formatAgentElapsed(
                '2026-06-30T10:00:00Z',
                '2026-06-30T10:02:00Z',
            ),
        ).toBe('2m');
    });

    it('formats minutes with a seconds remainder', () => {
        expect(
            formatAgentElapsed(
                '2026-06-30T10:00:00Z',
                '2026-06-30T10:01:30Z',
            ),
        ).toBe('1m 30s');
    });

    it('measures a still-running agent against the injected clock', () => {
        const now = new Date('2026-06-30T10:00:15Z').getTime();

        expect(formatAgentElapsed('2026-06-30T10:00:00Z', null, now)).toBe(
            '15s',
        );
    });

    it('never reports negative elapsed time for clock skew', () => {
        const now = new Date('2026-06-30T09:59:50Z').getTime();

        expect(formatAgentElapsed('2026-06-30T10:00:00Z', null, now)).toBe(
            '0s',
        );
    });
});
