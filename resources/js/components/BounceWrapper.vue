<script setup lang="ts">
import { computed } from 'vue';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        /**
         * Direction of the bounce movement.
         * Default: 'down'.
         */
        direction?: 'up' | 'down' | 'left' | 'right';
        /**
         * Distance to travel at the peak of the bounce.
         * Any CSS length string: '7px', '0.5rem', '10%'.
         * Default: '7px'.
         */
        distance?: string;
        /**
         * Full animation cycle duration in seconds.
         * Lower = faster bounce. Default: 2.
         */
        duration?: number;
        /**
         * CSS easing function for the bounce.
         * Default: 'ease-in-out'.
         */
        easing?: string;
        /**
         * Whether the bounce is active. Set to false to pause.
         * Default: true.
         */
        active?: boolean;
        /** HTML element to render. Default: 'span'. */
        tag?: string;
    }>(),
    {
        direction: 'down',
        distance: '7px',
        duration: 2,
        easing: 'ease-in-out',
        active: true,
        tag: 'span',
    },
);

const peakTransform = computed(() => {
    switch (props.direction) {
        case 'up':    return `translateY(-${props.distance})`;
        case 'left':  return `translateX(-${props.distance})`;
        case 'right': return `translateX(${props.distance})`;
        default:      return `translateY(${props.distance})`;
    }
});

const animDuration = computed(() => `${props.duration}s`);
const animState    = computed(() => (props.active ? 'running' : 'paused'));
</script>

<template>
    <component :is="tag" class="bounce-root" v-bind="$attrs">
        <slot />
    </component>
</template>

<style scoped>
.bounce-root {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    animation: bounce-anim v-bind(animDuration) v-bind(easing) infinite;
    animation-play-state: v-bind(animState);
}

@keyframes bounce-anim {
    0%,
    100% {
        transform: none;
    }

    50% {
        transform: v-bind(peakTransform);
    }
}
</style>
