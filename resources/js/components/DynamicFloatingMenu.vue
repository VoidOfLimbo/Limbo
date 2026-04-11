<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Component } from 'vue';

export interface DynamicFloatingMenuItem {
    id: string;
    label: string;
    icon: Component;
}

type Position = 'top' | 'bottom' | 'left' | 'right';
type Align = 'start' | 'center' | 'end';

const props = withDefaults(
    defineProps<{
        items: DynamicFloatingMenuItem[];
        position?: Position;
        alignment?: Align;
        activeItem?: string;
        collapsible?: boolean;
        iconSize?: string;
    }>(),
    {
        position: 'right',
        alignment: 'center',
        activeItem: '',
        collapsible: true,
        iconSize: 'size-5',
    },
);

const emit = defineEmits<{
    select: [id: string];
}>();

const hoveredItem = ref<string | null>(null);

const isVertical = computed(() => props.position === 'left' || props.position === 'right');

const edgeClasses: Record<Position, string> = {
    right: 'right-0 rounded-l-2xl',
    left: 'left-0 rounded-r-2xl',
    top: 'top-0 rounded-b-2xl',
    bottom: 'bottom-0 rounded-t-2xl',
};

const alignClasses: Record<'vertical' | 'horizontal', Record<Align, string>> = {
    vertical: {
        start: 'top-4',
        center: 'top-1/2 -translate-y-1/2',
        end: 'bottom-4',
    },
    horizontal: {
        start: 'left-4',
        center: 'left-1/2 -translate-x-1/2',
        end: 'right-4',
    },
};

const positionClasses = computed(() => {
    const edge = edgeClasses[props.position];
    const axis = isVertical.value ? 'vertical' : 'horizontal';
    const alignment = alignClasses[axis][props.alignment];

    return `${edge} ${alignment}`;
});

const flexDirectionClass = computed(() => (isVertical.value ? 'flex-col' : 'flex-row'));

// Icon grows away from the edge it's attached to
const scaleOrigins: Record<Position, string> = {
    right: 'right center',
    left: 'left center',
    top: 'center top',
    bottom: 'center bottom',
};

// Tooltip appears on the opposite side from the edge
const tooltipClasses: Record<Position, string> = {
    right: 'right-[calc(100%+14px)] top-1/2 -translate-y-1/2',
    left: 'left-[calc(100%+14px)] top-1/2 -translate-y-1/2',
    top: 'top-[calc(100%+10px)] left-1/2 -translate-x-1/2',
    bottom: 'bottom-[calc(100%+10px)] left-1/2 -translate-x-1/2',
};

// When the icon scales 1.5× from an edge origin, its far edge (facing the tooltip)
// moves by (scale - 1) × size = 0.5 × 44 = 22px. Push the tooltip the same distance
// so the visual gap stays constant. Direction is always away from the dock edge.
const SCALE = 1.5;
const ICON_SIZE = 44; // size-11 in px
const tooltipShiftPx = (SCALE - 1) * ICON_SIZE; // 22px

const tooltipShiftMap: Record<Position, string> = {
    right: `translateX(-${tooltipShiftPx}px)`,
    left: `translateX(${tooltipShiftPx}px)`,
    top: `translateY(${tooltipShiftPx}px)`,
    bottom: `translateY(-${tooltipShiftPx}px)`,
};

function tooltipStyle(itemId: string): Record<string, string> {
    const isHovered = hoveredItem.value === itemId;

    return {
        transform: isHovered ? tooltipShiftMap[props.position] : 'none',
        transition: 'transform 300ms, opacity 150ms',
    };
}
</script>

<template>
    <nav
        :class="[
            'fixed z-40 hidden items-end gap-1 overflow-visible border border-white/10 bg-[rgba(83,83,83,0.4)] p-3 shadow-2xl shadow-black/20 backdrop-blur-[50px] lg:flex',
            positionClasses,
            flexDirectionClass,
            collapsible ? 'opacity-10 transition-opacity duration-300 hover:opacity-100' : '',
        ]"
    >
        <div
            v-for="item in items"
            :key="item.id"
            class="group relative transition-[margin] duration-300"
            :class="isVertical ? 'hover:my-2' : 'hover:mx-2'"
            @mouseenter="hoveredItem = item.id"
            @mouseleave="hoveredItem = null"
        >
            <!-- Tooltip -->
            <span
                :class="[
                    'pointer-events-none absolute z-10 whitespace-nowrap rounded-lg bg-black/70 px-3 py-1.5 text-xs font-medium text-white opacity-0 backdrop-blur-sm group-hover:opacity-100',
                    tooltipClasses[position],
                ]"
                :style="tooltipStyle(item.id)"
            >
                {{ item.label }}
            </span>

            <button
                :style="{ transformOrigin: scaleOrigins[position] }"
                :class="[
                    'dock-btn flex size-11 cursor-pointer items-center justify-center rounded-xl transition-[color,background-color,box-shadow,transform] duration-300',
                    activeItem === item.id
                        ? 'bg-primary/20 text-primary shadow-[0_0_14px_2px] shadow-primary/30'
                        : 'text-white/60 hover:bg-white/10 hover:text-white',
                ]"
                @click="emit('select', item.id)"
            >
                <component :is="item.icon" :class="[iconSize, 'shrink-0']" />
            </button>
        </div>
    </nav>
</template>

<style scoped>
.dock-btn:hover {
    transform: scale(1.5);
}
</style>
