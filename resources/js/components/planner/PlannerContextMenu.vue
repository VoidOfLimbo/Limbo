<script setup lang="ts">
import { CheckCircle2, Circle, Pencil, AlarmClock, Trash2, Copy } from 'lucide-vue-next'
import {
    ContextMenuRoot,
    ContextMenuTrigger,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuPortal,
} from 'reka-ui'
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
</script>

<template>
    <ContextMenuRoot>
        <ContextMenuTrigger as-child>
            <slot />
        </ContextMenuTrigger>

        <ContextMenuPortal>
            <ContextMenuContent
                class="z-50 min-w-[160px] rounded-md border border-border bg-popover p-1 shadow-md
                       animate-in fade-in-0 zoom-in-95"
            >
                <!-- Toggle complete -->
                <ContextMenuItem
                    class="flex items-center gap-2 rounded px-2 py-1.5 text-sm cursor-pointer
                           hover:bg-accent outline-none select-none"
                    @select="emit('toggleStatus', event)"
                >
                    <CheckCircle2 v-if="event.status !== 'completed'" class="size-4 text-muted-foreground" />
                    <Circle v-else class="size-4 text-muted-foreground" />
                    {{ event.status === 'completed' ? 'Mark incomplete' : 'Mark complete' }}
                </ContextMenuItem>

                <ContextMenuSeparator class="my-1 h-px bg-border" />

                <!-- Edit -->
                <ContextMenuItem
                    class="flex items-center gap-2 rounded px-2 py-1.5 text-sm cursor-pointer
                           hover:bg-accent outline-none select-none"
                    @select="emit('edit', event)"
                >
                    <Pencil class="size-4 text-muted-foreground" />
                    Edit
                </ContextMenuItem>

                <!-- Duplicate -->
                <ContextMenuItem
                    class="flex items-center gap-2 rounded px-2 py-1.5 text-sm cursor-pointer
                           hover:bg-accent outline-none select-none"
                    @select="emit('duplicate', event)"
                >
                    <Copy class="size-4 text-muted-foreground" />
                    Duplicate
                </ContextMenuItem>

                <!-- Snooze -->
                <ContextMenuItem
                    class="flex items-center gap-2 rounded px-2 py-1.5 text-sm cursor-pointer
                           hover:bg-accent outline-none select-none"
                    @select="emit('snooze', event)"
                >
                    <AlarmClock class="size-4 text-amber-500" />
                    Snooze…
                </ContextMenuItem>

                <ContextMenuSeparator class="my-1 h-px bg-border" />

                <!-- Delete -->
                <ContextMenuItem
                    class="flex items-center gap-2 rounded px-2 py-1.5 text-sm cursor-pointer
                           hover:bg-destructive/10 text-destructive outline-none select-none"
                    @select="emit('delete', event)"
                >
                    <Trash2 class="size-4" />
                    Delete
                </ContextMenuItem>
            </ContextMenuContent>
        </ContextMenuPortal>
    </ContextMenuRoot>
</template>
