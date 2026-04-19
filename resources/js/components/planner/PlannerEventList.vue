<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import { VueDraggable } from 'vue-draggable-plus'
import { Skeleton } from '@/components/ui/skeleton'
import { Button } from '@/components/ui/button'
import PlannerEventRow from '@/components/planner/PlannerEventRow.vue'
import PlannerEmptyState from '@/components/planner/PlannerEmptyState.vue'
import { reorder as reorderEvents } from '@/actions/App/Http/Controllers/Planner/EventController'
import type { PaginatedData, PlannerEvent } from '@/types/planner'

const props = defineProps<{
    events: PaginatedData<PlannerEvent> | undefined
    showMilestone?: boolean
    loading?: boolean
}>()

const emit = defineEmits<{
    edit: [event: PlannerEvent]
    snooze: [event: PlannerEvent]
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
        if (isDragging.value) return
        nextTick(() => { localEvents.value = data ? [...data] : [] })
    },
    { immediate: true },
)

function onStart() {
    isDragging.value = true
}

function onEnd() {
    isDragging.value = false
    const ids = localEvents.value.map((e) => e.id)
    const def = reorderEvents()
    router.visit(def.url, {
        method: def.method,
        data: { ids },
        preserveScroll: true,
        preserveState: true,
        only: [],
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

        <!-- Event rows (draggable) -->
        <template v-else-if="events.data.length">
            <VueDraggable
                v-model="localEvents"
                handle=".drag-handle"
                ghost-class="opacity-30"
                animation="150"
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
                    @delete="emit('delete', $event)"
                    @toggle-status="emit('toggleStatus', $event)"
                    @duplicate="emit('duplicate', $event)"
                />
            </VueDraggable>

            <!-- Load more (pagination) -->
            <div v-if="events.next_page_url" class="flex justify-center py-4">
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="loading"
                    @click="emit('loadMore')"
                >
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
