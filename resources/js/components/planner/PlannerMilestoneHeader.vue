<script setup lang="ts">
import { computed } from 'vue'
import { AlertTriangle, Clock, Edit2, Plus, Lock, CheckCircle2 } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Tooltip, TooltipTrigger, TooltipContent } from '@/components/ui/tooltip'
import type { PlannerMilestone } from '@/types/planner'

const props = defineProps<{
    milestone: PlannerMilestone
}>()

const emit = defineEmits<{
    edit: [milestone: PlannerMilestone]
    createEvent: []
}>()

const statusVariant = computed((): 'default' | 'secondary' | 'outline' | 'destructive' => {
    const map: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
        active: 'default',
        completed: 'secondary',
        paused: 'outline',
        cancelled: 'destructive',
    }
    return map[props.milestone.status] ?? 'outline'
})

const priorityClass = computed(() => {
    const map: Record<string, string> = {
        critical: 'text-destructive',
        high: 'text-orange-500',
        medium: 'text-yellow-500',
        low: 'text-muted-foreground',
    }
    return map[props.milestone.priority] ?? ''
})

const deadlineLabel = computed(() => {
    if (!props.milestone.end_at) return null
    const d = new Date(props.milestone.end_at)
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
})

const progressBarColor = computed(() => {
    if (props.milestone.is_breached) return 'bg-destructive'
    if (props.milestone.progress >= 100) return 'bg-green-500'
    return 'bg-primary'
})
</script>

<template>
    <div
        class="relative flex items-center gap-3 px-4 py-2.5 border-b border-border bg-card/50 shrink-0"
        :style="milestone.color ? { borderLeftColor: milestone.color, borderLeftWidth: '3px' } : {}"
    >
        <!-- Title + status + flags -->
        <div class="flex-1 min-w-0 flex items-center gap-2">
            <h2 class="text-sm font-semibold truncate">{{ milestone.title }}</h2>
            <Badge :variant="statusVariant" class="capitalize text-[10px] h-5 px-1.5 shrink-0">{{ milestone.status }}</Badge>

            <Tooltip v-if="milestone.deadline_type === 'hard'">
                <TooltipTrigger as-child>
                    <Lock class="size-3 shrink-0 text-muted-foreground/50 cursor-help" />
                </TooltipTrigger>
                <TooltipContent>Hard deadline — events cannot exceed this date</TooltipContent>
            </Tooltip>

            <Tooltip v-if="milestone.is_breached">
                <TooltipTrigger as-child>
                    <AlertTriangle class="size-3.5 shrink-0 text-destructive cursor-help" />
                </TooltipTrigger>
                <TooltipContent>An event exceeds this deadline. Adjust events or convert to a soft deadline.</TooltipContent>
            </Tooltip>
        </div>

        <!-- Stats -->
        <div class="flex items-center gap-3 shrink-0 text-xs text-muted-foreground">
            <Tooltip>
                <TooltipTrigger as-child>
                    <span
                        class="tabular-nums font-medium cursor-help w-8 text-right"
                        :class="{ 'text-destructive': milestone.is_breached }"
                    >
                        {{ milestone.progress }}%
                    </span>
                </TooltipTrigger>
                <TooltipContent>{{ milestone.progress_source === 'manual' ? 'Manual' : 'Derived' }} progress</TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <span class="flex items-center gap-1 cursor-help">
                        <CheckCircle2 class="size-3" />
                        {{ milestone.completed_events_count }}/{{ milestone.total_events_count }}
                    </span>
                </TooltipTrigger>
                <TooltipContent>{{ milestone.completed_events_count }} of {{ milestone.total_events_count }} events completed</TooltipContent>
            </Tooltip>

            <Tooltip v-if="deadlineLabel">
                <TooltipTrigger as-child>
                    <span
                        class="flex items-center gap-1 cursor-help"
                        :class="{ 'text-destructive font-medium': milestone.is_breached }"
                    >
                        <Clock class="size-3" />
                        {{ deadlineLabel }}
                    </span>
                </TooltipTrigger>
                <TooltipContent>{{ milestone.deadline_type === 'hard' ? 'Hard' : 'Soft' }} deadline</TooltipContent>
            </Tooltip>

            <Tooltip>
                <TooltipTrigger as-child>
                    <span class="capitalize cursor-help" :class="priorityClass">{{ milestone.priority }}</span>
                </TooltipTrigger>
                <TooltipContent>Priority</TooltipContent>
            </Tooltip>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-1 shrink-0">
            <Tooltip>
                <TooltipTrigger as-child>
                    <Button variant="ghost" size="icon-sm" @click="emit('edit', milestone)">
                        <Edit2 class="size-3.5" />
                    </Button>
                </TooltipTrigger>
                <TooltipContent>Edit milestone</TooltipContent>
            </Tooltip>
            <Button variant="outline" size="sm" class="h-7 gap-1 text-xs" @click="emit('createEvent')">
                <Plus class="size-3.5" />
                Add event
            </Button>
        </div>

        <!-- Progress strip at the very bottom -->
        <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary/10">
            <div
                class="h-full transition-all duration-500"
                :class="progressBarColor"
                :style="{ width: `${Math.min(milestone.progress, 100)}%` }"
            />
        </div>
    </div>

    <!-- Breach warning callout -->
    <div
        v-if="milestone.is_breached"
        class="flex items-center gap-2 px-4 py-2 bg-destructive/5 border-b border-destructive/20 text-destructive text-xs"
    >
        <AlertTriangle class="size-3.5 shrink-0" />
        <span class="flex-1">
            <span class="font-medium">{{ milestone.breach_count }} event{{ milestone.breach_count !== 1 ? 's' : '' }} exceed{{ milestone.breach_count === 1 ? 's' : '' }} this hard deadline.</span>
            Adjust event dates, move events to backlog, or convert this milestone to a soft deadline.
        </span>
        <Button
            variant="outline"
            size="sm"
            class="h-6 px-2 text-[11px] border-destructive/30 text-destructive hover:bg-destructive/10 hover:text-destructive hover:border-destructive/50 shrink-0"
            @click="emit('edit', milestone)"
        >
            Resolve
        </Button>
    </div>
</template>
