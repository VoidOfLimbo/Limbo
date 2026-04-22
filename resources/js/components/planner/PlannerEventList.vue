<script setup lang="ts">
import { Loader2 } from 'lucide-vue-next'
import { ref, watch, nextTick, computed } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import { reorder as reorderEvents } from '@/actions/App/Http/Controllers/Planner/EventController'
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

// Split events into N columns for multi-column layout
const columnChunks = computed(() => {
    const cols = props.columns ?? 1

    if (cols <= 1 || !localEvents.value.length) {
        return [localEvents.value]
    }

    const chunks: PlannerEvent[][] = Array.from({ length: cols }, () => [])

    localEvents.value.forEach((e, i) => chunks[i % cols].push(e))

    return chunks
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

// ── Drag-to-reorder ──────────────────────────────────────────────────────────
const localEvents = ref<PlannerEvent[]>([])
const isDragging = ref(false)

watch(
    () => props.events?.data,
    (data) => {
        if (isDragging.value) {
            return
        }

        nextTick(() => {
            localEvents.value = data ? [...data] : []
        })
    },
    { immediate: true },
)

function onStart() {
    isDragging.value = true
}

function onEnd() {
    isDragging.value = false
    const ids = localEvents.value.map((e) => e.id)
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? ''
    fetch(reorderEvents().url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ ids }),
    })
}
</script>

<template>
    <div class="flex flex-col flex-1 overflow-y-auto">
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
            <!-- Single-column: draggable -->
            <VueDraggable
                v-if="(columns ?? 1) === 1"
                v-model="localEvents"
                handle=".drag-handle"
                ghost-class="opacity-30"
                :animation="150"
                class="flex flex-col"
                @start="onStart"
                @end="onEnd"
            >
                <PlannerEventRow
                    v-for="event in localEvents"
                    :key="event.id"
                    :event="event"
                    :show-milestone="showMilestone"
                    @edit="emit('edit', $event)"
                    @snooze="emit('snooze', $event)"
                    @move-to-backlog="emit('moveToBacklog', $event)"
                    @delete="emit('delete', $event)"
                    @toggle-status="emit('toggleStatus', $event)"
                    @duplicate="emit('duplicate', $event)"
                />
            </VueDraggable>

            <!-- Multi-column grid -->
            <div
                v-else
                class="grid flex-1 items-start gap-x-0"
                :class="{
                    'grid-cols-2': columns === 2,
                    'grid-cols-3': columns === 3,
                    'grid-cols-4': columns === 4,
                }"
            >
                <div
                    v-for="(chunk, ci) in columnChunks"
                    :key="ci"
                    class="flex flex-col border-r border-border/50 last:border-r-0"
                >
                    <PlannerEventRow
                        v-for="event in chunk"
                        :key="event.id"
                        :event="event"
                        :show-milestone="showMilestone"
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
