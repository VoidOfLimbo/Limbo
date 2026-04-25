<script setup lang="ts">
import { inject, ref, computed, watch, onUnmounted } from 'vue'
import { ROADMAP_LAYOUT_KEY, addDays } from '@/composables/planner/useRoadmapLayout'
import type { PlannerMilestone, PlannerEvent } from '@/types/planner'

type BarItem = PlannerMilestone | PlannerEvent

const props = defineProps<{
    item: BarItem
    kind: 'milestone' | 'event'
    rowHeight: number
    isSelected?: boolean
}>()

const emit = defineEmits<{
    reschedule: [id: string, kind: 'milestone' | 'event', newStart: string, newEnd: string]
    click: [item: BarItem]
}>()

const layout = inject(ROADMAP_LAYOUT_KEY)!

const PADDING_Y = 6

// Hard-deadline milestones have an immutable end_at — block right-resize
const isRightResizeLocked = computed(() =>
    props.kind === 'milestone'
    && (props.item as PlannerMilestone).deadline_type === 'hard'
    && props.item.end_at !== null,
)

// ── Pending position (locks bar at snapped location until props update arrives) ──

const pendingStart = ref<string | null>(null)
const pendingEnd = ref<string | null>(null)

watch(
    () => [props.item.start_at, props.item.end_at] as const,
    () => {
        pendingStart.value = null
        pendingEnd.value = null
    },
)

const effectiveStart = computed(() => pendingStart.value ?? props.item.start_at)
const effectiveEnd = computed(() => pendingEnd.value ?? props.item.end_at)

const barLeft = computed(() => {
    if (!effectiveStart.value) return 0
    return layout.dateToX(effectiveStart.value)
})

const barWidth = computed(() => {
    if (!effectiveStart.value) return 20
    if (!effectiveEnd.value) return 20
    return layout.widthFromDuration(effectiveStart.value, effectiveEnd.value)
})

const barHeight = computed(() => props.rowHeight - PADDING_Y * 2)

const barTop = computed(() => PADDING_Y)

// Status-based colours for events (used when no explicit color is set)
const STATUS_COLORS: Record<string, string> = {
    draft:       'hsl(220 9% 46%)',
    upcoming:    'hsl(217 91% 60%)',
    in_progress: 'hsl(262 83% 58%)',
    completed:   'hsl(142 71% 45%)',
    cancelled:   'hsl(0 0% 45%)',
    skipped:     'hsl(220 9% 46%)',
}

const barColor = computed(() => {
    // Explicit hex color set by the user always wins
    if (props.item.color) return props.item.color
    if (props.kind === 'milestone') return null // falls back to --primary
    const status = (props.item as PlannerEvent).status
    return STATUS_COLORS[status] ?? null
})

// ── Drag state ────────────────────────────────────────────────────────────────

type DragMode = 'move' | 'resize-left' | 'resize-right'

const isDragging = ref(false)
const dragMode = ref<DragMode>('move')
const dragStartX = ref(0)
const dragOffsetX = ref(0)

function startInteraction(e: PointerEvent, mode: DragMode) {
    e.preventDefault()
    e.stopPropagation()

    if (!props.item.start_at || !props.item.end_at) return

    isDragging.value = true
    dragMode.value = mode
    dragStartX.value = e.clientX
    dragOffsetX.value = 0

    document.addEventListener('pointermove', onPointerMove)
    document.addEventListener('pointerup', onPointerUp, { once: true })
}

function onPointerMove(e: PointerEvent) {
    if (!isDragging.value) return
    dragOffsetX.value = e.clientX - dragStartX.value
}

function onPointerUp() {
    if (!isDragging.value) return

    document.removeEventListener('pointermove', onPointerMove)

    const delta = dragOffsetX.value

    // Reset drag visuals immediately
    isDragging.value = false
    dragOffsetX.value = 0

    if (Math.abs(delta) < 2 || !props.item.start_at || !props.item.end_at) return

    const deltaDays = Math.round(delta / layout.pxPerDay.value)
    if (deltaDays === 0) return

    const startDate = new Date(props.item.start_at)
    const endDate = new Date(props.item.end_at)

    let newStart: Date
    let newEnd: Date

    if (dragMode.value === 'move') {
        newStart = addDays(startDate, deltaDays)
        newEnd = addDays(endDate, deltaDays)
    } else if (dragMode.value === 'resize-left') {
        newStart = addDays(startDate, deltaDays)
        if (newStart >= endDate) newStart = addDays(endDate, -1)
        newEnd = endDate
    } else {
        newStart = startDate
        newEnd = addDays(endDate, deltaDays)
        if (newEnd <= startDate) newEnd = addDays(startDate, 1)
    }

    const newStartStr = newStart.toISOString().slice(0, 10)
    const newEndStr = newEnd.toISOString().slice(0, 10)

    // Lock bar at snapped position so there's no visual snap-back before the
    // parent's optimistic update arrives (watched above, clears pending on change)
    pendingStart.value = newStartStr
    pendingEnd.value = newEndStr

    emit('reschedule', props.item.id, props.kind, newStartStr, newEndStr)
}

onUnmounted(() => {
    document.removeEventListener('pointermove', onPointerMove)
})

// Visual transform during drag
const dragTransform = computed(() => {
    if (!isDragging.value || dragOffsetX.value === 0) return ''
    if (dragMode.value === 'move') return `translateX(${dragOffsetX.value}px)`
    return ''
})

const resizeLeftDelta = computed(() => {
    if (!isDragging.value || dragMode.value !== 'resize-left') return 0
    return dragOffsetX.value
})

const resizeRightDelta = computed(() => {
    if (!isDragging.value || dragMode.value !== 'resize-right') return 0
    return dragOffsetX.value
})
</script>

<template>
    <div
        v-if="item.start_at && item.end_at"
        class="absolute flex items-center group select-none"
        :style="{
            left: `${barLeft + resizeLeftDelta}px`,
            top: `${barTop}px`,
            width: `${barWidth - resizeLeftDelta + resizeRightDelta}px`,
            height: `${barHeight}px`,
            transform: dragTransform,
            zIndex: isDragging ? 10 : 1,
            cursor: isDragging ? 'grabbing' : 'grab',
            willChange: isDragging ? 'transform' : 'auto',
        }"
        @pointerdown="startInteraction($event, 'move')"
        @click.stop="emit('click', item)"
    >
        <!-- Bar body -->
        <div
            class="absolute inset-0 rounded-md flex items-center overflow-hidden transition-opacity"
            :class="[
                kind === 'milestone'
                    ? 'opacity-90 hover:opacity-100'
                    : 'opacity-80 hover:opacity-100',
                isDragging ? 'shadow-lg ring-2 ring-primary/30' : '',
                isSelected ? 'ring-2 ring-primary ring-offset-1 ring-offset-background opacity-100' : '',
            ]"
            :style="{
                backgroundColor: barColor ?? (kind === 'milestone' ? 'hsl(var(--primary))' : 'hsl(var(--primary) / 0.6)'),
            }"
        >
            <span
                class="px-2 text-[11px] font-medium text-white truncate pointer-events-none leading-none"
            >
                {{ item.title }}
            </span>
        </div>

        <!-- Left resize handle -->
        <div
            class="absolute left-0 top-0 bottom-0 w-2 cursor-ew-resize opacity-0 group-hover:opacity-100 transition-opacity z-10"
            @pointerdown.stop="startInteraction($event, 'resize-left')"
        >
            <div class="absolute left-0.5 top-1/2 -translate-y-1/2 w-0.5 h-4 rounded-full bg-white/60" />
        </div>

        <!-- Right resize handle (hidden for hard-deadline milestones) -->
        <div
            v-if="!isRightResizeLocked"
            class="absolute right-0 top-0 bottom-0 w-2 cursor-ew-resize opacity-0 group-hover:opacity-100 transition-opacity z-10"
            @pointerdown.stop="startInteraction($event, 'resize-right')"
        >
            <div class="absolute right-0.5 top-1/2 -translate-y-1/2 w-0.5 h-4 rounded-full bg-white/60" />
        </div>
    </div>

    <!-- Placeholder for items with no dates -->
    <div
        v-else
        class="absolute inset-y-1 flex items-center px-2 opacity-30 italic text-xs text-muted-foreground pointer-events-none"
        :style="{ left: `${layout.dateToX(new Date())}px` }"
    >
        No dates
    </div>
</template>
