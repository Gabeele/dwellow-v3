<script setup lang="ts">
import { ImageOff } from '@lucide/vue';
import { ref } from 'vue';

interface Marker {
    /** 1-based callout number. */
    n: number;
    /** Horizontal position as a percentage of image width (0–100). */
    x: number;
    /** Vertical position as a percentage of image height (0–100). */
    y: number;
    /** Short description of what this callout points at. */
    label: string;
}

withDefaults(
    defineProps<{
        src: string;
        alt: string;
        markers?: Marker[];
        caption?: string;
    }>(),
    { markers: () => [], caption: '' },
);

const errored = ref(false);
</script>

<template>
    <figure
        class="overflow-hidden rounded-xl border border-border bg-card shadow-card"
    >
        <!-- Screenshot with numbered overlay callouts -->
        <div class="relative">
            <img
                v-show="!errored"
                :src="src"
                :alt="alt"
                loading="lazy"
                class="block w-full border-b border-border"
                @error="errored = true"
            />
            <!-- Fallback keeps the layout intentional if a screenshot is missing -->
            <div
                v-if="errored"
                class="flex aspect-[16/10] w-full flex-col items-center justify-center gap-2 border-b border-border bg-muted/40 text-muted-foreground"
            >
                <ImageOff :size="22" />
                <span class="text-13">Screenshot coming soon</span>
            </div>
            <template v-if="!errored">
                <span
                    v-for="marker in markers"
                    :key="marker.n"
                    class="absolute flex size-7 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-ai text-xs font-semibold text-ai-foreground ring-2 ring-background shadow-card-md"
                    :style="{ left: `${marker.x}%`, top: `${marker.y}%` }"
                    aria-hidden="true"
                >
                    {{ marker.n }}
                </span>
            </template>
        </div>

        <!-- Numbered legend -->
        <figcaption v-if="markers.length || caption" class="p-5">
            <ol v-if="markers.length" class="space-y-2.5">
                <li
                    v-for="marker in markers"
                    :key="marker.n"
                    class="flex items-start gap-3 text-13 leading-relaxed text-muted-foreground"
                >
                    <span
                        class="mt-px flex size-5 shrink-0 items-center justify-center rounded-full bg-ai text-[11px] font-semibold text-ai-foreground"
                    >
                        {{ marker.n }}
                    </span>
                    <span>{{ marker.label }}</span>
                </li>
            </ol>
            <p
                v-if="caption"
                class="text-13 text-muted-foreground"
                :class="markers.length ? 'mt-4 border-t border-border pt-4' : ''"
            >
                {{ caption }}
            </p>
        </figcaption>
    </figure>
</template>
