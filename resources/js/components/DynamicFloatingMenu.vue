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

function itemZoom(index: number): number {
    if (hoveredItem.value === null) {
        return 1;
    }

    const hi = props.items.findIndex((s) => s.id === hoveredItem.value);

    if (hi === -1) {
        return 1;
    }

    if (index === hi) {
        return 1.6;
    }

    if (Math.abs(index - hi) === 1) {
        return 1.25;
    }

    return 1;
}

const isVertical = computed(() => props.position === 'left' || props.position === 'right');

// Which edge to attach to + rounded corners on the open side
const edgeClasses: Record<Position, string> = {
    right: 'right-0 rounded-l-2xl',
    left: 'left-0 rounded-r-2xl',
    top: 'top-0 rounded-b-2xl',
    bottom: 'bottom-0 rounded-t-2xl',
};

// Alignment along the perpendicular axis
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

// Icon scale grows away from the edge (into the page)
// zoom on the wrapper is layout-affecting — siblings get pushed apart instead of overlapping
function itemStyle(index: number): Record<string, string> {
    const z = itemZoom(index);

    return { zoom: String(z) };
}

// Tooltip appears on the opposite side from the edge
const tooltipClasses: Record<Position, string> = {
    right: 'right-[calc(100%+24px)] top-1/2 -translate-y-1/2',
    left: 'left-[calc(100%+24px)] top-1/2 -translate-y-1/2',
    top: 'top-[calc(100%+20px)] left-1/2 -translate-x-1/2',
    bottom: 'bottom-[calc(100%+20px)] left-1/2 -translate-x-1/2',
};
</script>

<template>
    <nav
        :class="[
            'fixed z-40 hidden items-center gap-3 overflow-visible border border-white/10 bg-[rgba(83,83,83,0.4)] p-3 shadow-2xl shadow-black/20 backdrop-blur-[50px] lg:flex',
            positionClasses,
            flexDirectionClass,
            collapsible ? 'opacity-10 transition-opacity duration-300 hover:opacity-100' : '',
        ]"
    >
        <div
            v-for="(item, index) in items"
            :key="item.id"
            class="group relative size-11 transition-[zoom] duration-200"
            :style="itemStyle(index)"
            @mouseenter="hoveredItem = item.id"
            @mouseleave="hoveredItem = null"
        >
            <!-- Tooltip: anchored to the unscaled wrapper so it never shifts with zoom -->
            <span
                :class="[
                    'pointer-events-none absolute z-10 whitespace-nowrap rounded-lg bg-black/70 px-3 py-1.5 text-xs font-medium text-white opacity-0 backdrop-blur-sm transition-opacity duration-150 group-hover:opacity-100',
                    tooltipClasses[position],
                ]"
            >
                {{ item.label }}
            </span>

            <button
                :class="[
                    'flex size-11 cursor-pointer items-center justify-center rounded-xl transition-[color,background-color,box-shadow] duration-200',
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
