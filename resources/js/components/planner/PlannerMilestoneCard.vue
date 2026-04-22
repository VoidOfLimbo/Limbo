<script setup lang="ts">
import { computed } from 'vue'
import { AlertTriangle, Lock, CheckCircle2 } from 'lucide-vue-next'
import { Badge } from '@/components/ui/badge'
import type { PlannerMilestone } from '@/types/planner'

const props = defineProps<{
    milestone: PlannerMilestone
    active?: boolean
}>()

const emit = defineEmits<{
    select: [milestone: PlannerMilestone]
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

const priorityClass = computed(() => ({
    critical: 'text-destructive',
    high: 'text-orange-500',
    medium: 'text-yellow-500',
    low: 'text-muted-foreground',
}[props.milestone.priority] ?? 'text-muted-foreground'))

const progressBarColor = computed(() => {
    if (props.milestone.is_breached) return 'bg-destructive'
    if (props.milestone.progress >= 100) return 'bg-green-500'
    return 'bg-primary'
})

function formatDate(d: string | null | undefined): string {
    if (!d) return '—'
    return new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<template>
    <button
        type="button"
        class="group relative w-full text-left rounded-xl border bg-card transition-all duration-150
               hover:shadow-md hover:border-primary/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring overflow-hidden"
        :class="active ? 'border-primary/60 shadow-sm' : 'border-border'"
        :style="milestone.color
            ? { borderLeftColor: milestone.color, borderLeftWidth: '4px' }
            : {}"
        @click="emit('select', milestone)"
    >
        <!-- subtle color tint overlay -->
        <div
            v-if="milestone.color"
            class="absolute inset-0 pointer-events-none rounded-xl"
            :style="{ backgroundColor: milestone.color + '12' }"
        />
        <div class="p-4 flex flex-col gap-3">
            <!-- Header: title + badges -->
            <div class="flex items-start gap-2.5 min-w-0">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-sm font-semibold leading-snug truncate max-w-45">{{ milestone.title }}</span>
                        <AlertTriangle v-if="milestone.is_breached" class="size-3 text-destructive shrink-0" />
                        <Lock v-if="milestone.deadline_type === 'hard'" class="size-3 text-muted-foreground/50 shrink-0" />
                    </div>
                    <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                        <Badge :variant="statusVariant" class="capitalize text-[10px] h-4 px-1.5">{{ milestone.status }}</Badge>
                        <span class="text-[10px]" :class="priorityClass">{{ milestone.priority }}</span>
                    </div>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="space-y-1">
                <div class="flex items-center justify-between text-[10px] text-muted-foreground">
                    <span>{{ milestone.completed_events_count }}/{{ milestone.total_events_count }} events</span>
                    <span class="tabular-nums">{{ milestone.progress }}%</span>
                </div>
                <div class="h-1.5 rounded-full bg-border overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="progressBarColor"
                        :style="{ width: `${Math.min(milestone.progress, 100)}%` }"
                    />
                </div>
            </div>

            <!-- Dates + status counts -->
            <div class="flex items-center justify-between text-[10px] text-muted-foreground">
                <span v-if="milestone.end_at">
                    ends {{ formatDate(milestone.end_at) }}
                </span>
                <span v-else-if="milestone.start_at">
                    starts {{ formatDate(milestone.start_at) }}
                </span>
                <span v-else>No dates set</span>

                <div class="flex items-center gap-1.5">
                    <span v-if="milestone.in_progress_events_count" class="flex items-center gap-0.5">
                        <span class="size-1.5 rounded-full bg-violet-500 inline-block" />
                        {{ milestone.in_progress_events_count }}
                    </span>
                    <span v-if="milestone.upcoming_events_count" class="flex items-center gap-0.5">
                        <span class="size-1.5 rounded-full bg-blue-500 inline-block" />
                        {{ milestone.upcoming_events_count }}
                    </span>
                    <CheckCircle2 v-if="milestone.progress >= 100" class="size-3 text-green-500" />
                </div>
            </div>
        </div>
    </button>
</template>
