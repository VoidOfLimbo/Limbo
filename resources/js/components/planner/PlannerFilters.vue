<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Filter, X, ChevronDown } from 'lucide-vue-next'
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

const panelOpen = ref(false)

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

interface ActiveChip {
    id: string
    label: string
    color?: string | null
    remove: () => void
}

const activeChips = computed<ActiveChip[]>(() => {
    const chips: ActiveChip[] = []
    for (const s of selectedStatuses.value) {
        chips.push({ id: `status:${s}`, label: STATUS_OPTIONS.find((o) => o.value === s)?.label ?? s, remove: () => toggleStatus(s) })
    }
    for (const p of selectedPriorities.value) {
        chips.push({ id: `priority:${p}`, label: PRIORITY_OPTIONS.find((o) => o.value === p)?.label ?? p, remove: () => togglePriority(p) })
    }
    for (const t of selectedTags.value) {
        const tag = props.tags.find((tag) => tag.id === t)
        chips.push({ id: `tag:${t}`, label: tag?.name ?? t, color: tag?.color, remove: () => toggleTag(t) })
    }
    if (dateFrom.value || dateTo.value) {
        const fmt = (s: string) => new Date(s).toLocaleDateString(undefined, { month: 'short', day: 'numeric' })
        const parts = [dateFrom.value && fmt(dateFrom.value), dateTo.value && fmt(dateTo.value)].filter(Boolean)
        chips.push({ id: 'date', label: parts.join(' → '), remove: () => { dateFrom.value = ''; dateTo.value = '' } })
    }
    if (showSnoozed.value) {
        chips.push({ id: 'snoozed', label: 'Incl. snoozed', remove: () => { showSnoozed.value = false } })
    }
    return chips
})
</script>

<template>
    <div class="border-b border-border shrink-0">
        <!-- Toolbar row -->
        <div class="flex items-center gap-2 px-4 py-1.5 min-h-[36px]">
            <!-- Toggle panel button -->
            <button
                class="flex items-center gap-1.5 text-xs font-medium transition-colors focus-visible:outline-none shrink-0"
                :class="panelOpen ? 'text-foreground' : 'text-muted-foreground hover:text-foreground'"
                @click="panelOpen = !panelOpen"
            >
                <Filter class="size-3.5" />
                <span>Filters</span>
                <span
                    v-if="activeChips.length"
                    class="size-4 rounded-full bg-primary text-primary-foreground text-[10px] font-semibold flex items-center justify-center tabular-nums"
                >
                    {{ activeChips.length }}
                </span>
                <ChevronDown
                    v-else
                    class="size-3 opacity-40 transition-transform duration-200"
                    :class="{ 'rotate-180': panelOpen }"
                />
            </button>

            <!-- Active filter chips (removable) -->
            <div v-if="activeChips.length" class="flex items-center gap-1 flex-wrap flex-1 min-w-0">
                <button
                    v-for="chip in activeChips"
                    :key="chip.id"
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium border border-border hover:border-foreground/30 transition-colors whitespace-nowrap"
                    :style="chip.color ? { borderColor: chip.color, color: chip.color } : {}"
                    @click="chip.remove()"
                >
                    {{ chip.label }}
                    <X class="size-2.5 opacity-60" />
                </button>
                <button
                    class="text-[11px] text-muted-foreground hover:text-foreground underline-offset-2 hover:underline transition-colors ml-1 shrink-0"
                    @click="handleClear"
                >
                    Clear all
                </button>
            </div>
        </div>

        <!-- Collapsible filter panel -->
        <div
            v-show="panelOpen"
            class="grid grid-cols-[auto_auto_1fr_auto] gap-x-8 gap-y-1 px-4 py-3 border-t border-border/50 bg-muted/20 items-start"
        >
            <!-- Status -->
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground mb-2">Status</p>
                <div class="flex flex-col gap-1.5">
                    <label
                        v-for="opt in STATUS_OPTIONS"
                        :key="opt.value"
                        class="flex items-center gap-2 text-xs cursor-pointer transition-colors"
                        :class="selectedStatuses.includes(opt.value) ? 'text-foreground font-medium' : 'text-muted-foreground hover:text-foreground'"
                    >
                        <input
                            type="checkbox"
                            class="size-3 accent-primary rounded"
                            :checked="selectedStatuses.includes(opt.value)"
                            @change="toggleStatus(opt.value)"
                        />
                        {{ opt.label }}
                    </label>
                </div>
            </div>

            <!-- Priority -->
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground mb-2">Priority</p>
                <div class="flex flex-col gap-1.5">
                    <label
                        v-for="opt in PRIORITY_OPTIONS"
                        :key="opt.value"
                        class="flex items-center gap-2 text-xs cursor-pointer transition-colors"
                        :class="selectedPriorities.includes(opt.value) ? 'text-foreground font-medium' : 'text-muted-foreground hover:text-foreground'"
                    >
                        <input
                            type="checkbox"
                            class="size-3 accent-primary"
                            :checked="selectedPriorities.includes(opt.value)"
                            @change="togglePriority(opt.value)"
                        />
                        {{ opt.label }}
                    </label>
                </div>
            </div>

            <!-- Tags -->
            <div v-if="tags.length">
                <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground mb-2">Tags</p>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="tag in tags"
                        :key="tag.id"
                        class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] border transition-colors"
                        :class="
                            selectedTags.includes(tag.id)
                                ? 'text-white border-transparent'
                                : 'border-border text-muted-foreground hover:border-foreground/30 hover:text-foreground'
                        "
                        :style="selectedTags.includes(tag.id) && tag.color ? { backgroundColor: tag.color, borderColor: tag.color } : {}"
                        @click="toggleTag(tag.id)"
                    >
                        <span
                            v-if="tag.color && !selectedTags.includes(tag.id)"
                            class="size-1.5 rounded-full shrink-0"
                            :style="{ backgroundColor: tag.color }"
                        />
                        {{ tag.name }}
                    </button>
                </div>
            </div>

            <!-- Date range + Snoozed -->
            <div class="flex flex-col gap-3">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground mb-2">Date range</p>
                    <div class="flex items-center gap-1.5">
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="h-6 rounded border border-border bg-background px-1.5 text-[11px] text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        />
                        <span class="text-[11px] text-muted-foreground">–</span>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="h-6 rounded border border-border bg-background px-1.5 text-[11px] text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        />
                    </div>
                </div>
                <label class="flex items-center gap-2 text-xs cursor-pointer transition-colors" :class="showSnoozed ? 'text-foreground font-medium' : 'text-muted-foreground hover:text-foreground'">
                    <input v-model="showSnoozed" type="checkbox" class="size-3 accent-primary" />
                    Include snoozed
                </label>
            </div>
        </div>
    </div>
</template>
