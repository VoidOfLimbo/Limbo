<script setup lang="ts">
import { inject, computed } from 'vue'
import { ROADMAP_LAYOUT_KEY, addDays } from '@/composables/planner/useRoadmapLayout'

defineProps<{
    totalRows: number
    rowHeight: number
}>()

const layout = inject(ROADMAP_LAYOUT_KEY)!

const today = new Date()

const todayX = computed(() => layout.dateToX(today))

const columnCount = computed(() => Math.ceil(layout.totalWidth.value / layout.columnWidth.value))

// Weekend bands (only relevant for day/week zoom)
const weekendBands = computed(() => {
    if (layout.zoom.value !== 'day' && layout.zoom.value !== 'week') return []

    const bands: { left: number; width: number }[] = []
    const start = layout.viewStart.value
    const end = layout.viewEnd.value

    let d = new Date(start.getFullYear(), start.getMonth(), start.getDate())
    while (d < end) {
        const day = d.getDay()
        if (day === 6) {
            const bandEnd = addDays(d, 2)
            bands.push({ left: layout.dateToX(d), width: layout.dateToX(bandEnd) - layout.dateToX(d) })
            d = addDays(d, 2)
        } else if (day === 0) {
            const bandEnd = addDays(d, 1)
            bands.push({ left: layout.dateToX(d), width: layout.dateToX(bandEnd) - layout.dateToX(d) })
            d = addDays(d, 1)
        } else {
            d = addDays(d, 1)
        }
    }
    return bands
})

const showTodayLine = computed(
    () => today >= layout.viewStart.value && today <= layout.viewEnd.value,
)
</script>

<template>
    <div
        class="absolute inset-0 pointer-events-none"
        :style="{ width: `${layout.totalWidth.value}px` }"
    >
        <!-- Weekend shading -->
        <div
            v-for="band in weekendBands"
            :key="band.left"
            class="absolute top-0 bottom-0 bg-muted/20"
            :style="{ left: `${band.left}px`, width: `${band.width}px` }"
        />

        <!-- Vertical column dividers -->
        <div
            v-for="i in columnCount"
            :key="`col-${i}`"
            class="absolute top-0 bottom-0 w-px bg-border/15"
            :style="{ left: `${i * layout.columnWidth.value}px` }"
        />

        <!-- Row dividers -->
        <div
            v-for="i in totalRows"
            :key="`row-${i}`"
            class="absolute left-0 right-0 h-px bg-border/20"
            :style="{ top: `${i * rowHeight}px` }"
        />

        <!-- Today vertical line -->
        <div
            v-if="showTodayLine"
            class="absolute top-0 bottom-0 w-0.5 bg-primary/70 z-10"
            :style="{ left: `${todayX}px` }"
        >
            <div class="absolute top-0 left-1/2 -translate-x-1/2 size-1.5 rounded-full bg-primary" />
        </div>
    </div>
</template>
