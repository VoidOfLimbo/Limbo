<script setup lang="ts">
import { inject, computed } from 'vue'
import { ROADMAP_LAYOUT_KEY, addDays } from '@/composables/planner/useRoadmapLayout'

const layout = inject(ROADMAP_LAYOUT_KEY)!

interface HeaderSegment {
    label: string
    left: number
    width: number
}

// ── Major segments ─────────────────────────────────────────────────────────────

const majorSegments = computed((): HeaderSegment[] => {
    const segments: HeaderSegment[] = []
    const start = layout.viewStart.value
    const end = layout.viewEnd.value

    if (layout.zoom.value === 'day' || layout.zoom.value === 'week') {
        // Major = months
        let d = new Date(start.getFullYear(), start.getMonth(), 1)
        while (d < end) {
            const next = new Date(d.getFullYear(), d.getMonth() + 1, 1)
            const segLeft = layout.dateToX(d < start ? start : d)
            const segRight = layout.dateToX(next > end ? end : next)
            if (segRight > segLeft) {
                segments.push({
                    label: new Intl.DateTimeFormat('en', { month: 'short', year: 'numeric' }).format(d),
                    left: segLeft,
                    width: segRight - segLeft,
                })
            }
            d = next
        }
    } else {
        // Major = years
        let d = new Date(start.getFullYear(), 0, 1)
        while (d < end) {
            const next = new Date(d.getFullYear() + 1, 0, 1)
            const segLeft = layout.dateToX(d < start ? start : d)
            const segRight = layout.dateToX(next > end ? end : next)
            if (segRight > segLeft) {
                segments.push({
                    label: String(d.getFullYear()),
                    left: segLeft,
                    width: segRight - segLeft,
                })
            }
            d = next
        }
    }
    return segments
})

// ── Minor segments ─────────────────────────────────────────────────────────────

interface MinorSegment extends HeaderSegment {
    isToday?: boolean
    isWeekend?: boolean
}

function isSameDay(a: Date, b: Date): boolean {
    return a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate()
}

function getISOWeek(d: Date): number {
    const jan4 = new Date(d.getFullYear(), 0, 4)
    const startOfWeek1 = new Date(jan4.getTime() - ((jan4.getDay() + 6) % 7) * 86_400_000)
    return Math.floor((d.getTime() - startOfWeek1.getTime()) / (7 * 86_400_000)) + 1
}

const today = new Date()

const minorSegments = computed((): MinorSegment[] => {
    const segments: MinorSegment[] = []
    const start = layout.viewStart.value
    const end = layout.viewEnd.value

    if (layout.zoom.value === 'day') {
        let d = new Date(start.getFullYear(), start.getMonth(), start.getDate())
        while (d < end) {
            const next = addDays(d, 1)
            segments.push({
                label: String(d.getDate()),
                left: layout.dateToX(d),
                width: layout.dateToX(next) - layout.dateToX(d),
                isToday: isSameDay(d, today),
                isWeekend: d.getDay() === 0 || d.getDay() === 6,
            })
            d = next
        }
    } else if (layout.zoom.value === 'week') {
        // Start from the Monday on/before viewStart
        const startDay = new Date(start.getFullYear(), start.getMonth(), start.getDate())
        const offset = (startDay.getDay() + 6) % 7 // days since Monday
        let d = addDays(startDay, -offset)
        while (d < end) {
            const next = addDays(d, 7)
            const segLeft = layout.dateToX(d < start ? start : d)
            const segRight = layout.dateToX(next)
            if (segRight > segLeft) {
                segments.push({
                    label: `W${getISOWeek(d)}`,
                    left: segLeft,
                    width: segRight - segLeft,
                })
            }
            d = next
        }
    } else if (layout.zoom.value === 'month') {
        let d = new Date(start.getFullYear(), start.getMonth(), 1)
        while (d < end) {
            const next = new Date(d.getFullYear(), d.getMonth() + 1, 1)
            segments.push({
                label: new Intl.DateTimeFormat('en', { month: 'short' }).format(d),
                left: layout.dateToX(d),
                width: layout.dateToX(next) - layout.dateToX(d),
            })
            d = next
        }
    } else {
        // quarter
        const startQ = Math.floor(start.getMonth() / 3)
        let d = new Date(start.getFullYear(), startQ * 3, 1)
        while (d < end) {
            const next = new Date(d.getFullYear(), d.getMonth() + 3, 1)
            const q = Math.floor(d.getMonth() / 3) + 1
            segments.push({
                label: `Q${q}`,
                left: layout.dateToX(d),
                width: layout.dateToX(next) - layout.dateToX(d),
            })
            d = next
        }
    }
    return segments
})
</script>

<template>
    <div
        class="sticky top-0 z-20 border-b border-border bg-background select-none"
        :style="{ width: `${layout.totalWidth.value}px`, minWidth: '100%' }"
    >
        <!-- Major row -->
        <div class="relative h-6 border-b border-border/50">
            <div
                v-for="seg in majorSegments"
                :key="seg.left"
                class="absolute top-0 bottom-0 flex items-center border-r border-border/30 overflow-hidden"
                :style="{ left: `${seg.left}px`, width: `${seg.width}px` }"
            >
                <span class="px-2 text-[10px] font-semibold text-muted-foreground uppercase tracking-wide truncate">
                    {{ seg.label }}
                </span>
            </div>
        </div>

        <!-- Minor row -->
        <div class="relative h-7">
            <div
                v-for="seg in minorSegments"
                :key="seg.left"
                class="absolute top-0 bottom-0 flex items-center justify-center border-r border-border/20 overflow-hidden"
                :class="[
                    seg.isToday ? 'bg-primary/10 text-primary font-semibold' : '',
                    seg.isWeekend ? 'bg-muted/30 text-muted-foreground' : 'text-muted-foreground',
                ]"
                :style="{ left: `${seg.left}px`, width: `${seg.width}px` }"
            >
                <span class="text-[10px] truncate leading-none">{{ seg.label }}</span>
            </div>
        </div>
    </div>
</template>
