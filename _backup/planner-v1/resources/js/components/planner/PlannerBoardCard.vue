<script setup lang="ts">
import { CalendarRange } from 'lucide-vue-next'
import PlannerBadge from '@/components/planner/PlannerBadge.vue'
import type { PlannerEvent } from '@/types/planner'

const props = defineProps<{
    event: PlannerEvent
    showMilestone?: boolean
}>()

const emit = defineEmits<{
    click: []
    snooze: []
    delete: []
    toggleStatus: []
    duplicate: []
}>()

function formatDate(dateStr: string | null) {
    if (!dateStr) return null
    try {
        return new Date(dateStr).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
    } catch {
        return null
    }
}
</script>

<template>
    <div
        class="group relative flex flex-col gap-2 rounded-md border border-border bg-background p-2.5 cursor-grab active:cursor-grabbing hover:shadow-sm transition-shadow"
        @click.stop="emit('click')"
    >
        <!-- Title -->
        <p class="text-sm font-medium leading-tight line-clamp-2">{{ event.title }}</p>

        <!-- Badges -->
        <div class="flex items-center gap-1.5 flex-wrap">
            <PlannerBadge kind="priority" :value="event.priority" />
            <PlannerBadge kind="type" :value="event.type" />
        </div>

        <!-- Date range -->
        <div
            v-if="event.start_at || event.end_at"
            class="flex items-center gap-1 text-xs text-muted-foreground"
        >
            <CalendarRange class="size-3 shrink-0" />
            <span>
                {{ formatDate(event.start_at) }}
                <template v-if="event.start_at && event.end_at"> – </template>
                {{ formatDate(event.end_at) }}
            </span>
        </div>

        <!-- Tags -->
        <div v-if="event.tags.length" class="flex items-center gap-1 flex-wrap">
            <span
                v-for="tag in event.tags.slice(0, 3)"
                :key="tag.id"
                class="inline-flex items-center gap-1 rounded-full bg-muted px-1.5 py-0.5 text-xs text-muted-foreground"
            >
                <span
                    v-if="tag.color"
                    class="size-1.5 rounded-full shrink-0"
                    :style="{ backgroundColor: tag.color }"
                />
                {{ tag.name }}
            </span>
            <span v-if="event.tags.length > 3" class="text-xs text-muted-foreground">
                +{{ event.tags.length - 3 }}
            </span>
        </div>

        <!-- Milestone -->
        <p v-if="showMilestone && event.milestone" class="text-xs text-muted-foreground truncate">
            {{ event.milestone.title }}
        </p>
    </div>
</template>
