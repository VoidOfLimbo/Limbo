<script setup lang="ts">
import { computed, provide, ref, nextTick, watch, onMounted } from 'vue'
import {
    useRoadmapLayout,
    ROADMAP_LAYOUT_KEY,
    type ZoomLevel,
} from '@/composables/planner/useRoadmapLayout'
import type { PaginatedData, PlannerEvent, PlannerMilestone } from '@/types/planner'
import PlannerRoadmapToolbar from '@/components/planner/PlannerRoadmapToolbar.vue'
import PlannerRoadmapSidebar, { type RoadmapRow } from '@/components/planner/PlannerRoadmapSidebar.vue'
import PlannerTimelineHeader from '@/components/planner/PlannerTimelineHeader.vue'
import PlannerTimelineGrid from '@/components/planner/PlannerTimelineGrid.vue'
import PlannerTimelineBar from '@/components/planner/PlannerTimelineBar.vue'
import PlannerEmptyState from '@/components/planner/PlannerEmptyState.vue'

const props = defineProps<{
    milestones: PlannerMilestone[]
    events: PaginatedData<PlannerEvent> | undefined
    activeMilestoneId: string | null
}>()

const emit = defineEmits<{
    createEvent: []
    reschedule: [id: string, kind: 'milestone' | 'event', newStart: string, newEnd: string]
}>()

// ── Row height (px) ───────────────────────────────────────────────────────────

const ROW_HEIGHT = 44

// ── View state ────────────────────────────────────────────────────────────────

const STORAGE_ZOOM_KEY = 'planner:roadmapZoom'
const VALID_ZOOMS: ZoomLevel[] = ['week', 'month', 'quarter', 'year']
const storedZoom = typeof localStorage !== 'undefined' ? localStorage.getItem(STORAGE_ZOOM_KEY) : null
const zoom = ref<ZoomLevel>(VALID_ZOOMS.includes(storedZoom as ZoomLevel) ? (storedZoom as ZoomLevel) : 'month')
const showDependencies = ref(false)
const expandedMilestoneIds = ref<Set<string>>(new Set())

function setZoom(z: ZoomLevel) {
    zoom.value = z
    if (typeof localStorage !== 'undefined') localStorage.setItem(STORAGE_ZOOM_KEY, z)
}

function toggleMilestone(milestoneId: string) {
    const updated = new Set(expandedMilestoneIds.value)
    if (updated.has(milestoneId)) {
        updated.delete(milestoneId)
    } else {
        updated.add(milestoneId)
    }
    expandedMilestoneIds.value = updated
}

// ── Local stable copies (never wiped while deferred props reload) ────────────
// props.events becomes `undefined` between plannerVisit calls (deferred loading).
// We hold onto the last known data so bars never disappear during server round-trips.

const localMilestones = ref<PlannerMilestone[]>([...props.milestones])
const localEvents = ref<PlannerEvent[]>(props.events?.data ?? [])

watch(
    () => props.milestones,
    (val) => {
        // Merge by ID — update properties in-place, preserve display order, append new items.
        // This prevents rows from jumping around when a reschedule response returns.
        const newMap = new Map(val.map((m) => [m.id, m]))
        const kept = localMilestones.value.filter((m) => newMap.has(m.id)).map((m) => newMap.get(m.id)!)
        const existingIds = new Set(localMilestones.value.map((m) => m.id))
        for (const m of val) {
            if (!existingIds.has(m.id)) kept.push(m)
        }
        localMilestones.value = kept
    },
)

// Clear stale events immediately when navigating to a different milestone.
// props.events passes through undefined (deferred) before the new data arrives,
// and the watcher below skips undefined — so without this guard, old events
// from the previous milestone stay visible until the new batch loads.
watch(
    () => props.activeMilestoneId,
    () => { localEvents.value = [] },
)

watch(
    () => props.events,
    (val) => {
        // Only update when we actually have data — skip the undefined deferred state
        if (!val) return
        const newMap = new Map(val.data.map((e) => [e.id, e]))
        const kept = localEvents.value.filter((e) => newMap.has(e.id)).map((e) => newMap.get(e.id)!)
        const existingIds = new Set(localEvents.value.map((e) => e.id))
        for (const e of val.data) {
            if (!existingIds.has(e.id)) kept.push(e)
        }
        localEvents.value = kept
    },
)

// ── Data ──────────────────────────────────────────────────────────────────────

const eventsByMilestone = computed(() => {
    const map = new Map<string | null, PlannerEvent[]>()
    for (const event of localEvents.value) {
        const key = event.milestone_id ?? null
        if (!map.has(key)) map.set(key, [])
        map.get(key)!.push(event)
    }
    return map
})

const allItems = computed(() => [
    ...visibleMilestones.value.filter((m) => m.start_at || m.end_at),
    ...localEvents.value.filter((e) => e.start_at || e.end_at),
])

// ── Layout (provided to all child bars) ──────────────────────────────────────

const layout = useRoadmapLayout(zoom, allItems)
provide(ROADMAP_LAYOUT_KEY, layout)

// ── Flat row list ─────────────────────────────────────────────────────────────

// When inside a specific milestone context, only that milestone's row (and its
// events) should appear. The full list is still needed for allItems (layout
// bounds) so we keep localMilestones unfiltered and only slice here.
const visibleMilestones = computed(() =>
    props.activeMilestoneId
        ? localMilestones.value.filter((m) => m.id === props.activeMilestoneId)
        : localMilestones.value,
)

const rows = computed((): RoadmapRow[] => {
    const result: RoadmapRow[] = []

    for (const milestone of visibleMilestones.value) {
        result.push({ kind: 'milestone', milestone })
        if (expandedMilestoneIds.value.has(milestone.id)) {
            for (const event of eventsByMilestone.value.get(milestone.id) ?? []) {
                result.push({ kind: 'event', event })
            }
        }
    }

    // Backlog events (no milestone) — only shown when not in a milestone context
    if (!props.activeMilestoneId) {
        for (const event of eventsByMilestone.value.get(null) ?? []) {
            result.push({ kind: 'event', event })
        }
    }

    return result
})

// ── Scroll sync ───────────────────────────────────────────────────────────────

const sidebarScrollEl = ref<HTMLElement | null>(null)
const timelineScrollEl = ref<HTMLElement | null>(null)
let syncing = false

function onSidebarScroll() {
    if (syncing || !timelineScrollEl.value || !sidebarScrollEl.value) return
    syncing = true
    timelineScrollEl.value.scrollTop = sidebarScrollEl.value.scrollTop
    syncing = false
}

function onTimelineScroll() {
    if (syncing || !sidebarScrollEl.value || !timelineScrollEl.value) return
    syncing = true
    sidebarScrollEl.value.scrollTop = timelineScrollEl.value.scrollTop
    syncing = false
}

// ── Scroll to today ───────────────────────────────────────────────────────────

async function scrollToToday() {
    await nextTick()
    if (!timelineScrollEl.value) return
    const todayX = layout.dateToX(new Date())
    const containerWidth = timelineScrollEl.value.clientWidth
    timelineScrollEl.value.scrollLeft = Math.max(0, todayX - containerWidth / 2)
}

// Auto-scroll to today on first load once the layout has data (totalWidth > 0).
// On hard refresh, events is a deferred prop so totalWidth starts at 0; we watch
// for the first non-zero value instead of calling scrollToToday in onMounted.
let scrolledOnce = false
watch(
    () => layout.totalWidth.value,
    (width) => {
        if (!scrolledOnce && width > 0) {
            scrolledOnce = true
            scrollToToday()
        }
    },
)

onMounted(() => {
    // If milestones already have dates (no deferred wait needed), scroll immediately
    if (layout.totalWidth.value > 0) {
        scrolledOnce = true
        scrollToToday()
    }
})

// ── Reschedule (drag/resize) ──────────────────────────────────────────────────
// Optimistically update local state so bars never flicker, then emit to parent.

function handleReschedule(id: string, kind: 'milestone' | 'event', newStart: string, newEnd: string) {
    // Apply optimistic update immediately so the bar moves without waiting for server
    if (kind === 'milestone') {
        localMilestones.value = localMilestones.value.map((m) => {
            if (m.id !== id) return m
            // Hard-deadline: end_at is immutable — don't update it optimistically
            const endAt = m.deadline_type === 'hard' && m.end_at !== null ? m.end_at : newEnd
            return { ...m, start_at: newStart, end_at: endAt }
        })
    } else {
        localEvents.value = localEvents.value.map((e) =>
            e.id === id ? { ...e, start_at: newStart, end_at: newEnd } : e,
        )
    }
    emit('reschedule', id, kind, newStart, newEnd)
}

const hasAnyDates = computed(() => allItems.value.length > 0 || localMilestones.value.length > 0)
</script>

<template>
    <div class="flex flex-col h-full overflow-hidden">
        <!-- Toolbar -->
        <PlannerRoadmapToolbar
            :zoom="zoom"
            :show-dependencies="showDependencies"
            @update:zoom="setZoom"
            @update:show-dependencies="showDependencies = $event"
            @scroll-to-today="scrollToToday"
        />

        <!-- Empty state -->
        <PlannerEmptyState
            v-if="!hasAnyDates"
            title="No dates set"
            description="Add start and end dates to milestones or events to see them on the roadmap."
            action-label="Create event"
            @action="emit('createEvent')"
        />

        <!-- Canvas: sidebar + timeline -->
        <div v-else class="flex flex-1 min-h-0 overflow-hidden">
            <!-- ── Left sidebar ─────────────────────────────────────────────── -->
            <div class="flex flex-col w-[260px] shrink-0 border-r border-border bg-background overflow-hidden">
                <!-- Header aligns with timeline header (52px = 6 major + 7 minor) -->
                <div class="h-[52px] shrink-0 border-b border-border flex items-end px-3 pb-2">
                    <span class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wide">Item</span>
                </div>

                <!-- Rows (scroll synced with timeline) -->
                <div
                    ref="sidebarScrollEl"
                    class="flex-1 overflow-y-auto overflow-x-hidden"
                    @scroll="onSidebarScroll"
                >
                    <PlannerRoadmapSidebar
                        :rows="rows"
                        :expanded-milestone-ids="expandedMilestoneIds"
                        :row-height="ROW_HEIGHT"
                        @toggle-milestone="toggleMilestone"
                    />
                </div>
            </div>

            <!-- ── Right timeline ──────────────────────────────────────────── -->
            <div
                ref="timelineScrollEl"
                class="flex-1 overflow-auto"
                @scroll="onTimelineScroll"
            >
                <div
                    class="relative inline-block min-w-full"
                    :style="{ width: `${layout.totalWidth.value}px` }"
                >
                    <!-- Sticky date header (52px total = 24px major + 28px minor) -->
                    <PlannerTimelineHeader />

                    <!-- Rows area -->
                    <div
                        class="relative"
                        :style="{
                            height: `${rows.length * ROW_HEIGHT}px`,
                            width: `${layout.totalWidth.value}px`,
                        }"
                    >
                        <!-- Background: grid lines + today line + weekend shading -->
                        <PlannerTimelineGrid
                            :total-rows="rows.length"
                            :row-height="ROW_HEIGHT"
                        />

                        <!-- Timeline bars -->
                        <div
                            v-for="(row, rowIndex) in rows"
                            :key="rowIndex"
                            class="absolute left-0"
                            :style="{
                                top: `${rowIndex * ROW_HEIGHT}px`,
                                height: `${ROW_HEIGHT}px`,
                                width: `${layout.totalWidth.value}px`,
                            }"
                        >
                            <PlannerTimelineBar
                                v-if="row.kind === 'milestone'"
                                :item="row.milestone"
                                kind="milestone"
                                :row-height="ROW_HEIGHT"
                                @reschedule="handleReschedule"
                                @click="() => {}"
                            />
                            <PlannerTimelineBar
                                v-else
                                :item="row.event"
                                kind="event"
                                :row-height="ROW_HEIGHT"
                                @reschedule="handleReschedule"
                                @click="() => {}"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
