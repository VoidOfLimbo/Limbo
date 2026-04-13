<script lang="ts">
/**
 * Named glitch variants for GlitchText.
 *
 * Use with `include` to whitelist, or `exclude` to blacklist variants.
 * Both can be combined: include takes priority, exclude is applied after.
 *
 * Example — only shake & tear:
 *   :include="['shake', 'tear']"
 *
 * Example — everything except invert:
 *   :exclude="['invert']"
 */
export type GlitchName =
    | 'rgb-split'      // Classic RGB colour split with horizontal skew
    | 'shake'          // Rapid micro horizontal shake
    | 'tear'           // Horizontal tear with clip-path slice
    | 'blur-burst'     // Heavy blur burst then sharp snap
    | 'invert'         // Repeated colour invert flash
    | 'mega-tear'      // Wide horizontal mega tear
    | 'scale-distort'  // Compress then stretch scale distortion
    | 'ghost'          // Ghost double image (rgba shadows)
    | 'data-corrupt';  // Rapid multi-colour chaos

/** Per-variant default playback duration in ms — tuned to match each keyframe length */
export const GLITCH_DURATIONS: Record<GlitchName, number> = {
    'rgb-split':     700,
    'shake':         650,
    'tear':          600,
    'blur-burst':    750,
    'invert':        500,
    'mega-tear':     600,
    'scale-distort': 700,
    'ghost':         650,
    'data-corrupt':  750,
};

/** All available glitch variant names in declaration order. */
export const ALL_GLITCH_VARIANTS = Object.keys(GLITCH_DURATIONS) as GlitchName[];
</script>

<script setup lang="ts">
import { computed, ref } from 'vue';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        /** Array of texts to cycle through. Each glitch trigger advances to the next. */
        texts: string[];
        /**
         * Whitelist of variants to sample from.
         * When omitted, all are available.
         * Evaluated before `exclude`.
         */
        include?: GlitchName[];
        /**
         * Variants to remove from the pool even if listed in `include`.
         * Useful for blocking a specific effect without enumerating the rest.
         */
        exclude?: GlitchName[];
        /**
         * Override the per-variant default play duration (ms).
         * Omit to let each variant use its own tuned value.
         */
        duration?: number;
        /** HTML element to render. Default: 'span'. */
        tag?: string;
    }>(),
    {
        include: () => [...ALL_GLITCH_VARIANTS],
        exclude: () => [],
        duration: undefined,
        tag: 'span',
    },
);

/** Resolved variant pool after applying include + exclude filters. */
const pool = computed<GlitchName[]>(() => {
    const base = props.include.length ? props.include : ALL_GLITCH_VARIANTS;

    return base.filter((v) => !props.exclude.includes(v));
});

const activeVariant = ref<GlitchName | null>(null);
const currentIndex = ref(0);
const currentText = computed(() => props.texts[currentIndex.value] ?? '');

/** Trigger a random glitch from the resolved pool. Call via template ref. */
function trigger(): void {
    if (pool.value.length === 0) {
        return;
    }

    activeVariant.value = null;
    currentIndex.value = (currentIndex.value + 1) % props.texts.length;

    requestAnimationFrame(() => {
        const picked = pool.value[Math.floor(Math.random() * pool.value.length)];
        activeVariant.value = picked;

        setTimeout(() => {
            activeVariant.value = null;
        }, props.duration ?? GLITCH_DURATIONS[picked]);
    });
}

defineExpose({ trigger, pool });
</script>

<template>
    <component
        :is="tag"
        :class="['glitch-root', activeVariant ? `glitch-${activeVariant}` : '']"
        :data-text="currentText"
        v-bind="$attrs"
    >{{ currentText }}</component>
</template>

<style scoped>
.glitch-root {
    position: relative;
    font-family: var(--font-neon);
}

.glitch-rgb-split     { animation: glitch-rgb-split     0.70s forwards; }
.glitch-shake         { animation: glitch-shake         0.65s forwards; }
.glitch-tear          { animation: glitch-tear          0.60s forwards; }
.glitch-blur-burst    { animation: glitch-blur-burst    0.75s forwards; }
.glitch-invert        { animation: glitch-invert        0.50s forwards; }
.glitch-mega-tear     { animation: glitch-mega-tear     0.60s forwards; }
.glitch-scale-distort { animation: glitch-scale-distort 0.70s forwards; }
.glitch-ghost         { animation: glitch-ghost         0.65s forwards; }
.glitch-data-corrupt  { animation: glitch-data-corrupt  0.75s forwards; }

/* rgb-split — Classic RGB colour split + skew */
@keyframes glitch-rgb-split {
    0%, 100% { text-shadow: none; transform: none; filter: none; }
    15%       { text-shadow: -4px 0 #f0f, 4px 0 #0ff; transform: translateX(-3px) skewX(-3deg); }
    30%       { text-shadow: 4px 0 #f0f, -4px 0 #0ff; transform: translateX(3px) skewX(3deg); }
    50%       { text-shadow: -2px 0 #f0f, 2px 0 #0ff; transform: translateX(-1px); filter: blur(0.5px); }
    70%       { text-shadow: none; transform: none; filter: none; }
}

/* shake — Rapid micro horizontal shake */
@keyframes glitch-shake {
    0%, 100% { transform: none; }
    10%       { transform: translateX(-7px); }
    20%       { transform: translateX(7px); }
    30%       { transform: translateX(-5px); }
    40%       { transform: translateX(5px); }
    50%       { transform: translateX(-3px); }
    60%       { transform: translateX(3px); }
    75%       { transform: translateX(-1px); }
    85%       { transform: none; }
}

/* tear — Horizontal tear with clip-path slice */
@keyframes glitch-tear {
    0%, 100% { transform: none; text-shadow: none; clip-path: none; }
    20%       { transform: translateX(10px); text-shadow: -10px 0 #0ff; }
    35%       { transform: translateX(-8px); text-shadow: 8px 0 #f0f; clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%); }
    50%       { transform: translateX(5px); clip-path: polygon(0 55%, 100% 55%, 100% 100%, 0 100%); text-shadow: -5px 0 #0ff; }
    65%       { clip-path: none; transform: none; text-shadow: none; }
}

/* blur-burst — Heavy blur burst then sharp snap */
@keyframes glitch-blur-burst {
    0%, 100% { filter: none; text-shadow: none; }
    20%       { filter: blur(3px) brightness(1.8); }
    40%       { filter: blur(0) brightness(2) contrast(1.5); text-shadow: 0 0 25px #0ff, 0 0 50px #0ff; }
    65%       { filter: blur(1.5px); text-shadow: none; }
    80%       { filter: none; }
}

/* invert — Repeated colour invert flash */
@keyframes glitch-invert {
    0%, 100% { filter: none; }
    15%       { filter: invert(1); }
    22%       { filter: none; }
    40%       { filter: invert(1) hue-rotate(90deg); }
    47%       { filter: none; }
    60%       { filter: invert(1) saturate(2); }
    67%       { filter: none; }
}

/* mega-tear — Wide horizontal mega tear */
@keyframes glitch-mega-tear {
    0%, 100% { transform: none; text-shadow: none; }
    15%       { transform: translateX(22px); text-shadow: -22px 0 #f0f; }
    30%       { transform: translateX(-18px); text-shadow: 18px 0 #0ff; }
    45%       { transform: translateX(10px); text-shadow: -10px 0 #f0f; }
    60%       { transform: translateX(-5px); text-shadow: 5px 0 #0ff; }
    75%       { transform: none; text-shadow: none; }
}

/* scale-distort — Compress then stretch scale distortion */
@keyframes glitch-scale-distort {
    0%, 100% { transform: none; }
    20%       { transform: scaleX(1.1) scaleY(0.92); }
    35%       { transform: scaleX(0.88) scaleY(1.08); }
    50%       { transform: scaleX(1.06) scaleY(0.96); }
    65%       { transform: scaleX(0.96) scaleY(1.03); }
    80%       { transform: none; }
}

/* ghost — Ghost double image */
@keyframes glitch-ghost {
    0%, 100% { text-shadow: none; }
    20%       { text-shadow: 8px 4px 0 rgba(0, 255, 255, 0.55), -8px -4px 0 rgba(255, 0, 255, 0.55); }
    40%       { text-shadow: -8px -4px 0 rgba(0, 255, 255, 0.55), 8px 4px 0 rgba(255, 0, 255, 0.55); }
    60%       { text-shadow: 4px 8px 0 rgba(0, 255, 255, 0.35), -4px 0 0 rgba(255, 0, 255, 0.35); }
    80%       { text-shadow: none; }
}

/* data-corrupt — Rapid multi-colour data corruption */
@keyframes glitch-data-corrupt {
    0%, 100% { transform: none; text-shadow: none; filter: none; }
    10%       { transform: translateX(-6px) skewX(6deg); text-shadow: 6px 0 #f00; filter: brightness(1.6); }
    20%       { transform: translateX(6px) skewX(-6deg); text-shadow: -6px 0 #0f0; filter: brightness(0.7); }
    30%       { transform: translateX(-4px) skewY(3deg); text-shadow: 4px 0 #00f; filter: none; }
    40%       { transform: translateX(4px) skewY(-3deg); text-shadow: -4px 0 #f0f; filter: contrast(2); }
    50%       { transform: translateX(-3px); text-shadow: 3px 0 #0ff; filter: brightness(1.3); }
    60%       { transform: translateX(3px); text-shadow: -3px 0 #ff0; filter: none; }
    70%       { transform: none; text-shadow: none; filter: none; }
}
</style>
