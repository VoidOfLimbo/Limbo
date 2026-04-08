<script lang="ts">
/**
 * Tilt group — overrides transform/position for one or more specific characters.
 *
 * Example:
 *   :tilt="[
 *     { chars: 'L',         angle: 18, top: '4px' },
 *     { chars: ['?', '!'],  angle: 20, left: '-3px', scale: 1.1 },
 *     { chars: ['H', 'h'],  angle: -12, bottom: '2px', right: '1px' },
 *   ]"
 */
export type TiltGroup = {
    /** Single char string or array of char strings (case-sensitive). */
    chars: string | string[];
    /** Rotation in degrees. Positive = clockwise. */
    angle?: number;
    /** Uniform scale factor. 1 = normal, 1.2 = 20% larger, 0.8 = 20% smaller. */
    scale?: number;
    /** CSS offset from top (e.g. '4px', '0.2em'). Positive moves down. */
    top?: string;
    /** CSS offset from bottom (e.g. '4px'). Positive moves up. */
    bottom?: string;
    /** CSS offset from left (e.g. '4px'). Positive moves right. */
    left?: string;
    /** CSS offset from right (e.g. '4px'). Positive moves left. */
    right?: string;
};
</script>

<script setup lang="ts">
import { computed } from 'vue';

/**
 * NeonText — per-character neon blink component.
 *
 * Offsets use CSS length strings (px, em, rem, %) and behave like
 * CSS `top/bottom/left/right` on a `position: relative` element.
 */

type CharTransform = {
    angle: number | null;
    scale: number | null;
    top: string | null;
    bottom: string | null;
    left: string | null;
    right: string | null;
};

const props = withDefaults(
    defineProps<{
        text: string;
        /** Max spread across which delays are distributed (seconds) */
        spread?: number;
        /** Base cycle duration per character (seconds) */
        baseDuration?: number;
        /** Optional tilt groups — characters listed here get transforms applied */
        tilt?: TiltGroup[];
    }>(),
    {
        spread: 10,
        baseDuration: 5,
        tilt: () => [],
    },
);

type CharData = {
    char: string;
    delay: string;
    duration: string;
    transform: CharTransform | null;
};

/** Build a lookup map: char → CharTransform */
const tiltMap = computed<Map<string, CharTransform>>(() => {
    const map = new Map<string, CharTransform>();

    for (const group of props.tilt) {
        const chars = Array.isArray(group.chars) ? group.chars : [group.chars];
        const transform: CharTransform = {
            angle: group.angle ?? null,
            scale: group.scale ?? null,
            top: group.top ?? null,
            bottom: group.bottom ?? null,
            left: group.left ?? null,
            right: group.right ?? null,
        };

        for (const c of chars) {
            map.set(c, transform);
        }
    }

    return map;
});

const chars = computed<CharData[]>(() =>
    [...props.text].map((char, i) => {
        const delay = ((i * 1.618034) % props.spread).toFixed(2);
        const duration = (props.baseDuration + (i % 5) * 0.8).toFixed(1);
        const displayChar = char === ' ' ? '\u00A0' : char;
        const transform = tiltMap.value.get(char) ?? null;

        return {
            char: displayChar,
            delay: `${delay}s`,
            duration: `${duration}s`,
            transform,
        };
    }),
);

function charStyle(c: CharData): Record<string, string> {
    const style: Record<string, string> = {
        animationDelay: c.delay,
        animationDuration: c.duration,
    };

    if (c.transform === null) {
        return style;
    }

    style.display = 'inline-block';
    style.position = 'relative';

    if (c.transform.angle !== null || c.transform.scale !== null) {
        const rotate = c.transform.angle !== null ? `rotate(${c.transform.angle}deg)` : '';
        const scale = c.transform.scale !== null ? `scale(${c.transform.scale})` : '';
        style.transform = [rotate, scale].filter(Boolean).join(' ');
    }

    if (c.transform.top !== null) {
        style.top = c.transform.top;
    }

    if (c.transform.bottom !== null) {
        style.bottom = c.transform.bottom;
    }

    if (c.transform.left !== null) {
        style.left = c.transform.left;
    }

    if (c.transform.right !== null) {
        style.right = c.transform.right;
    }

    return style;
}
</script>

<template>
    <span class="limbo-neon-text" :aria-label="text">
        <span
            v-for="(c, i) in chars"
            :key="i"
            class="limbo-char-blink"
            :style="charStyle(c)"
            aria-hidden="true"
            >{{ c.char }}</span
        >
    </span>
</template>

<style scoped>
/**
 * Per-character neon blink.
 * Each span gets unique animation-delay / animation-duration via inline style.
 *
 * How it works:
 *  1. Resting  — filled text sits on top of the dotted bg (invisible dots)
 *  2. Spark    — tight drop-shadows burst from the character
 *  3. Outline  — color → transparent reveals dotted fill; stroke draws hollow outline
 *  4. Flash    — bright snap before returning to normal
 */
.limbo-char-blink {
    display: inline-block; /* required for background-clip: text */
    position: relative;
    font-family: var(--font-neon);
    /* Ensure descenders (g, y, p, j) are fully inside the element box */
    line-height: 1.3;
    padding-bottom: 0.15em;

    /* Dotted fill — offset origin centres the grid on the glyph body */
    background-image: radial-gradient(circle, var(--color-neon) 1.5px, transparent 1.5px);
    background-size: 4px 4px;
    background-position: 2px 2px;
    -webkit-background-clip: text;
    background-clip: text;

    animation: limbo-char-blink 5s ease-in-out infinite both;
}

@keyframes limbo-char-blink {
    /* Resting: filled text covers the dotted background */
    0%,
    62%,
    100% {
        color: inherit;
        -webkit-text-stroke-width: 0;
        filter: none;
    }

    /* Spark: tight bright burst */
    70% {
        color: var(--color-neon);
        -webkit-text-stroke-width: 0;
        filter:
            drop-shadow(0 0 3px var(--color-neon))
            drop-shadow(0 0 8px var(--color-neon))
            brightness(1.4);
    }

    /* Outline + dotted fill revealed */
    76%,
    90% {
        color: transparent;
        -webkit-text-stroke: 1.5px var(--color-neon);
        filter: drop-shadow(0 0 4px var(--color-neon-glow));
    }

    /* Flicker mid-outline */
    83% {
        color: transparent;
        -webkit-text-stroke: 1px var(--color-neon);
        filter: none;
    }

    /* Snap back: bright flash */
    95% {
        color: var(--color-neon);
        -webkit-text-stroke-width: 0;
        filter: drop-shadow(0 0 6px var(--color-neon));
    }
}
</style>
