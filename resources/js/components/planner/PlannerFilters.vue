<script setup lang="ts">
import { ref, watch } from 'vue'
import { Filter, X, Eye } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import type { PlannerFilters, PlannerTag } from '@/types/planner'

const props = defineProps<{
    filters: PlannerFilters
    tags: PlannerTag[]
    isActive: boolean
}>()

const emit = defineEmits<{
    change: [patch: Partial<PlannerFilters>]
    clear: []
}>()

const STATUS_OPTIONS = [
    { value: 'draft', label: 'Draft' },
    { value: 'upcoming', label: 'Upcoming' },
    { value: 'in_progress', label: 'In Progress' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'skipped', label: 'Skipped' },
]

const PRIORITY_OPTIONS = [
    { value: 'critical', label: 'Critical' },
    { value: 'high', label: 'High' },
    { value: 'medium', label: 'Medium' },
    { value: 'low', label: 'Low' },
]

const selectedStatuses = ref<string[]>(
    Array.isArray(props.filters.status) ? props.filters.status : props.filters.status ? [props.filters.status] : [],
)
const selectedPriorities = ref<string[]>(
    Array.isArray(props.filters.priority) ? props.filters.priority : props.filters.priority ? [props.filters.priority] : [],
)
const selectedTags = ref<string[]>(props.filters.tags ?? [])
const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')
const showSnoozed = ref(props.filters.show_snoozed === 'true')

watch([selectedStatuses, selectedPriorities, selectedTags, dateFrom, dateTo, showSnoozed], () => {
    emit('change', {
        status: selectedStatuses.value.length ? selectedStatuses.value : undefined,
        priority: selectedPriorities.value.length ? selectedPriorities.value : undefined,
        tags: selectedTags.value.length ? selectedTags.value : undefined,
        date_from: dateFrom.value || undefined,
        date_to: dateTo.value || undefined,
        show_snoozed: showSnoozed.value ? 'true' : undefined,
    })
})

function toggleStatus(value: string) {
    const idx = selectedStatuses.value.indexOf(value)
    if (idx === -1) selectedStatuses.value.push(value)
    else selectedStatuses.value.splice(idx, 1)
}

function togglePriority(value: string) {
    const idx = selectedPriorities.value.indexOf(value)
    if (idx === -1) selectedPriorities.value.push(value)
    else selectedPriorities.value.splice(idx, 1)
}

function toggleTag(id: string) {
    const idx = selectedTags.value.indexOf(id)
    if (idx === -1) selectedTags.value.push(id)
    else selectedTags.value.splice(idx, 1)
}

function handleClear() {
    selectedStatuses.value = []
    selectedPriorities.value = []
    selectedTags.value = []
    dateFrom.value = ''
    dateTo.value = ''
    showSnoozed.value = false
    emit('clear')
}
</script>

<template>
    <div class="flex items-center gap-2 px-4 py-2 border-b border-border overflow-x-auto scrollbar-thin shrink-0">
        <Filter class="size-3.5 text-muted-foreground shrink-0" />

        <!-- Status chips -->
        <div class="flex items-center gap-1">
            <button
                v-for="opt in STATUS_OPTIONS"
                :key="opt.value"
                class="px-2 py-0.5 rounded-full text-[11px] font-medium border transition-colors whitespace-nowrap"
                :class="
                    selectedStatuses.includes(opt.value)
                        ? 'bg-primary text-primary-foreground border-primary'
                        : 'border-border text-muted-foreground hover:border-foreground/50 hover:text-foreground'
                "
                @click="toggleStatus(opt.value)"
            >
                {{ opt.label }}
            </button>
        </div>

        <div class="h-4 w-px bg-border shrink-0" />

        <!-- Priority chips -->
        <div class="flex items-center gap-1">
            <button
                v-for="opt in PRIORITY_OPTIONS"
                :key="opt.value"
                class="px-2 py-0.5 rounded-full text-[11px] font-medium border transition-colors whitespace-nowrap"
                :class="
                    selectedPriorities.includes(opt.value)
                        ? 'bg-primary text-primary-foreground border-primary'
                        : 'border-border text-muted-foreground hover:border-foreground/50 hover:text-foreground'
                "
                @click="togglePriority(opt.value)"
            >
                {{ opt.label }}
            </button>
        </div>

        <!-- Tag chips (only if tags exist) -->
        <template v-if="tags.length">
            <div class="h-4 w-px bg-border shrink-0" />
            <div class="flex items-center gap-1">
                <button
                    v-for="tag in tags"
                    :key="tag.id"
                    class="px-2 py-0.5 rounded-full text-[11px] font-medium border transition-colors whitespace-nowrap"
                    :class="
                        selectedTags.includes(tag.id)
                            ? 'text-white border-transparent'
                            : 'border-border text-muted-foreground hover:border-foreground/50 hover:text-foreground'
                    "
                    :style="
                        selectedTags.includes(tag.id) && tag.color
                            ? { backgroundColor: tag.color, borderColor: tag.color }
                            : {}
                    "
                    @click="toggleTag(tag.id)"
                >
                    {{ tag.name }}
                </button>
            </div>
        </template>

        <div class="h-4 w-px bg-border shrink-0" />

        <!-- Date range -->
        <div class="flex items-center gap-1 shrink-0">
            <input
                v-model="dateFrom"
                type="date"
                class="h-6 rounded border border-border bg-background px-1.5 text-[11px] text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                placeholder="From"
            />
            <span class="text-[11px] text-muted-foreground">–</span>
            <input
                v-model="dateTo"
                type="date"
                class="h-6 rounded border border-border bg-background px-1.5 text-[11px] text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                placeholder="To"
            />
        </div>

        <div class="h-4 w-px bg-border shrink-0" />

        <!-- Show snoozed toggle -->
        <button
            class="flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium border transition-colors whitespace-nowrap shrink-0"
            :class="
                showSnoozed
                    ? 'bg-primary text-primary-foreground border-primary'
                    : 'border-border text-muted-foreground hover:border-foreground/50 hover:text-foreground'
            "
            @click="showSnoozed = !showSnoozed"
        >
            <Eye class="size-3" />
            Snoozed
        </button>

        <!-- Clear button -->
        <Button
            v-if="isActive"
            variant="ghost"
            size="sm"
            class="ml-auto h-6 gap-1 text-muted-foreground shrink-0 text-[11px]"
            @click="handleClear"
        >
            <X class="size-3" />
            Clear
        </Button>
    </div>
</template>
