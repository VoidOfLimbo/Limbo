<script setup lang="ts">
import { ref } from 'vue'
import { AlarmClock } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import type { PlannerEvent } from '@/types/planner'

const props = defineProps<{
    event: PlannerEvent
}>()

const emit = defineEmits<{
    snooze: [eventId: string, until: string]
    cancel: []
}>()

const customDatetime = ref('')

const PRESETS: { label: string; offset: () => Date }[] = [
    {
        label: 'In 1 hour',
        offset: () => {
            const d = new Date()
            d.setHours(d.getHours() + 1)
            return d
        },
    },
    {
        label: 'Tomorrow 9am',
        offset: () => {
            const d = new Date()
            d.setDate(d.getDate() + 1)
            d.setHours(9, 0, 0, 0)
            return d
        },
    },
    {
        label: 'Next Monday',
        offset: () => {
            const d = new Date()
            const day = d.getDay()
            const daysUntilMonday = (8 - day) % 7 || 7
            d.setDate(d.getDate() + daysUntilMonday)
            d.setHours(9, 0, 0, 0)
            return d
        },
    },
    {
        label: 'In 3 days',
        offset: () => {
            const d = new Date()
            d.setDate(d.getDate() + 3)
            d.setHours(9, 0, 0, 0)
            return d
        },
    },
]

function applyPreset(d: Date) {
    emit('snooze', props.event.id, d.toISOString())
}

function applyCustom() {
    if (!customDatetime.value) return
    emit('snooze', props.event.id, new Date(customDatetime.value).toISOString())
}

// Minimum datetime for the custom picker — must be in the future
const minDatetime = new Date(Date.now() + 60_000).toISOString().slice(0, 16)
</script>

<template>
    <div class="flex flex-col gap-3 p-4 min-w-[220px]">
        <div class="flex items-center gap-2 text-sm font-medium">
            <AlarmClock class="size-4 text-amber-500" />
            Snooze "{{ event.title }}"
        </div>

        <!-- Preset buttons -->
        <div class="grid grid-cols-2 gap-1.5">
            <Button
                v-for="preset in PRESETS"
                :key="preset.label"
                variant="outline"
                size="sm"
                class="h-7 text-xs justify-start"
                @click="applyPreset(preset.offset())"
            >
                {{ preset.label }}
            </Button>
        </div>

        <div class="flex items-center gap-2 text-xs text-muted-foreground">
            <div class="flex-1 h-px bg-border" />
            or pick a time
            <div class="flex-1 h-px bg-border" />
        </div>

        <!-- Custom datetime -->
        <div class="flex gap-1.5">
            <input
                v-model="customDatetime"
                type="datetime-local"
                :min="minDatetime"
                class="flex-1 h-8 rounded border border-border bg-background px-2 text-xs text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
            />
            <Button
                size="sm"
                class="h-8 px-3"
                :disabled="!customDatetime"
                @click="applyCustom"
            >
                Set
            </Button>
        </div>

        <Button variant="ghost" size="sm" class="h-7 text-xs text-muted-foreground" @click="emit('cancel')">
            Cancel
        </Button>
    </div>
</template>
