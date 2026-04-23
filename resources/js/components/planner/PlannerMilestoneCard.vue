<script setup lang="ts">
import { computed } from 'vue'
import { AlertTriangle, Lock } from 'lucide-vue-next'
import { Tooltip, TooltipTrigger, TooltipContent } from '@/components/ui/tooltip'
import type { PlannerMilestone } from '@/types/planner'

const props = defineProps<{
    milestone: PlannerMilestone
    active?: boolean
}>()

const emit = defineEmits<{
    select: [milestone: PlannerMilestone]
}>()

// ── Health score: progress vs time elapsed ────────────────────────────────────
// Returns 'healthy' | 'at-risk' | 'critical' | 'complete' | 'no-deadline'
const health = computed((): 'healthy' | 'at-risk' | 'critical' | 'complete' | 'no-deadline' => {
    const m = props.milestone
    if (m.progress >= 100) return 'complete'
    if (m.is_breached) return 'critical'
    if (!m.start_at || !m.end_at) return 'no-deadline'

    const start = new Date(m.start_at).getTime()
    const end = new Date(m.end_at).getTime()
    const now = Date.now()

    if (now > end) return 'critical' // past deadline and not complete

    const totalDuration = end - start
    if (totalDuration <= 0) return 'no-deadline'

    const timeElapsed = Math.max(0, now - start) / totalDuration // 0–1
    const progress = m.progress / 100 // 0–1

    const slack = progress - timeElapsed // positive = ahead, negative = behind

    if (slack >= -0.1) return 'healthy'      // on track or within 10% behind
    if (slack >= -0.3) return 'at-risk'      // 10–30% behind
    return 'critical'                         // >30% behind
})

const progressBarClass = computed(() => {
    const map: Record<string, string> = {
        complete:      'bg-green-500',
        healthy:       'bg-green-500',
        'at-risk':     'bg-yellow-500',
        critical:      'bg-destructive',
        'no-deadline': 'bg-primary',
    }
    return map[health.value] ?? 'bg-primary'
})

// ── Deadline urgency label ────────────────────────────────────────────────────
const daysUntilDeadline = computed((): number | null => {
    if (!props.milestone.end_at) return null
    const diff = new Date(props.milestone.end_at).getTime() - Date.now()
    return Math.ceil(diff / 86_400_000)
})

const deadlineLabel = computed((): { text: string; class: string } => {
    const d = daysUntilDeadline.value
    if (d === null) {
        if (props.milestone.start_at) {
            return { text: `starts ${formatDate(props.milestone.start_at)}`, class: 'text-muted-foreground' }
        }
        return { text: 'No dates set', class: 'text-muted-foreground/50' }
    }
    if (d < 0) return { text: `${Math.abs(d)}d overdue`, class: 'text-destructive font-medium' }
    if (d === 0) return { text: 'Due today', class: 'text-destructive font-medium' }
    if (d === 1) return { text: 'Due tomorrow', class: 'text-orange-500 font-medium' }
    if (d <= 7) return { text: `${d} days left`, class: 'text-orange-500 font-medium' }
    if (d <= 14) return { text: `${d} days left`, class: 'text-yellow-500' }
    return { text: `ends ${formatDate(props.milestone.end_at)}`, class: 'text-muted-foreground' }
})

// ── Stacked event-status bar segments ─────────────────────────────────────────
const eventBarSegments = computed(() => {
    const m = props.milestone
    const total = m.total_events_count
    if (total === 0) return []
    return [
        { key: 'completed', count: m.completed_events_count, color: 'bg-green-500', label: 'Completed' },
        { key: 'in_progress', count: m.in_progress_events_count, color: 'bg-violet-500', label: 'In progress' },
        { key: 'upcoming', count: m.upcoming_events_count, color: 'bg-blue-500', label: 'Upcoming' },
        { key: 'draft', count: m.draft_events_count, color: 'bg-muted-foreground/40', label: 'Draft' },
        { key: 'cancelled', count: m.cancelled_events_count + m.skipped_events_count, color: 'bg-muted-foreground/20', label: 'Cancelled/Skipped' },
    ].filter((s) => s.count > 0).map((s) => ({ ...s, pct: (s.count / total) * 100 }))
})

function formatDate(d: string | null | undefined): string {
    if (!d) return '—'
    return new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<template>
    <button
        type="button"
        class="group relative w-full text-left rounded-xl border bg-card shadow-sm transition-all duration-200 cursor-pointer
               hover:shadow-lg hover:-translate-y-0.5 hover:border-primary/40
               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring overflow-hidden"
        :class="active ? 'border-primary/60 shadow-md -translate-y-0.5' : 'border-border'"
        @click="emit('select', milestone)"
    >

        <div class="p-4 flex flex-col gap-3">
            <!-- Header: title + attention icons -->
            <div class="flex items-start justify-between gap-2 min-w-0">
                <span class="text-sm font-semibold leading-snug line-clamp-2 flex-1 min-w-0">{{ milestone.title }}</span>
                <div class="flex items-center gap-1 shrink-0 mt-0.5">
                    <Tooltip v-if="milestone.is_breached">
                        <TooltipTrigger as-child>
                            <AlertTriangle class="size-3.5 text-destructive shrink-0" />
                        </TooltipTrigger>
                        <TooltipContent>Events exceed milestone deadline</TooltipContent>
                    </Tooltip>
                    <Tooltip v-if="milestone.deadline_type === 'hard'">
                        <TooltipTrigger as-child>
                            <Lock class="size-3.5 text-muted-foreground/40 shrink-0" />
                        </TooltipTrigger>
                        <TooltipContent>Hard deadline — cannot be extended</TooltipContent>
                    </Tooltip>
                </div>
            </div>

            <!-- Progress bar (health-coloured) -->
            <div class="space-y-1">
                <div class="flex items-center justify-between text-[10px] text-muted-foreground">
                    <span>{{ milestone.completed_events_count }}/{{ milestone.total_events_count }} completed</span>
                    <span class="tabular-nums">{{ milestone.progress }}%</span>
                </div>
                <div class="h-1.5 rounded-full bg-border overflow-hidden">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="progressBarClass"
                        :style="{ width: `${Math.min(milestone.progress, 100)}%` }"
                    />
                </div>
            </div>

            <!-- Deadline urgency (always visible) -->
            <div class="flex items-center justify-between text-[10px]">
                <span :class="deadlineLabel.class">{{ deadlineLabel.text }}</span>

                <!-- Priority badge — only when not dominant context (on hover too) -->
                <span
                    class="capitalize opacity-0 group-hover:opacity-100 transition-opacity text-[10px]"
                    :class="{
                        'text-destructive': milestone.priority === 'critical',
                        'text-orange-500': milestone.priority === 'high',
                        'text-yellow-500': milestone.priority === 'medium',
                        'text-muted-foreground': milestone.priority === 'low',
                    }"
                >{{ milestone.priority }}</span>
            </div>

            <!-- Stacked event-status bar with inline counts + tooltip (visible on hover) -->
            <Tooltip v-if="milestone.total_events_count > 0">
                <TooltipTrigger as-child>
                    <div class="opacity-0 group-hover:opacity-100 transition-opacity flex w-full h-4 cursor-default">
                        <div
                            v-for="(seg, i) in eventBarSegments"
                            :key="seg.key"
                            class="flex items-center justify-center overflow-hidden min-w-0"
                            :class="[
                                seg.color,
                                i === 0 ? 'rounded-l-full' : '',
                                i === eventBarSegments.length - 1 ? 'rounded-r-full' : '',
                            ]"
                            :style="{ width: `${seg.pct}%` }"
                        >
                            <span class="text-[9px] font-medium text-white/90 leading-none truncate px-1 tabular-nums">
                                {{ seg.count }}
                            </span>
                        </div>
                    </div>
                </TooltipTrigger>
                <TooltipContent>
                    <div class="flex flex-col gap-1 text-xs">
                        <div
                            v-for="seg in eventBarSegments"
                            :key="seg.key"
                            class="flex items-center gap-1.5"
                        >
                            <span class="size-2 rounded-full shrink-0" :class="seg.color" />
                            {{ seg.count }} {{ seg.label }}
                        </div>
                    </div>
                </TooltipContent>
            </Tooltip>
        </div>
    </button>
</template>
