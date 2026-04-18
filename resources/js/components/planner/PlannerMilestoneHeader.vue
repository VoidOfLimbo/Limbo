<script setup lang="ts">
import { computed } from 'vue'
import { AlertTriangle, Clock, Edit2, Plus, Lock } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
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
        class="flex flex-col gap-3 px-4 py-3 border-b border-border bg-card/50"
        :style="milestone.color ? { borderLeftColor: milestone.color, borderLeftWidth: '3px' } : {}"
    >
        <!-- Top row: title + actions -->
        <div class="flex items-start gap-3">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <h2 class="text-base font-semibold truncate">{{ milestone.title }}</h2>
                    <Badge :variant="statusVariant" class="capitalize text-[11px]">{{ milestone.status }}</Badge>
                    <span v-if="milestone.deadline_type === 'hard'" class="inline-flex items-center gap-1 text-[11px] text-muted-foreground">
                        <Lock class="size-3" />
                        Hard deadline
                    </span>
                </div>

                <!-- Breach warning -->
                <div
                    v-if="milestone.is_breached"
                    class="mt-1 flex items-center gap-1.5 text-xs text-destructive font-medium"
                >
                    <AlertTriangle class="size-3.5" />
                    An event exceeds the deadline — adjust events or convert to soft deadline.
                </div>
            </div>

            <div class="flex items-center gap-1 shrink-0">
                <Button variant="ghost" size="icon-sm" title="Edit milestone" @click="emit('edit', milestone)">
                    <Edit2 class="size-3.5" />
                </Button>
                <Button variant="outline" size="sm" class="gap-1.5 h-7" @click="emit('createEvent')">
                    <Plus class="size-3.5" />
                    Add event
                </Button>
            </div>
        </div>

        <!-- Progress + meta row -->
        <div class="flex items-center gap-4">
            <!-- Progress bar -->
            <div class="flex-1 flex items-center gap-2">
                <div class="flex-1 h-1.5 rounded-full bg-primary/10 overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="progressBarColor"
                        :style="{ width: `${Math.min(milestone.progress, 100)}%` }"
                    />
                </div>
                <span class="text-xs tabular-nums text-muted-foreground shrink-0 w-10 text-right">
                    {{ milestone.progress }}%
                </span>
            </div>

            <!-- Events count -->
            <span class="text-xs text-muted-foreground shrink-0">
                {{ milestone.completed_events_count }}/{{ milestone.total_events_count }} events
            </span>

            <!-- Deadline -->
            <div v-if="deadlineLabel" class="flex items-center gap-1 text-xs text-muted-foreground shrink-0">
                <Clock class="size-3" />
                <span :class="{ 'text-destructive font-medium': milestone.is_breached }">{{ deadlineLabel }}</span>
            </div>

            <!-- Priority -->
            <span class="text-xs capitalize shrink-0" :class="priorityClass">
                {{ milestone.priority }}
            </span>
        </div>
    </div>
</template>
