<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { Plus, CalendarRange } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import type { PlannerMilestone } from '@/types/planner'

const props = defineProps<{
    milestones: PlannerMilestone[]
    activeMilestoneId: string | null
    currentFilters: Record<string, unknown>
}>()

const emit = defineEmits<{
    createMilestone: []
}>()

function navigateTo(milestoneId: string | null) {
    router.visit(window.location.pathname, {
        data: { ...props.currentFilters, milestone: milestoneId ?? undefined },
        preserveScroll: false,
        preserveState: false,
        replace: true,
    })
}
</script>

<template>
    <div class="flex items-center gap-1 border-b border-border overflow-x-auto scrollbar-thin px-4 shrink-0">
        <!-- Milestone tabs -->
        <button
            v-for="milestone in milestones"
            :key="milestone.id"
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

            <!-- Breach indicator -->
            <span
                v-if="milestone.is_breached"
                class="size-1.5 rounded-full bg-destructive shrink-0"
                title="Deadline breached"
            />

            <!-- Progress indicator pill -->
            <span class="text-[10px] font-semibold tabular-nums text-muted-foreground">
                {{ milestone.progress }}%
            </span>
        </button>

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
