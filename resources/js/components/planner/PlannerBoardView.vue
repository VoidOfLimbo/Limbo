<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { update as updateEvent, store as storeEvent } from '@/actions/App/Http/Controllers/Planner/EventController'
import PlannerBoardColumn from '@/components/planner/PlannerBoardColumn.vue'
import type { PlannerEvent, EventStatus } from '@/types/planner'

const props = defineProps<{
    events: PlannerEvent[]
    activeMilestoneId?: string | null
}>()

const emit = defineEmits<{
    edit: [event: PlannerEvent]
    snooze: [event: PlannerEvent]
    delete: [event: PlannerEvent]
    toggleStatus: [event: PlannerEvent]
    duplicate: [event: PlannerEvent]
}>()

interface BoardColumn {
    id: EventStatus
    label: string
    color: string
}

const COLUMNS: BoardColumn[] = [
    { id: 'draft', label: 'Draft', color: 'text-muted-foreground' },
    { id: 'upcoming', label: 'Upcoming', color: 'text-blue-500' },
    { id: 'in_progress', label: 'In Progress', color: 'text-violet-500' },
    { id: 'completed', label: 'Completed', color: 'text-green-500' },
    { id: 'cancelled', label: 'Cancelled', color: 'text-muted-foreground' },
    { id: 'skipped', label: 'Skipped', color: 'text-muted-foreground' },
]

const columnEvents = computed(() =>
    COLUMNS.map((col) => ({
        ...col,
        events: props.events.filter((e) => e.status === col.id),
    })),
)

function onCardMoved(eventId: string, newStatus: EventStatus) {
    const def = updateEvent(eventId)
    router.visit(def.url, {
        method: def.method,
        data: { status: newStatus },
        preserveScroll: true,
        only: ['events', 'milestones'],
    })
}

function onAddCard(status: EventStatus, title: string) {
    const def = storeEvent()
    router.visit(def.url, {
        method: def.method,
        data: {
            title,
            status,
            type: 'task',
            priority: 'medium',
            milestone_id: props.activeMilestoneId ?? undefined,
        },
        preserveScroll: true,
        only: ['events', 'milestones'],
    })
}
</script>

<template>
    <div class="flex-1 overflow-x-auto overflow-y-hidden">
        <div class="flex h-full gap-3 p-4 min-w-max">
            <PlannerBoardColumn
                v-for="col in columnEvents"
                :key="col.id"
                :column-id="col.id"
                :label="col.label"
                :color="col.color"
                :events="col.events"
                :all-events="events"
                @card-moved="onCardMoved"
                @add-card="(title) => onAddCard(col.id, title)"
                @edit="emit('edit', $event)"
                @snooze="emit('snooze', $event)"
                @delete="emit('delete', $event)"
                @toggle-status="emit('toggleStatus', $event)"
                @duplicate="emit('duplicate', $event)"
            />
        </div>
    </div>
</template>
