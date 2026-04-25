<script setup lang="ts">
import { ref } from 'vue'
import { CalendarDays, Share2, ChevronDown, Check } from 'lucide-vue-next'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import type { ZoomLevel } from '@/composables/planner/useRoadmapLayout'

defineProps<{
    zoom: ZoomLevel
    showDependencies: boolean
}>()

const emit = defineEmits<{
    'update:zoom': [zoom: ZoomLevel]
    'update:showDependencies': [value: boolean]
    scrollToToday: []
}>()

const zoomOpen = ref(false)

const ZOOM_LEVELS: { value: ZoomLevel; label: string }[] = [
    { value: 'week', label: 'Week' },
    { value: 'month', label: 'Month' },
    { value: 'quarter', label: 'Quarter' },
    { value: 'year', label: 'Year' },
]
</script>

<template>
    <div class="flex items-center gap-2 px-3 py-1.5 border-b border-border bg-background shrink-0">
        <!-- Zoom picker dropdown -->
        <Popover v-model:open="zoomOpen">
            <PopoverTrigger as-child>
                <button
                    type="button"
                    class="flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-md border border-border text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                >
                    {{ ZOOM_LEVELS.find((l) => l.value === zoom)?.label ?? zoom }}
                    <ChevronDown class="size-3 opacity-60" />
                </button>
            </PopoverTrigger>
            <PopoverContent align="start" class="w-36 p-1" :side-offset="6">
                <p class="px-2 py-1.5 text-[10px] font-semibold text-muted-foreground uppercase tracking-wide">
                    Zoom level
                </p>
                <button
                    v-for="level in ZOOM_LEVELS"
                    :key="level.value"
                    type="button"
                    class="flex items-center w-full px-2 py-1.5 text-sm rounded-sm transition-colors hover:bg-muted"
                    :class="zoom === level.value ? 'font-semibold text-foreground' : 'text-muted-foreground'"
                    @click="
                        () => {
                            emit('update:zoom', level.value)
                            zoomOpen = false
                        }
                    "
                >
                    <Check
                        v-if="zoom === level.value"
                        class="size-3.5 mr-2 shrink-0 text-primary"
                    />
                    <span v-else class="size-3.5 mr-2 shrink-0" />
                    {{ level.label }}
                </button>
            </PopoverContent>
        </Popover>

        <!-- Today button -->
        <button
            type="button"
            class="flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-md border border-border text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
            @click="emit('scrollToToday')"
        >
            <CalendarDays class="size-3.5" />
            Today
        </button>

        <!-- Spacer -->
        <div class="flex-1" />

        <!-- Dependencies toggle -->
        <button
            type="button"
            class="flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-md border transition-colors"
            :class="
                showDependencies
                    ? 'bg-primary/10 border-primary/30 text-primary'
                    : 'border-border text-muted-foreground hover:bg-muted hover:text-foreground'
            "
            @click="emit('update:showDependencies', !showDependencies)"
        >
            <Share2 class="size-3.5" />
            Dependencies
        </button>
    </div>
</template>
