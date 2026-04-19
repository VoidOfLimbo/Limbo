<script setup lang="ts">
import { computed } from 'vue'
import { Clock, BellOff, CheckCircle2, Circle, Pencil, Trash2, CheckSquare2, Flag } from 'lucide-vue-next'
import { Tooltip, TooltipTrigger, TooltipContent } from '@/components/ui/tooltip'
import { Button } from '@/components/ui/button'
import PlannerContextMenu from '@/components/planner/PlannerContextMenu.vue'
import type { PlannerEvent } from '@/types/planner'

const props = defineProps<{
    event: PlannerEvent
}>()

const emit = defineEmits<{
    edit: [event: PlannerEvent]
    snooze: [event: PlannerEvent]
    delete: [event: PlannerEvent]
    toggleStatus: [event: PlannerEvent]
    duplicate: [event: PlannerEvent]
}>()

const isCompleted = computed(() => props.event.status === 'completed')
const isSnoozed = computed(() => !!props.event.snoozed_until && new Date(props.event.snoozed_until) > new Date())

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
        weekday: 'short', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
    })
})

const tagsTooltip = computed(() => props.event.tags.map((t) => t.name).join(', '))
</script>

<template>
    <PlannerContextMenu
        :event="event"
        @edit="emit('edit', $event)"
        @snooze="emit('snooze', $event)"
        @delete="emit('delete', $event)"
        @toggle-status="emit('toggleStatus', $event)"
        @duplicate="emit('duplicate', $event)"
    >
        <div
            class="group flex items-center gap-3 px-4 py-2 hover:bg-accent/40 transition-colors cursor-default"
            :class="{ 'opacity-50': isSnoozed || event.status === 'cancelled' || event.status === 'skipped' }"
        >
            <!-- Status toggle -->
            <Tooltip>
                <TooltipTrigger as-child>
                    <button
                        class="shrink-0 text-muted-foreground hover:text-primary transition-colors focus-visible:outline-none"
                        @click="emit('toggleStatus', event)"
                    >
                        <CheckCircle2 v-if="isCompleted" class="size-3.5 text-green-500" />
                        <Circle v-else class="size-3.5" />
                    </button>
                </TooltipTrigger>
                <TooltipContent>{{ isCompleted ? 'Mark incomplete' : 'Mark complete' }}</TooltipContent>
            </Tooltip>

            <!-- Content -->
            <div class="flex-1 min-w-0 flex flex-col gap-0.5">
                <!-- Title row -->
                <div class="flex items-center gap-2 min-w-0">
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <span
                                class="size-1.5 rounded-full shrink-0 cursor-help"
                                :class="indicatorClass"
                                :style="indicatorStyle"
                            />
                        </TooltipTrigger>
                        <TooltipContent>Priority: {{ event.priority }}</TooltipContent>
                    </Tooltip>

                    <span
                        class="text-sm leading-snug truncate"
                        :class="{
                            'line-through text-muted-foreground': event.status === 'cancelled' || event.status === 'skipped',
                            'text-muted-foreground': isCompleted,
                        }"
                    >
                        {{ event.title }}
                    </span>

                    <Tooltip v-if="event.type !== 'event'">
                        <TooltipTrigger as-child>
                            <span class="shrink-0 text-muted-foreground/60">
                                <CheckSquare2 v-if="event.type === 'task'" class="size-3" />
                                <Flag v-else-if="event.type === 'milestone_marker'" class="size-3" />
                            </span>
                        </TooltipTrigger>
                        <TooltipContent>{{ event.type === 'task' ? 'Task' : 'Milestone marker' }}</TooltipContent>
                    </Tooltip>

                    <Tooltip v-if="isSnoozed">
                        <TooltipTrigger as-child>
                            <BellOff class="size-3 shrink-0 text-amber-500 cursor-help" />
                        </TooltipTrigger>
                        <TooltipContent>Snoozed until {{ snoozeLabel }}</TooltipContent>
                    </Tooltip>
                </div>

                <!-- Meta row -->
                <div class="flex items-center gap-3 flex-wrap text-xs text-muted-foreground">
                    <span v-if="dateRange" class="flex items-center gap-1">
                        <Clock class="size-3" />
                        {{ dateRange }}
                    </span>

                    <Tooltip v-if="event.tags.length">
                        <TooltipTrigger as-child>
                            <span class="flex items-center gap-1 cursor-help">
                                <span
                                    v-for="tag in event.tags.slice(0, 4)"
                                    :key="tag.id"
                                    class="size-1.5 rounded-full ring-1 ring-background"
                                    :style="tag.color ? { backgroundColor: tag.color } : { backgroundColor: 'currentColor' }"
                                />
                                <span v-if="event.tags.length > 4" class="text-[10px]">+{{ event.tags.length - 4 }}</span>
                            </span>
                        </TooltipTrigger>
                        <TooltipContent>{{ tagsTooltip }}</TooltipContent>
                    </Tooltip>
                </div>
            </div>

            <!-- Hover actions -->
            <div class="flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            class="size-6 text-muted-foreground hover:text-foreground"
                            @click="emit('edit', event)"
                        >
                            <Pencil class="size-3" />
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
                            <BellOff class="size-3" />
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
                            <Trash2 class="size-3" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Delete</TooltipContent>
                </Tooltip>
            </div>
        </div>
    </PlannerContextMenu>
</template>
