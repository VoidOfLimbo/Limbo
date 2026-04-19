<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import { VueDraggable } from 'vue-draggable-plus'
import PlannerBoardCard from '@/components/planner/PlannerBoardCard.vue'
import PlannerBoardAddCard from '@/components/planner/PlannerBoardAddCard.vue'
import type { PlannerEvent, EventStatus } from '@/types/planner'

const props = defineProps<{
    columnId: EventStatus
    label: string
    color: string
    events: PlannerEvent[]
    allEvents: PlannerEvent[]
}>()

const emit = defineEmits<{
    cardMoved: [eventId: string, newStatus: EventStatus]
    addCard: [title: string]
    edit: [event: PlannerEvent]
    snooze: [event: PlannerEvent]
    delete: [event: PlannerEvent]
    toggleStatus: [event: PlannerEvent]
    duplicate: [event: PlannerEvent]
}>()

const localEvents = ref([...props.events])
const isDragging = ref(false)

// Only sync from Inertia when not mid-drag, and defer to nextTick so
// SortableJS finishes its own DOM cleanup before Vue re-renders.
watch(
    () => props.events,
    (val) => {
        if (isDragging.value) return
        nextTick(() => { localEvents.value = [...val] })
    },
)

// @end fires on the SOURCE container before SortableJS cleanup runs.
// We read the destination column id from the data attribute we set on VueDraggable.
function onEnd(evt: any) {
    isDragging.value = false
    if (evt.from === evt.to) return // within-column reorder — v-model already handled it

    const eventId: string | null = evt.item?.getAttribute('data-id')
    const newStatus = evt.to?.getAttribute('data-column-id') as EventStatus | null
    if (eventId && newStatus) {
        emit('cardMoved', eventId, newStatus)
    }
}

function onStart() {
    isDragging.value = true
}
</script>

<template>
    <div class="flex flex-col w-64 shrink-0 rounded-lg border border-border bg-muted/30">
        <!-- Column header -->
        <div class="flex items-center justify-between px-3 py-2.5 border-b border-border">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium" :class="color">{{ label }}</span>
                <span class="text-xs text-muted-foreground bg-muted rounded-full px-1.5 py-0.5">
                    {{ events.length }}
                </span>
            </div>
        </div>

        <!-- Cards (draggable) -->
        <VueDraggable
            v-model="localEvents"
            class="flex flex-col gap-2 p-2 flex-1 overflow-y-auto min-h-[80px]"
            group="planner-board"
            ghost-class="opacity-40"
            animation="150"
            :data-column-id="columnId"
            @start="onStart"
            @end="onEnd"
        >
            <PlannerBoardCard
                v-for="event in localEvents"
                :key="event.id"
                :event="event"
                :data-id="event.id"
                @click="emit('edit', event)"
                @snooze="emit('snooze', event)"
                @delete="emit('delete', event)"
                @toggle-status="emit('toggleStatus', event)"
                @duplicate="emit('duplicate', event)"
            />
        </VueDraggable>

        <!-- Add card input -->
        <PlannerBoardAddCard @add="emit('addCard', $event)" />
    </div>
</template>
