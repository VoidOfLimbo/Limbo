<script setup lang="ts">
import { CalendarX2 } from 'lucide-vue-next'
import { Skeleton } from '@/components/ui/skeleton'
import { Button } from '@/components/ui/button'
import PlannerEventRow from '@/components/planner/PlannerEventRow.vue'
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
    loadMore: []
}>()
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
            <PlannerEventRow
                v-for="event in events.data"
                :key="event.id"
                :event="event"
                :show-milestone="showMilestone"
                @edit="emit('edit', $event)"
                @snooze="emit('snooze', $event)"
                @delete="emit('delete', $event)"
                @toggle-status="emit('toggleStatus', $event)"
            />

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
        <div v-else class="flex flex-col items-center justify-center gap-3 py-20 text-center px-4">
            <CalendarX2 class="size-10 text-muted-foreground/40" />
            <div class="space-y-1">
                <p class="text-sm font-medium text-muted-foreground">No events yet</p>
                <p class="text-xs text-muted-foreground/60">Add your first event to get started.</p>
            </div>
        </div>
    </div>
</template>
