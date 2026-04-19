<script setup lang="ts">
import { computed } from 'vue'
import { Clock, MapPin, BellOff, ChevronRight, CheckCircle2, Circle, AlertTriangle, Pencil, Trash2, CheckSquare2, Flag } from 'lucide-vue-next'
import { Tooltip, TooltipTrigger, TooltipContent } from '@/components/ui/tooltip'
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

// Single indicator dot: event color wins, otherwise priority color
const indicatorStyle = computed(() =>
    props.event.color ? { backgroundColor: props.event.color } : undefined,
)
const indicatorClass = computed(() => {
    if (props.event.color) return ''
    const map: Record<string, string> = {
        critical: 'bg-destructive',
        high: 'bg-orange-500',
        medium: 'bg-yellow-500',
        low: 'bg-muted-foreground/30',
    }
    return map[props.event.priority] ?? 'bg-muted-foreground/30'
})
const indicatorTooltip = computed(() => {
    const parts = [`Priority: ${props.event.priority}`]
    if (props.event.color) parts.push(`Color: ${props.event.color}`)
    return parts.join(' · ')
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
        weekday: 'short',
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

const tagsTooltip = computed(() => props.event.tags.map((t) => t.name).join(', '))
</script>

<template>
    <div
        class="group flex items-center gap-3 px-4 py-2.5 hover:bg-accent/40 transition-colors border-b border-border/50 cursor-default"
        :class="{ 'opacity-50': isSnoozed || event.status === 'cancelled' || event.status === 'skipped' }"
    >
        <!-- Status toggle -->
        <Tooltip>
            <TooltipTrigger as-child>
                <button
                    class="shrink-0 text-muted-foreground hover:text-primary transition-colors focus-visible:outline-none"
                    @click="emit('toggleStatus', event)"
                >
                    <CheckCircle2 v-if="isCompleted" class="size-4 text-green-500" />
                    <Circle v-else class="size-4" />
                </button>
            </TooltipTrigger>
            <TooltipContent>{{ isCompleted ? 'Mark incomplete' : 'Mark complete' }}</TooltipContent>
        </Tooltip>

        <!-- Content -->
        <div class="flex-1 min-w-0 flex flex-col gap-0.5">
            <!-- Title row -->
            <div class="flex items-center gap-2 min-w-0">
                <!-- Single indicator dot (color > priority) -->
                <Tooltip>
                    <TooltipTrigger as-child>
                        <span
                            class="size-2 rounded-full shrink-0 cursor-help"
                            :class="indicatorClass"
                            :style="indicatorStyle"
                        />
                    </TooltipTrigger>
                    <TooltipContent>{{ indicatorTooltip }}</TooltipContent>
                </Tooltip>

                <!-- Title -->
                <span
                    class="text-sm font-medium leading-snug truncate"
                    :class="{ 'line-through text-muted-foreground': event.status === 'cancelled' || event.status === 'skipped' }"
                >
                    {{ event.title }}
                </span>

                <!-- Type icon (task / milestone_marker only) -->
                <Tooltip v-if="event.type !== 'event'">
                    <TooltipTrigger as-child>
                        <span class="shrink-0 text-muted-foreground/60">
                            <CheckSquare2 v-if="event.type === 'task'" class="size-3.5" />
                            <Flag v-else-if="event.type === 'milestone_marker'" class="size-3.5" />
                        </span>
                    </TooltipTrigger>
                    <TooltipContent>{{ event.type === 'task' ? 'Task' : 'Milestone marker' }}</TooltipContent>
                </Tooltip>

                <!-- Breach icon -->
                <Tooltip v-if="isMilestoneBreached">
                    <TooltipTrigger as-child>
                        <AlertTriangle class="size-3.5 shrink-0 text-destructive cursor-help" />
                    </TooltipTrigger>
                    <TooltipContent>Exceeds the milestone's hard deadline</TooltipContent>
                </Tooltip>

                <!-- Snooze icon -->
                <Tooltip v-if="isSnoozed">
                    <TooltipTrigger as-child>
                        <BellOff class="size-3.5 shrink-0 text-amber-500 cursor-help" />
                    </TooltipTrigger>
                    <TooltipContent>Snoozed until {{ snoozeLabel }}</TooltipContent>
                </Tooltip>
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

                <!-- Tags: colored dots with tooltip -->
                <Tooltip v-if="event.tags.length">
                    <TooltipTrigger as-child>
                        <span class="flex items-center gap-1 cursor-help">
                            <span
                                v-for="tag in event.tags.slice(0, 5)"
                                :key="tag.id"
                                class="size-2 rounded-full ring-1 ring-background"
                                :style="tag.color ? { backgroundColor: tag.color } : { backgroundColor: 'currentColor' }"
                            />
                            <span v-if="event.tags.length > 5" class="text-[10px]">+{{ event.tags.length - 5 }}</span>
                        </span>
                    </TooltipTrigger>
                    <TooltipContent>{{ tagsTooltip }}</TooltipContent>
                </Tooltip>

                <!-- Children count -->
                <span v-if="event.children.length" class="flex items-center gap-0.5 text-[11px]">
                    <ChevronRight class="size-3" />
                    {{ event.children.length }} sub-task{{ event.children.length !== 1 ? 's' : '' }}
                </span>
            </div>
        </div>

        <!-- Actions (revealed on row hover) -->
        <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        class="size-6 text-muted-foreground hover:text-foreground"
                        @click="emit('edit', event)"
                    >
                        <Pencil class="size-3.5" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent>Edit</TooltipContent>
            </Tooltip>
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        class="size-6 text-muted-foreground hover:text-amber-500"
                        @click="emit('snooze', event)"
                    >
                        <BellOff class="size-3.5" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent>Snooze</TooltipContent>
            </Tooltip>
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        class="size-6 text-muted-foreground hover:text-destructive"
                        @click="emit('delete', event)"
                    >
                        <Trash2 class="size-3.5" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent>Delete</TooltipContent>
            </Tooltip>
        </div>
    </div>
</template>
