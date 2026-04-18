<script setup lang="ts">
import { computed } from 'vue'
import { Clock, MapPin, AlarmClock, ChevronRight, CheckCircle2, Circle, AlertTriangle } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import type { PlannerEvent } from '@/types/planner'

const props = defineProps<{
    event: PlannerEvent
    showMilestone?: boolean
}>()

const emit = defineEmits<{
    edit: [event: PlannerEvent]
    snooze: [event: PlannerEvent]
    delete: [event: PlannerEvent]
    toggleStatus: [event: PlannerEvent]
}>()

const isCompleted = computed(() => props.event.status === 'completed')
const isSnoozed = computed(() => !!props.event.snoozed_until && new Date(props.event.snoozed_until) > new Date())

const statusColor = computed(() => {
    const map: Record<string, string> = {
        draft: 'text-muted-foreground bg-muted border-muted',
        upcoming: 'text-blue-600 bg-blue-50 border-blue-200 dark:bg-blue-950 dark:border-blue-800',
        in_progress: 'text-amber-600 bg-amber-50 border-amber-200 dark:bg-amber-950 dark:border-amber-800',
        completed: 'text-green-600 bg-green-50 border-green-200 dark:bg-green-950 dark:border-green-800',
        cancelled: 'text-muted-foreground bg-muted border-muted line-through',
        skipped: 'text-muted-foreground bg-muted border-muted',
    }
    return map[props.event.status] ?? ''
})

const priorityDot = computed(() => {
    const map: Record<string, string> = {
        critical: 'bg-destructive',
        high: 'bg-orange-500',
        medium: 'bg-yellow-500',
        low: 'bg-muted-foreground/50',
    }
    return map[props.event.priority] ?? 'bg-muted-foreground/30'
})

const dateRange = computed(() => {
    if (!props.event.start_at) return null
    const fmt = (iso: string) =>
        new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
    if (props.event.is_all_day) {
        return props.event.end_at
            ? `${fmt(props.event.start_at)} – ${fmt(props.event.end_at)}`
            : fmt(props.event.start_at)
    }
    const time = (iso: string) =>
        new Date(iso).toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' })
    if (props.event.end_at) {
        return `${fmt(props.event.start_at)} ${time(props.event.start_at)} – ${time(props.event.end_at)}`
    }
    return `${fmt(props.event.start_at)} ${time(props.event.start_at)}`
})

const snoozeLabel = computed(() => {
    if (!props.event.snoozed_until) return null
    return new Date(props.event.snoozed_until).toLocaleDateString(undefined, {
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    })
})

const isMilestoneBreached = computed(() => {
    if (!props.event.milestone || !props.event.end_at || !props.event.milestone.end_at) return false
    return (
        props.event.milestone.deadline_type === 'hard' &&
        new Date(props.event.end_at) > new Date(props.event.milestone.end_at)
    )
})
</script>

<template>
    <div
        class="group flex items-start gap-3 px-4 py-3 hover:bg-accent/40 transition-colors border-b border-border/50 cursor-default"
        :class="{ 'opacity-60': isSnoozed || event.status === 'cancelled' || event.status === 'skipped' }"
    >
        <!-- Status toggle button -->
        <button
            class="mt-0.5 shrink-0 text-muted-foreground hover:text-primary transition-colors focus-visible:outline-none"
            :title="isCompleted ? 'Mark incomplete' : 'Mark complete'"
            @click="emit('toggleStatus', event)"
        >
            <CheckCircle2 v-if="isCompleted" class="size-4 text-green-500" />
            <Circle v-else class="size-4" />
        </button>

        <!-- Content -->
        <div class="flex-1 min-w-0 flex flex-col gap-1">
            <!-- Title row -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- Priority dot -->
                <span class="size-2 rounded-full shrink-0" :class="priorityDot" />

                <!-- Color dot if event has color -->
                <span
                    v-if="event.color"
                    class="size-2 rounded-full shrink-0"
                    :style="{ backgroundColor: event.color }"
                />

                <span
                    class="text-sm font-medium leading-snug"
                    :class="{ 'line-through text-muted-foreground': event.status === 'cancelled' || event.status === 'skipped' }"
                >
                    {{ event.title }}
                </span>

                <!-- Type badge for tasks -->
                <Badge v-if="event.type === 'task'" variant="outline" class="text-[10px] h-4 px-1.5">Task</Badge>

                <!-- Breach warning -->
                <span
                    v-if="isMilestoneBreached"
                    class="inline-flex items-center gap-0.5 text-[10px] text-destructive font-medium"
                    title="Exceeds hard milestone deadline"
                >
                    <AlertTriangle class="size-3" />
                    Breach
                </span>

                <!-- Snooze indicator -->
                <span
                    v-if="isSnoozed"
                    class="inline-flex items-center gap-0.5 text-[10px] text-amber-600 font-medium"
                    :title="`Snoozed until ${snoozeLabel}`"
                >
                    <AlarmClock class="size-3" />
                    Until {{ snoozeLabel }}
                </span>
            </div>

            <!-- Meta row -->
            <div class="flex items-center gap-3 flex-wrap text-xs text-muted-foreground">
                <!-- Date/time -->
                <span v-if="dateRange" class="flex items-center gap-1">
                    <Clock class="size-3" />
                    {{ dateRange }}
                </span>

                <!-- Location -->
                <span v-if="event.location" class="flex items-center gap-1 truncate max-w-[200px]">
                    <MapPin class="size-3 shrink-0" />
                    {{ event.location }}
                </span>

                <!-- Milestone label (Backlog view) -->
                <span v-if="showMilestone && event.milestone" class="flex items-center gap-1 text-[11px]">
                    <span class="text-muted-foreground/60">in</span>
                    {{ event.milestone.title }}
                </span>

                <!-- Tags -->
                <div v-if="event.tags.length" class="flex items-center gap-1 flex-wrap">
                    <span
                        v-for="tag in event.tags"
                        :key="tag.id"
                        class="px-1.5 py-0.5 rounded-full text-[10px] border border-border text-muted-foreground"
                        :style="tag.color ? { borderColor: tag.color, color: tag.color } : {}"
                    >
                        {{ tag.name }}
                    </span>
                </div>

                <!-- Children count -->
                <span v-if="event.children.length" class="flex items-center gap-0.5 text-[11px]">
                    <ChevronRight class="size-3" />
                    {{ event.children.length }} sub-task{{ event.children.length !== 1 ? 's' : '' }}
                </span>
            </div>
        </div>

        <!-- Actions (shown on hover) -->
        <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
            <Button
                variant="ghost"
                size="icon-sm"
                class="h-6 w-6 text-muted-foreground hover:text-foreground"
                title="Edit"
                @click="emit('edit', event)"
            >
                <span class="text-[10px]">Edit</span>
            </Button>
            <Button
                variant="ghost"
                size="icon-sm"
                class="h-6 w-6 text-muted-foreground hover:text-amber-500"
                title="Snooze"
                @click="emit('snooze', event)"
            >
                <AlarmClock class="size-3.5" />
            </Button>
            <Button
                variant="ghost"
                size="icon-sm"
                class="h-6 w-6 text-muted-foreground hover:text-destructive"
                title="Delete"
                @click="emit('delete', event)"
            >
                <span class="text-[10px]">Del</span>
            </Button>
        </div>
    </div>
</template>
