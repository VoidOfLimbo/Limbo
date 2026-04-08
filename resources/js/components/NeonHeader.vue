<script setup lang="ts">
import { computed } from 'vue';
import NeonText from '@/components/NeonText.vue';
import type { TiltGroup } from '@/components/NeonText.vue';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        /** The text to display with the neon glow + flicker effect. */
        text: string;
        /** HTML element to render. Default: 'p'. */
        tag?: 'p' | 'h1' | 'h2' | 'h3' | 'h4' | 'span' | 'div';
        /** Base text colour when fully lit. Accepts any CSS colour string or variable.
         * Defaults to `var(--foreground)` so it automatically adapts to light/dark mode.
         */
        color?: string;
        /**
         * Glow / halo colour — include opacity here.
         * Defaults to a 35% mix of --foreground so the glow always contrasts the background.
         */
        glowColor?: string;
        /** Full flicker-cycle duration in seconds. Default: 7. */
        flickerDuration?: number;
        /**
         * Max per-character delay spread for the NeonText blink animation (seconds).
         * Higher = characters blink more out of phase with each other.
         * Default: 12.
         */
        spread?: number;
        /**
         * Base blink-cycle duration for each character (seconds).
         * Lower = faster individual blinks. Default: 7.
         */
        baseDuration?: number;
        /** Per-character tilt / transform overrides. Passed through to NeonText. */
        tilt?: TiltGroup[];
    }>(),
    {
        tag: 'p',
        color: 'var(--foreground)',
        glowColor: 'color-mix(in srgb, var(--foreground) 35%, transparent)',
        flickerDuration: 7,
        spread: 12,
        baseDuration: 7,
        tilt: () => [],
    },
);

const animDuration = computed(() => `${props.flickerDuration}s`);
</script>

<template>
    <component :is="tag" class="neon-header-root" v-bind="$attrs">
        <NeonText :text="text" :spread="spread" :base-duration="baseDuration" :tilt="tilt" />
    </component>
</template>

<style scoped>
.neon-header-root {
    font-family: var(--font-neon);
    color: v-bind(color);
    animation: neon-header-flicker v-bind(animDuration) infinite;
}

@keyframes neon-header-flicker {
    /* ── Fully lit ── */
    0%,
    19%,
    21%,
    23%,
    25%,
    54%,
    56%,
    100% {
        color: v-bind(color);
        text-shadow:
            0 0 10px v-bind(glowColor),
            0 0 30px v-bind(glowColor),
            0 0 60px v-bind(glowColor);
    }

    /* ── Flicker off ── */
    20%,
    24%,
    55% {
        color: color-mix(in srgb, v-bind(color) 60%, transparent);
        text-shadow: none;
    }

    /* ── Half-off flicker ── */
    22% {
        color: color-mix(in srgb, v-bind(color) 85%, transparent);
        text-shadow: 0 0 5px color-mix(in srgb, v-bind(color) 50%, transparent);
    }
}
</style>
