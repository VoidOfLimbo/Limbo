<script setup lang="ts">
import { ChevronRight, Lock, AlertTriangle } from 'lucide-vue-next'
import type { PlannerMilestone, PlannerEvent } from '@/types/planner'

export type RoadmapRow =
    | { kind: 'milestone'; milestone: PlannerMilestone }
    | { kind: 'event'; event: PlannerEvent }

defineProps<{
    rows: RoadmapRow[]
    expandedMilestoneIds: Set<string>
    rowHeight: number
    selectedId: string | null
}>()

const emit = defineEmits<{
    toggleMilestone: [milestoneId: string]
    select: [id: string]
}>()

const STATUS_COLORS: Record<string, string> = {
    active: 'bg-blue-500',
    completed: 'bg-green-500',
    paused: 'bg-yellow-500',
    cancelled: 'bg-red-500',
}

const EVENT_STATUS_COLORS: Record<string, string> = {
    upcoming: 'bg-blue-400',
    in_progress: 'bg-violet-500',
    completed: 'bg-green-500',
    draft: 'bg-gray-400',
    cancelled: 'bg-red-400',
    skipped: 'bg-gray-300',
}
</script>

<template>
    <!-- Pure row list — scroll wrapper is managed by the parent -->
    <div class="flex flex-col">
        <div
            v-for="(row, i) in rows"
            :key="i"
            class="flex items-center gap-2 px-2 border-b border-border/20 shrink-0 cursor-pointer transition-colors"
            :class="
                (row.kind === 'milestone' ? row.milestone.id : row.event.id) === selectedId
                    ? 'bg-primary/10 border-l-2 border-l-primary -ml-px'
                    : 'hover:bg-accent/30'
            "
            :style="{ height: `${rowHeight}px` }"
            @click="emit('select', row.kind === 'milestone' ? row.milestone.id : row.event.id)"
        >
            <!-- Milestone row -->
            <template v-if="row.kind === 'milestone'">
                <button
                    type="button"
                    class="flex items-center justify-center size-5 shrink-0 rounded hover:bg-muted transition-colors"
                    @click.stop="emit('toggleMilestone', row.milestone.id)"
                >
                    <ChevronRight
                        class="size-3.5 text-muted-foreground transition-transform duration-150"
                        :class="expandedMilestoneIds.has(row.milestone.id) ? 'rotate-90' : ''"
                    />
                </button>

                <div
                    class="size-2 rounded-full shrink-0"
                    :class="STATUS_COLORS[row.milestone.status] ?? 'bg-gray-400'"
                />

                <span class="text-sm font-medium truncate flex-1 leading-tight">
                    {{ row.milestone.title }}
                </span>

                <Lock v-if="row.milestone.deadline_type === 'hard'" class="size-3 text-muted-foreground shrink-0" />
                <AlertTriangle v-if="row.milestone.is_breached" class="size-3 text-destructive shrink-0" />
            </template>

            <!-- Event row -->
            <template v-else>
                <div class="w-7 shrink-0" />
                <div
                    class="size-1.5 rounded-full shrink-0"
                    :class="EVENT_STATUS_COLORS[row.event.status] ?? 'bg-gray-400'"
                />
                <span class="text-xs text-muted-foreground truncate flex-1 leading-tight">
                    {{ row.event.title }}
                </span>
            </template>
        </div>
    </div>
</template>
