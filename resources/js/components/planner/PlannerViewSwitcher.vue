<script setup lang="ts">
import { LayoutList, Table2, LayoutDashboard, GanttChartSquare } from 'lucide-vue-next';
import { usePlannerStore, type PlannerViewMode } from '@/stores/planner';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

const store = usePlannerStore();

const views: { id: PlannerViewMode; label: string; icon: typeof LayoutList }[] = [
    { id: 'list', label: 'List', icon: LayoutList },
    { id: 'table', label: 'Table', icon: Table2 },
    { id: 'board', label: 'Board', icon: LayoutDashboard },
    { id: 'roadmap', label: 'Roadmap', icon: GanttChartSquare },
];
</script>

<template>
    <TooltipProvider :delay-duration="400">
        <div class="flex items-center rounded-md border p-0.5 gap-0.5">
            <template v-for="view in views" :key="view.id">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <button
                            type="button"
                            class="flex items-center justify-center rounded px-2 py-1 text-sm transition-colors"
                            :class="
                                store.activeView === view.id
                                    ? 'bg-primary text-primary-foreground'
                                    : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                            "
                            @click="store.setView(view.id)"
                        >
                            <component :is="view.icon" class="size-4" />
                            <span class="ml-1.5 hidden sm:inline">{{ view.label }}</span>
                        </button>
                    </TooltipTrigger>
                    <TooltipContent>{{ view.label }} view</TooltipContent>
                </Tooltip>
            </template>
        </div>
    </TooltipProvider>
</template>
