<script setup lang="ts">
import { Loader2 } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import PlannerEmptyState from '@/components/planner/PlannerEmptyState.vue'
import PlannerEventRow from '@/components/planner/PlannerEventRow.vue'
import { Button } from '@/components/ui/button'
import { Skeleton } from '@/components/ui/skeleton'
import type { PaginatedData, PlannerEvent } from '@/types/planner'

const props = defineProps<{
    events: PaginatedData<PlannerEvent> | undefined
    showMilestone?: boolean
    loading?: boolean
    columns?: 1 | 2 | 3 | 4
}>()

// Build rows: each entry is [event, globalIndex] across all columns.
// Rendering as rows (not columns) ensures equal height per row.
const rowChunks = computed(() => {
    const cols = props.columns ?? 1
    const data = props.events?.data ?? []
    if (cols <= 1 || !data.length) return null

    const rows: { event: PlannerEvent; globalIndex: number }[][] = []
    for (let i = 0; i < data.length; i += cols) {
        const row: { event: PlannerEvent; globalIndex: number }[] = []
        for (let c = 0; c < cols; c++) {
            if (i + c < data.length) row.push({ event: data[i + c], globalIndex: i + c })
        }
        rows.push(row)
    }
    return rows
})

const emit = defineEmits<{
    edit: [event: PlannerEvent]
    snooze: [event: PlannerEvent]
    moveToBacklog: [event: PlannerEvent]
    delete: [event: PlannerEvent]
    toggleStatus: [event: PlannerEvent]
    duplicate: [event: PlannerEvent]
    loadMore: []
}>()

// ── Selection & keyboard navigation ──────────────────────────────────────
const selectedId = ref<number | null>(null)

const allEvents = computed(() => props.events?.data ?? [])
const selectedIndex = computed(() => allEvents.value.findIndex((e) => e.id === selectedId.value))

// Clear selection when event list changes (e.g. filter/page change)
watch(() => props.events?.data, () => { selectedId.value = null })

function onKeydown(e: KeyboardEvent) {
    if (!['ArrowUp', 'ArrowDown', 'Enter', 'Escape'].includes(e.key)) return
    const total = allEvents.value.length
    if (!total) return
    e.preventDefault()
    if (e.key === 'ArrowDown') {
        const next = selectedIndex.value < total - 1 ? selectedIndex.value + 1 : 0
        selectedId.value = allEvents.value[next].id
    } else if (e.key === 'ArrowUp') {
        const prev = selectedIndex.value > 0 ? selectedIndex.value - 1 : total - 1
        selectedId.value = allEvents.value[prev].id
    } else if (e.key === 'Enter' && selectedIndex.value >= 0) {
        emit('edit', allEvents.value[selectedIndex.value])
    } else if (e.key === 'Escape') {
        selectedId.value = null
    }
}
</script>

<template>
    <div class="flex flex-col flex-1 overflow-y-auto focus:outline-none" tabindex="0" @keydown="onKeydown">
        <!-- Skeleton state (deferred props loading) -->
        <template v-if="events === undefined">
            <div v-for="i in 6" :key="i" class="flex items-start gap-3 px-4 py-3 border-b border-border/50">
                <Skeleton class="size-4 rounded-full mt-0.5 shrink-0" />
                <div class="flex-1 space-y-2">
                    <Skeleton class="h-3.5 w-3/4 rounded" />
                    <Skeleton class="h-3 w-1/2 rounded" />
                </div>
            </div>
        </template>

        <!-- Event rows -->
        <template v-else-if="events.data.length">
            <!-- Single-column -->
            <div v-if="!rowChunks" class="flex flex-col">
                <PlannerEventRow
                    v-for="(event, i) in events.data"
                    :key="event.id"
                    :event="event"
                    :row-number="i + 1"
                    :show-milestone="showMilestone"
                    :is-selected="selectedId === event.id"
                    @select="selectedId = $event.id"
                    @edit="emit('edit', $event)"
                    @snooze="emit('snooze', $event)"
                    @move-to-backlog="emit('moveToBacklog', $event)"
                    @delete="emit('delete', $event)"
                    @toggle-status="emit('toggleStatus', $event)"
                    @duplicate="emit('duplicate', $event)"
                />
            </div>

            <!-- Multi-column: rows rendered horizontally so height equalises per row -->
            <div v-else class="flex flex-col">
                <div
                    v-for="(row, ri) in rowChunks"
                    :key="ri"
                    class="grid"
                    :class="{
                        'grid-cols-2': (columns ?? 1) === 2,
                        'grid-cols-3': (columns ?? 1) === 3,
                        'grid-cols-4': (columns ?? 1) === 4,
                    }"
                >
                    <PlannerEventRow
                        v-for="cell in row"
                        :key="cell.event.id"
                        :event="cell.event"
                        :row-number="cell.globalIndex + 1"
                        :show-milestone="showMilestone"
                        :is-selected="selectedId === cell.event.id"
                        class="border-r border-border/50 last:border-r-0"
                        @select="selectedId = $event.id"
                        @edit="emit('edit', $event)"
                        @snooze="emit('snooze', $event)"
                        @move-to-backlog="emit('moveToBacklog', $event)"
                        @delete="emit('delete', $event)"
                        @toggle-status="emit('toggleStatus', $event)"
                        @duplicate="emit('duplicate', $event)"
                    />
                </div>
            </div>

            <!-- Load more (pagination) -->
            <div v-if="events.next_page_url" class="flex justify-center py-4">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="loading"
                    @click="emit('loadMore')"
                >
                    <Loader2 v-if="loading" class="size-3.5 animate-spin" />
                    {{ loading ? 'Loading…' : 'Load more' }}
                </Button>
            </div>

            <div v-else class="py-4 text-center text-xs text-muted-foreground">
                {{ events.total }} event{{ events.total !== 1 ? 's' : '' }} total
            </div>
        </template>

        <!-- Empty state -->
        <PlannerEmptyState
            v-else
            title="No events yet"
            description="Add your first event to get started."
        />
    </div>
</template>
