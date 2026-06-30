import type { Ref } from 'vue';
import { onScopeDispose, ref, watch } from 'vue';

/**
 * A reactive clock that advances every `intervalMs` while `active` is truthy.
 * The returned `now` ref holds the current epoch-ms; it ticks only while a run
 * is in flight and freezes the moment `active` goes false, so a live-ticking
 * elapsed display recomputes each second without burning a timer when idle. On
 * reactivation the clock catches up to the real current time. Cleans up its
 * interval on scope dispose (component unmount or `effectScope` stop).
 */
export function useNow(active: Ref<boolean>, intervalMs: number = 1000): Ref<number> {
    const now = ref(Date.now());
    let handle: ReturnType<typeof setInterval> | null = null;

    function stop(): void {
        if (handle !== null) {
            clearInterval(handle);
            handle = null;
        }
    }

    function start(): void {
        if (handle !== null) {
            return;
        }

        now.value = Date.now();
        handle = setInterval(() => {
            now.value = Date.now();
        }, intervalMs);
    }

    watch(active, (isActive) => (isActive ? start() : stop()), {
        immediate: true,
        flush: 'sync',
    });

    onScopeDispose(stop);

    return now;
}
