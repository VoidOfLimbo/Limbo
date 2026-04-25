<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import { Plus, CalendarRange } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Tooltip, TooltipTrigger, TooltipContent } from '@/components/ui/tooltip'
import type { PlannerMilestone } from '@/types/planner'

const props = defineProps<{
    milestones: PlannerMilestone[]
    activeMilestoneId: string | null
    currentFilters: Record<string, unknown>
}>()

const emit = defineEmits<{
    createMilestone: []
}>()

const tabsPage = usePage()

function navigateTo(milestoneId: string | null) {
    router.visit(tabsPage.url.split('?')[0], {
        data: { ...props.currentFilters, milestone: milestoneId ?? undefined },
        preserveScroll: false,
        preserveState: false,
        replace: true,
    })
}

// SVG arc — radius 5.5, circumference ≈ 34.56
const CIRC = 2 * Math.PI * 5.5

function arcOffset(progress: number): number {
    return CIRC * (1 - Math.min(progress, 100) / 100)
}

function milestoneTooltip(m: PlannerMilestone): string {
    const parts: string[] = [`${m.progress}% complete`]
    if (m.total_events_count) {
        parts.push(`${m.completed_events_count}/${m.total_events_count} events`)
    }
    if (m.end_at) {
        const date = new Date(m.end_at).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
        parts.push(`${m.deadline_type === 'hard' ? 'Hard' : 'Soft'} deadline: ${date}`)
    }
    parts.push(m.status.charAt(0).toUpperCase() + m.status.slice(1))
    if (m.is_breached) parts.push('⚠ Deadline breached')
    return parts.join(' · ')
}
</script>

<template>
    <div class="flex items-center gap-1 border-b border-border overflow-x-auto scrollbar-thin px-4 shrink-0">
        <!-- Milestone tabs -->
        <Tooltip v-for="milestone in milestones" :key="milestone.id">
            <TooltipTrigger as-child>
                <button
                    class="relative flex items-center gap-2 px-3 py-2.5 text-sm font-medium whitespace-nowrap shrink-0 border-b-2 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    :class="
                        activeMilestoneId === milestone.id
                            ? 'border-primary text-primary'
                            : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'
                    "
                    @click="navigateTo(milestone.id)"
                >
                    <!-- Color dot -->
                    <span
                        v-if="milestone.color"
                        class="size-2 rounded-full shrink-0"
                        :style="{ backgroundColor: milestone.color }"
                    />
                    <CalendarRange v-else class="size-3.5 shrink-0 opacity-50" />

                    <span>{{ milestone.title }}</span>

                    <!-- Breach dot -->
                    <span
                        v-if="milestone.is_breached"
                        class="size-1.5 rounded-full bg-destructive shrink-0"
                    />

                    <!-- Mini SVG progress arc -->
                    <svg
                        class="size-3.5 shrink-0 -rotate-90"
                        viewBox="0 0 16 16"
                        fill="none"
                        aria-hidden="true"
                    >
                        <circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="2" class="opacity-15" />
                        <circle
                            cx="8"
                            cy="8"
                            r="5.5"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            :stroke-dasharray="CIRC"
                            :stroke-dashoffset="arcOffset(milestone.progress)"
                            class="transition-all duration-500"
                            :class="milestone.is_breached ? 'stroke-destructive' : milestone.progress >= 100 ? 'stroke-green-500' : ''"
                        />
                    </svg>
                </button>
            </TooltipTrigger>
            <TooltipContent>{{ milestoneTooltip(milestone) }}</TooltipContent>
        </Tooltip>

        <!-- Backlog tab -->
        <button
            class="relative flex items-center gap-2 px-3 py-2.5 text-sm font-medium whitespace-nowrap shrink-0 border-b-2 transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            :class="
                activeMilestoneId === null
                    ? 'border-primary text-primary'
                    : 'border-transparent text-muted-foreground hover:text-foreground hover:border-border'
            "
            @click="navigateTo(null)"
        >
            Backlog
        </button>

        <!-- Spacer + New milestone button -->
        <div class="ml-auto pl-2 py-1.5 shrink-0">
            <Button variant="ghost" size="sm" class="h-7 gap-1.5 text-muted-foreground" @click="emit('createMilestone')">
                <Plus class="size-3.5" />
                <span class="hidden sm:inline">New milestone</span>
            </Button>
        </div>
    </div>
</template>
