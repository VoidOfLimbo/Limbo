<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useTextScramble } from '@/composables/useTextScramble';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        /** Array of texts to cycle through in order. */
        texts: string[];
        /** Milliseconds between each cycle. Default: 2200. */
        interval?: number;
        /** Text colour. Any CSS colour string or variable. Default: var(--color-theme). */
        color?: string;
        /**
         * Glow colour. Include opacity here for best results.
         * Default: var(--color-theme-glow).
         */
        glowColor?: string;
        /** Flicker animation cycle duration in seconds. Default: 5. */
        flickerDuration?: number;
        /** HTML element to render. Default: 'span'. */
        tag?: string;
    }>(),
    {
        interval: 2200,
        color: 'var(--color-theme)',
        glowColor: 'var(--color-theme-glow)',
        flickerDuration: 5,
        tag: 'span',
    },
);

const emit = defineEmits<{
    /** Emitted on every cycle transition, after the scramble begins. */
    change: [index: number, text: string];
}>();

const currentIndex = ref(0);
const { scrambled, scramble } = useTextScramble(props.texts[0] ?? '');

let cycleTimer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    cycleTimer = setInterval(() => {
        currentIndex.value = (currentIndex.value + 1) % props.texts.length;
        scramble(props.texts[currentIndex.value]);
        emit('change', currentIndex.value, props.texts[currentIndex.value]);
    }, props.interval);
});

onBeforeUnmount(() => {
    if (cycleTimer !== null) {
        clearInterval(cycleTimer);
    }
});

const animDuration = computed(() => `${props.flickerDuration}s`);
</script>

<template>
    <component :is="tag" class="scramble-root" v-bind="$attrs">
        <span class="block">{{ scrambled }}</span>
    </component>
</template>

<style scoped>
.scramble-root {
    font-family: var(--font-neon);
    color: v-bind(color);
    text-shadow:
        0 0 18px v-bind(glowColor),
        0 0 40px v-bind(glowColor);
    animation: scramble-flicker v-bind(animDuration) infinite;
}

@keyframes scramble-flicker {
    0%,
    93%,
    100% {
        opacity: 1;
        text-shadow:
            0 0 18px v-bind(glowColor),
            0 0 40px v-bind(glowColor);
    }

    94% {
        opacity: 0.65;
        text-shadow: 0 0 4px v-bind(glowColor);
    }

    95% {
        opacity: 1;
        text-shadow:
            0 0 28px v-bind(glowColor),
            0 0 60px v-bind(glowColor);
    }

    96% {
        opacity: 0.8;
    }
}
</style>
