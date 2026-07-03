import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { effectScope, ref } from 'vue';
import { useNow } from '@/composables/useNow';

describe('useNow', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-06-30T10:00:00Z'));
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('advances on each interval while active', () => {
        const active = ref(true);
        const scope = effectScope();
        const now = scope.run(() => useNow(active, 1000))!;

        const start = now.value;
        vi.advanceTimersByTime(3000);

        expect(now.value).toBe(start + 3000);

        scope.stop();
    });

    it('freezes while inactive and catches up on reactivation', () => {
        const active = ref(true);
        const scope = effectScope();
        const now = scope.run(() => useNow(active, 1000))!;

        vi.advanceTimersByTime(1000);
        const settled = now.value;

        // Idle: the clock must not tick — no wasted timer once work settles.
        active.value = false;
        vi.advanceTimersByTime(5000);
        expect(now.value).toBe(settled);

        // Resuming snaps straight to the real current time, then ticks again.
        active.value = true;
        expect(now.value).toBe(Date.now());
        vi.advanceTimersByTime(2000);
        expect(now.value).toBe(Date.now());

        scope.stop();
    });

    it('clears its interval when the scope is disposed', () => {
        const active = ref(true);
        const scope = effectScope();
        const now = scope.run(() => useNow(active, 1000))!;

        scope.stop();
        const final = now.value;
        vi.advanceTimersByTime(5000);

        expect(now.value).toBe(final);
    });
});
