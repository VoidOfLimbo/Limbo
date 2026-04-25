<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { SlidersHorizontal } from 'lucide-vue-next'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import PlannerFilterChip from '@/components/planner/PlannerFilterChip.vue'
import type { PlannerFilters, PlannerTag } from '@/types/planner'

const props = defineProps<{
    filters: PlannerFilters
    tags: PlannerTag[]
    isActive: boolean
}>()

const emit = defineEmits<{
    change: [patch: Partial<PlannerFilters>]
}>()

const panelOpen = ref(false)

type Tone = 'zinc' | 'slate' | 'blue' | 'amber' | 'emerald' | 'red' | 'rose' | 'orange' | 'sky' | 'violet' | 'green' | 'gray'

interface FilterOption {
    value: string
    label: string
    tone: Tone
}

const STATUS_OPTIONS: FilterOption[] = [
    { value: 'draft',       label: 'Draft',       tone: 'zinc' },
    { value: 'upcoming',    label: 'Upcoming',    tone: 'blue' },
    { value: 'in_progress', label: 'In Progress', tone: 'amber' },
    { value: 'completed',   label: 'Completed',   tone: 'emerald' },
    { value: 'cancelled',   label: 'Cancelled',   tone: 'red' },
    { value: 'skipped',     label: 'Skipped',     tone: 'slate' },
]

const PRIORITY_OPTIONS: FilterOption[] = [
    { value: 'critical',  label: 'Critical',  tone: 'rose' },
    { value: 'high',      label: 'High',      tone: 'orange' },
    { value: 'medium',    label: 'Medium',    tone: 'amber' },
    { value: 'low',       label: 'Low',       tone: 'sky' },
    { value: 'ignorable', label: 'Ignorable', tone: 'gray' },
]

// ── State ───────────────────────────────────────────────────────────────────
// Use new-array assignments (not push/splice) so watch() detects reference changes.
const selectedStatuses = ref<string[]>(
    Array.isArray(props.filters.status) ? [...props.filters.status] : props.filters.status ? [props.filters.status] : [],
)
const selectedPriorities = ref<string[]>(
    Array.isArray(props.filters.priority) ? [...props.filters.priority] : props.filters.priority ? [props.filters.priority] : [],
)
const selectedTags = ref<string[]>(props.filters.tags ? [...props.filters.tags] : [])
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
    selectedStatuses.value = selectedStatuses.value.includes(value)
        ? selectedStatuses.value.filter((v) => v !== value)
        : [...selectedStatuses.value, value]
}

function togglePriority(value: string) {
    selectedPriorities.value = selectedPriorities.value.includes(value)
        ? selectedPriorities.value.filter((v) => v !== value)
        : [...selectedPriorities.value, value]
}

function toggleTag(id: string) {
    selectedTags.value = selectedTags.value.includes(id)
        ? selectedTags.value.filter((v) => v !== id)
        : [...selectedTags.value, id]
}

function handleClear() {
    selectedStatuses.value = []
    selectedPriorities.value = []
    selectedTags.value = []
    dateFrom.value = ''
    dateTo.value = ''
    showSnoozed.value = false
    // watch fires automatically and calls emit('change') with empty patch → applyFilters clears the URL
}

// ── Active chips ─────────────────────────────────────────────────────────────
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
        <div class="flex items-center gap-2 px-4 py-2 min-h-11">
            <!-- Filters popover trigger -->
            <Popover v-model:open="panelOpen">
                <PopoverTrigger as-child>
                    <button
                        class="flex items-center gap-1.5 text-xs font-medium transition-colors focus-visible:outline-none shrink-0 cursor-pointer"
                        :class="panelOpen || activeChips.length ? 'text-foreground' : 'text-muted-foreground hover:text-foreground'"
                    >
                        <SlidersHorizontal class="size-3.5" />
                        <span>Filters</span>
                        <span
                            v-if="activeChips.length"
                            class="size-4 rounded-full bg-primary text-primary-foreground text-[10px] font-semibold flex items-center justify-center tabular-nums leading-none"
                        >
                            {{ activeChips.length }}
                        </span>
                    </button>
                </PopoverTrigger>
                <PopoverContent align="start" :side-offset="8" class="w-auto max-w-[min(95vw,680px)] p-4">
                    <div class="flex flex-col gap-4">
                        <!-- Status -->
                        <div class="flex items-start gap-3">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground w-16 pt-1 shrink-0">Status</p>
                            <div class="flex flex-wrap gap-1.5 flex-1">
                                <PlannerFilterChip
                                    v-for="opt in STATUS_OPTIONS"
                                    :key="opt.value"
                                    :label="opt.label"
                                    :tone="opt.tone"
                                    :active="selectedStatuses.includes(opt.value)"
                                    @click="toggleStatus(opt.value)"
                                />
                            </div>
                        </div>

                        <!-- Priority -->
                        <div class="flex items-start gap-3">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground w-16 pt-1 shrink-0">Priority</p>
                            <div class="flex flex-wrap gap-1.5 flex-1">
                                <PlannerFilterChip
                                    v-for="opt in PRIORITY_OPTIONS"
                                    :key="opt.value"
                                    :label="opt.label"
                                    :tone="opt.tone"
                                    :active="selectedPriorities.includes(opt.value)"
                                    @click="togglePriority(opt.value)"
                                />
                            </div>
                        </div>

                        <!-- Tags -->
                        <div v-if="tags.length" class="flex items-start gap-3">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground w-16 pt-1 shrink-0">Tags</p>
                            <div class="flex flex-wrap gap-1.5 flex-1">
                                <PlannerFilterChip
                                    v-for="tag in tags"
                                    :key="tag.id"
                                    :label="tag.name"
                                    :color="tag.color"
                                    :active="selectedTags.includes(tag.id)"
                                    @click="toggleTag(tag.id)"
                                />
                            </div>
                        </div>

                        <!-- Date range + Snoozed toggle -->
                        <div class="flex items-start gap-3 flex-wrap">
                            <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground w-16 pt-1.5 shrink-0">Date</p>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <input
                                    v-model="dateFrom"
                                    type="date"
                                    class="h-7 rounded-md border border-border bg-background px-2 text-[11px] text-foreground focus:outline-none focus:ring-1 focus:ring-ring cursor-pointer dark:scheme-dark"
                                />
                                <span class="text-xs text-muted-foreground">→</span>
                                <input
                                    v-model="dateTo"
                                    type="date"
                                    class="h-7 rounded-md border border-border bg-background px-2 text-[11px] text-foreground focus:outline-none focus:ring-1 focus:ring-ring cursor-pointer dark:scheme-dark"
                                />
                            </div>
                            <button
                                role="switch"
                                :aria-checked="showSnoozed"
                                class="ml-auto inline-flex items-center gap-2 text-[11px] font-medium transition-colors cursor-pointer"
                                :class="showSnoozed ? 'text-foreground' : 'text-muted-foreground hover:text-foreground'"
                                @click="showSnoozed = !showSnoozed"
                            >
                                <span
                                    class="relative inline-flex h-4 w-7 items-center rounded-full border transition-colors shrink-0"
                                    :class="showSnoozed ? 'bg-primary border-primary' : 'bg-muted border-border'"
                                >
                                    <span
                                        class="inline-block size-3 rounded-full bg-background shadow-sm transition-transform"
                                        :class="showSnoozed ? 'translate-x-3.5' : 'translate-x-0.5'"
                                    />
                                </span>
                                Include snoozed
                            </button>
                        </div>

                        <!-- Clear all (bottom-right of popover) -->
                        <div v-if="activeChips.length" class="flex justify-end border-t border-border/40 pt-3 mt-1">
                            <button
                                class="text-[11px] text-muted-foreground hover:text-foreground underline-offset-2 hover:underline transition-colors cursor-pointer"
                                @click="handleClear"
                            >
                                Clear all filters
                            </button>
                        </div>
                    </div>
                </PopoverContent>
            </Popover>

            <!-- Active filter chips (removable) -->
            <div class="flex-1" />

            <!-- Trailing slot: view switcher -->
            <slot name="trailing" />
        </div>
    </div>
</template>
