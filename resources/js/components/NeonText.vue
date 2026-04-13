<!--
    NeonText — per-character neon sign flicker animation.

    Renders `text` as individual <span> elements, each running an independently
    timed CSS @keyframe that simulates a neon tube flickering: resting solid,
    then bursting into rapid blinks, then settling back.

    Basic:
        <NeonText text="VoidOfLimbo" tag="p" default-neon-color="#ff0044" />

    Per-character colour:
        :flicker="[{ chars: 'V', neonColor: '#00ffff' }]"

    Per-character tilt:
        :tilt="[{ chars: 'L', angle: 18, top: '6px' }]"

    All extra attributes (class, id, aria-*, …) pass through to the root element.
-->
<script lang="ts">
export type TiltGroup = {
    chars: string | string[];
    /** Rotation angle in degrees. Positive = clockwise. */
    angle?: number;
    /** Scale factor (1 = normal size). */
    scale?: number;
    /** Offset from the top edge (e.g. '4px'). */
    top?: string;
    bottom?: string;
    left?: string;
    right?: string;
};

export type CharFlicker = {
    chars: string | string[];
    /** Neon glow colour for this character (hex, rgb, etc.). */
    neonColor?: string;
    minFlickers?: number;
    maxFlickers?: number;
    /** How often this character flickers, in seconds. */
    interval?: number;
    /** Blink duty-cycle speed. Lower = briefer dark cuts (more "on"). Default: 0.4. */
    speed?: number;
};
</script>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, useId } from 'vue';

const props = withDefaults(
    defineProps<{
        text: string;
        tag?: 'span' | 'p' | 'h1' | 'h2' | 'h3' | 'h4' | 'div';
        /** Resting fill colour for all characters. Accepts any CSS colour. Default: var(--foreground). */
        color?: string;
        /** Max seconds of delay spread across characters (golden-ratio distributed). Default: 10. */
        spread?: number;
        tilt?: TiltGroup[];
        flicker?: CharFlicker[];
        /** Neon glow colour used by all characters unless overridden per-char. Default: '#ff0044'. */
        defaultNeonColor?: string;
        /** Minimum blinks per flicker burst for all characters. Default: 2. */
        defaultMinFlickers?: number;
        /** Maximum blinks per flicker burst for all characters. Default: 6. */
        defaultMaxFlickers?: number;
        /** Base flicker interval in seconds. Default: 4. */
        defaultInterval?: number;
        /** Blink duty-cycle speed for all characters. Lower = briefer dark cuts. Default: 0.4. */
        defaultSpeed?: number;
    }>(),
    {
        tag: 'span',
        color: 'var(--foreground)',
        spread: 10,
        tilt: () => [],
        flicker: () => [],
        defaultNeonColor: '#ff0044',
        defaultMinFlickers: 2,
        defaultMaxFlickers: 6,
        defaultInterval: 9,
        defaultSpeed: 0.7,
    },
);

defineOptions({ inheritAttrs: false });

const uid = useId().replace(/[^a-z0-9-]/gi, '-');

const tiltMap = computed<Map<string, TiltGroup>>(() => {
    const map = new Map<string, TiltGroup>();

    for (const g of props.tilt) {
        const targets = Array.isArray(g.chars) ? g.chars : [g.chars];

        for (const c of targets) {
            map.set(c, g);
        }
    }

    return map;
});

type Frozen = { neonColor: string; flickerCount: number; duration: number; speed: number; name: string };
const frozen = ref<Frozen[]>([]);

function initFrozen(random: boolean): void {
    const flickerMap = new Map<string, CharFlicker>();

    for (const f of props.flicker) {
        const targets = Array.isArray(f.chars) ? f.chars : [f.chars];

        for (const c of targets) {
            flickerMap.set(c, f);
        }
    }

    frozen.value = [...props.text].map((char, i) => {
        const cfg = flickerMap.get(char);

        const neonColor = cfg?.neonColor ?? props.defaultNeonColor;

        const min = cfg?.minFlickers ?? props.defaultMinFlickers;
        const max = Math.max(min, cfg?.maxFlickers ?? props.defaultMaxFlickers);
        const flickerCount = random ? Math.floor(Math.random() * (max - min + 1)) + min : min + (i % (max - min + 1));

        const base = cfg?.interval ?? props.defaultInterval;
        const duration = random ? base * (0.5 + Math.random() * 1.2) : base * (0.7 + (i % 7) * 0.1);

        return { neonColor, flickerCount, duration, speed: cfg?.speed ?? props.defaultSpeed, name: `lnc-${uid}-${i}` };
    });
}

const DOT_START = 78;
const DOT_END = 97;
const DOT_RANGE = DOT_END - DOT_START;
const REST_END = DOT_START - 3;
const SPARK = DOT_START - 1;

function buildKeyframe(f: Frozen): string {
    const nc = f.neonColor;
    const step = DOT_RANGE / f.flickerCount;
    const onRatio = Math.min(0.85, Math.max(0.15, 0.5 / Math.max(0.1, f.speed)));
    const glowOn = `drop-shadow(0 0 2px ${nc}) drop-shadow(0 0 7px ${nc})`;
    const sparkGlow = `drop-shadow(0 0 4px ${nc}) brightness(1.15)`;

    let stops = '';

    for (let i = 0; i < f.flickerCount; i++) {
        const onAt = parseFloat((DOT_START + i * step).toFixed(2));
        const offAt = parseFloat((onAt + onRatio * step).toFixed(2));
        const snapAt = parseFloat((offAt + 0.05).toFixed(2));
        stops += `    ${onAt}%  { color: transparent; -webkit-text-stroke: 1.5px ${nc}; filter: ${glowOn}; }\n`;
        stops += `    ${offAt}% { color: transparent; -webkit-text-stroke: 1.5px ${nc}; filter: ${glowOn}; }\n`;
        stops += `    ${snapAt}% { color: transparent; -webkit-text-stroke: 0.5px ${nc}; filter: none; }\n`;
    }

    stops += `    ${parseFloat((DOT_END - 0.1).toFixed(2))}% { color: transparent; -webkit-text-stroke: 1.5px ${nc}; filter: ${glowOn}; }\n`;

    return `@keyframes ${f.name} {
    0%, ${REST_END}%  { color: inherit; -webkit-text-stroke-width: 0; filter: none; }
    ${SPARK}%    { color: ${nc}; -webkit-text-stroke-width: 0; filter: ${sparkGlow}; }
    ${DOT_START}%   { color: ${nc}; -webkit-text-stroke-width: 0; filter: ${glowOn}; }
    ${DOT_START + 0.05}% { color: transparent; -webkit-text-stroke: 1.5px ${nc}; filter: ${glowOn}; }
${stops}    ${DOT_END}%   { color: ${nc}; -webkit-text-stroke-width: 0; filter: ${sparkGlow}; }
    ${DOT_END + 3}%      { color: ${nc}; -webkit-text-stroke-width: 0; filter: drop-shadow(0 0 2px ${nc}); }
    100%     { color: inherit; -webkit-text-stroke-width: 0; filter: none; }
}`;
}

let styleEl: HTMLStyleElement | null = null;

function injectStyles(): void {
    if (typeof document === 'undefined') {
        return;
    }

    if (!styleEl) {
        styleEl = document.createElement('style');
        styleEl.setAttribute('data-neon-uid', uid);
        document.head.appendChild(styleEl);
    }

    styleEl.textContent = frozen.value.map((f) => buildKeyframe(f)).join('\n');
}

initFrozen(false);

onMounted(() => {
    initFrozen(true);
    injectStyles();
});

onBeforeUnmount(() => {
    styleEl?.remove();
    styleEl = null;
});

const chars = computed(() =>
    [...props.text].map((char, i) => {
        const f = frozen.value[i];
        const delay = ((i * 1.618034) % props.spread).toFixed(2);
        const t = tiltMap.value.get(char);

        const style: Record<string, string> = {
            animationName: f.name,
            animationDuration: `${f.duration.toFixed(2)}s`,
            animationDelay: `-${delay}s`,
            animationIterationCount: 'infinite',
            animationTimingFunction: 'linear',
            backgroundImage: `radial-gradient(circle, ${f.neonColor} 1.5px, transparent 1.5px)`,
        };

        if (t) {
            if (t.angle != null || t.scale != null) {
                const r = t.angle != null ? `rotate(${t.angle}deg)` : '';
                const s = t.scale != null ? `scale(${t.scale})` : '';
                style.transform = [r, s].filter(Boolean).join(' ');
            }

            if (t.top != null) {
                style.top = t.top;
            }

            if (t.bottom != null) {
                style.bottom = t.bottom;
            }

            if (t.left != null) {
                style.left = t.left;
            }

            if (t.right != null) {
                style.right = t.right;
            }
        }

        return { char: char === ' ' ? '\u00a0' : char, style };
    }),
);
</script>

<template>
    <component :is="tag" class="neon-text-root" v-bind="$attrs" :aria-label="text">
        <span v-for="(c, i) in chars" :key="i" class="neon-char" :style="c.style" aria-hidden="true">{{ c.char }}</span>
    </component>
</template>

<style scoped>
.neon-text-root {
    font-family: var(--font-neon);
    color: v-bind(color);
}

.neon-char {
    display: inline-block;
    position: relative;
    line-height: 1.3;
    padding-bottom: 0.1em;
    background-size: 4px 4px;
    background-position: 2px 2px;
    -webkit-background-clip: text;
    background-clip: text;
}
</style>
