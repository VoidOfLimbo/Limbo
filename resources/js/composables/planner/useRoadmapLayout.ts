import { type InjectionKey, type Ref, computed, type ComputedRef } from 'vue'

export type ZoomLevel = 'day' | 'week' | 'month' | 'quarter' | 'year'

export interface RoadmapItem {
    start_at: string | null
    end_at: string | null
}

export const COLUMN_WIDTH: Record<ZoomLevel, number> = {
    day: 40,
    week: 80,
    month: 120,
    quarter: 200,
    year: 320,
}

/** Average days per zoom unit — used for fractional pixel positioning */
const DAYS_PER_UNIT: Record<ZoomLevel, number> = {
    day: 1,
    week: 7,
    month: 30.4375,
    quarter: 91.3125,
    year: 365.25,
}

// ── Date helpers ──────────────────────────────────────────────────────────────

export function addDays(d: Date, n: number): Date {
    return new Date(d.getTime() + n * 86_400_000)
}

export function differenceInUnits(end: Date, start: Date, zoom: ZoomLevel): number {
    const diffMs = end.getTime() - start.getTime()
    const diffDays = diffMs / 86_400_000
    return diffDays / DAYS_PER_UNIT[zoom]
}

export function addUnits(date: Date, n: number, zoom: ZoomLevel): Date {
    return addDays(date, n * DAYS_PER_UNIT[zoom])
}

// ── Injection key for layout context ─────────────────────────────────────────

export interface RoadmapLayoutContext {
    columnWidth: ComputedRef<number>
    totalWidth: ComputedRef<number>
    viewStart: ComputedRef<Date>
    viewEnd: ComputedRef<Date>
    zoom: Ref<ZoomLevel>
    dateToX: (date: Date | string) => number
    xToDate: (x: number) => Date
    widthFromDuration: (start: Date | string, end: Date | string) => number
    pxPerDay: ComputedRef<number>
}

export const ROADMAP_LAYOUT_KEY: InjectionKey<RoadmapLayoutContext> = Symbol('roadmapLayout')

// ── Composable ────────────────────────────────────────────────────────────────

export function useRoadmapLayout(zoom: Ref<ZoomLevel>, items: Ref<RoadmapItem[]>): RoadmapLayoutContext {
    const columnWidth = computed(() => COLUMN_WIDTH[zoom.value])

    const pxPerDay = computed(() => columnWidth.value / DAYS_PER_UNIT[zoom.value])

    const today = new Date()

    const allTimestamps = computed(() => {
        const ts: number[] = []
        for (const item of items.value) {
            if (item.start_at) ts.push(new Date(item.start_at).getTime())
            if (item.end_at) ts.push(new Date(item.end_at).getTime())
        }
        return ts
    })

    const earliestDate = computed((): Date => {
        if (!allTimestamps.value.length) return addDays(today, -30)
        return new Date(Math.min(...allTimestamps.value))
    })

    const latestDate = computed((): Date => {
        if (!allTimestamps.value.length) return addDays(today, 30)
        return new Date(Math.max(...allTimestamps.value))
    })

    const viewStart = computed(() => addUnits(earliestDate.value, -3, zoom.value))
    const viewEnd = computed(() => addUnits(latestDate.value, 3, zoom.value))

    const totalWidth = computed(() =>
        Math.max(differenceInUnits(viewEnd.value, viewStart.value, zoom.value), 1) * columnWidth.value,
    )

    function dateToX(date: Date | string): number {
        const d = typeof date === 'string' ? new Date(date) : date
        return differenceInUnits(d, viewStart.value, zoom.value) * columnWidth.value
    }

    function xToDate(x: number): Date {
        return addUnits(viewStart.value, x / columnWidth.value, zoom.value)
    }

    function widthFromDuration(start: Date | string, end: Date | string): number {
        const s = typeof start === 'string' ? new Date(start) : start
        const e = typeof end === 'string' ? new Date(end) : end
        return Math.max(differenceInUnits(e, s, zoom.value) * columnWidth.value, columnWidth.value / 4)
    }

    return { columnWidth, totalWidth, viewStart, viewEnd, zoom, dateToX, xToDate, widthFromDuration, pxPerDay }
}
