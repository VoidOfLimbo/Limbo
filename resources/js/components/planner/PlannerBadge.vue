<script setup lang="ts">
import { computed } from 'vue'
import { Badge } from '@/components/ui/badge'
import { cn } from '@/lib/utils'
import type { EventPriority, EventStatus, EventType } from '@/types/planner'

type BadgeKind = 'priority' | 'status' | 'type'

const props = defineProps<{
    kind: BadgeKind
    value: EventPriority | EventStatus | EventType | string
    class?: string
}>()

// ── Priority ─────────────────────────────────────────────────────────────────
const priorityConfig: Record<EventPriority, { label: string; class: string }> = {
    critical: { label: 'Critical', class: 'bg-destructive/15 text-destructive border-destructive/30 dark:bg-destructive/20' },
    high:     { label: 'High',     class: 'bg-orange-500/15 text-orange-600 border-orange-500/30 dark:text-orange-400' },
    medium:   { label: 'Medium',   class: 'bg-yellow-500/15 text-yellow-700 border-yellow-500/30 dark:text-yellow-400' },
    low:      { label: 'Low',      class: 'bg-muted/60 text-muted-foreground border-border' },
}

// ── Status ───────────────────────────────────────────────────────────────────
const statusConfig: Record<EventStatus, { label: string; class: string }> = {
    draft:       { label: 'Draft',       class: 'bg-muted/60 text-muted-foreground border-border' },
    upcoming:    { label: 'Upcoming',    class: 'bg-blue-500/15 text-blue-700 border-blue-500/30 dark:text-blue-400' },
    in_progress: { label: 'In Progress', class: 'bg-violet-500/15 text-violet-700 border-violet-500/30 dark:text-violet-400' },
    completed:   { label: 'Completed',   class: 'bg-green-500/15 text-green-700 border-green-500/30 dark:text-green-400' },
    cancelled:   { label: 'Cancelled',   class: 'bg-muted/60 text-muted-foreground/60 border-border line-through' },
    skipped:     { label: 'Skipped',     class: 'bg-muted/60 text-muted-foreground/60 border-border' },
}

// ── Type ─────────────────────────────────────────────────────────────────────
const typeConfig: Record<EventType, { label: string; class: string }> = {
    event:             { label: 'Event',   class: 'bg-muted/60 text-muted-foreground border-border' },
    task:              { label: 'Task',    class: 'bg-blue-500/15 text-blue-700 border-blue-500/30 dark:text-blue-400' },
    milestone_marker:  { label: 'Marker',  class: 'bg-violet-500/15 text-violet-700 border-violet-500/30 dark:text-violet-400' },
}

const config = computed(() => {
    if (props.kind === 'priority') return priorityConfig[props.value as EventPriority] ?? { label: props.value, class: '' }
    if (props.kind === 'status')   return statusConfig[props.value as EventStatus]   ?? { label: props.value, class: '' }
    return typeConfig[props.value as EventType] ?? { label: props.value, class: '' }
})
</script>

<template>
    <Badge
        variant="outline"
        :class="cn('text-xs font-medium capitalize', config.class, props.class)"
    >
        {{ config.label }}
    </Badge>
</template>
