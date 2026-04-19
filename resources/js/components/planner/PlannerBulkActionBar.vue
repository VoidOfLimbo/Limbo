<script setup lang="ts">
import { Trash2, X } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import type { PlannerEvent } from '@/types/planner'

const props = defineProps<{
    events: PlannerEvent[]
}>()

const emit = defineEmits<{
    clear: []
    delete: [event: PlannerEvent]
}>()
</script>

<template>
    <div
        class="flex items-center gap-3 px-4 py-3 border-t border-border bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80"
    >
        <span class="text-sm text-muted-foreground">
            {{ events.length }} selected
        </span>

        <div class="flex items-center gap-2 ml-auto">
            <Button
                variant="outline"
                size="sm"
                class="h-7 gap-1.5 text-destructive hover:text-destructive"
                @click="events.forEach((e) => emit('delete', e))"
            >
                <Trash2 class="size-3.5" />
                Delete
            </Button>

            <Button variant="ghost" size="sm" class="h-7 gap-1" @click="emit('clear')">
                <X class="size-3.5" />
                Clear
            </Button>
        </div>
    </div>
</template>
