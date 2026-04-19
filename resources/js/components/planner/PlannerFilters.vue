<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { SlidersHorizontal, X, ChevronDown } from 'lucide-vue-next'
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

interface FilterOption {
    value: string
    label: string
    inactive: string
    active: string
}

const STATUS_OPTIONS: FilterOption[] = [
    {
        value: 'draft',
        label: 'Draft',
        inactive: 'border-border text-muted-foreground hover:border-zinc-500/50 hover:text-zinc-400',
        active: 'bg-zinc-500/15 border-zinc-500/60 text-zinc-300',
    },
    {
        value: 'upcoming',
        label: 'Upcoming',
        inactive: 'border-border text-muted-foreground hover:border-blue-500/50 hover:text-blue-400',
        active: 'bg-blue-500/15 border-blue-500/60 text-blue-300',
    },
    {
        value: 'in_progress',
        label: 'In Progress',
        inactive: 'border-border text-muted-foreground hover:border-amber-500/50 hover:text-amber-400',
        active: 'bg-amber-500/15 border-amber-500/60 text-amber-300',
    },
    {
        value: 'completed',
        label: 'Completed',
        inactive: 'border-border text-muted-foreground hover:border-emerald-500/50 hover:text-emerald-400',
        active: 'bg-emerald-500/15 border-emerald-500/60 text-emerald-300',
    },
    {
        value: 'cancelled',
        label: 'Cancelled',
        inactive: 'border-border text-muted-foreground hover:border-red-500/50 hover:text-red-400',
        active: 'bg-red-500/15 border-red-500/60 text-red-300',
    },
    {
        value: 'skipped',
        label: 'Skipped',
        inactive: 'border-border text-muted-foreground hover:border-slate-500/50 hover:text-slate-400',
        active: 'bg-slate-500/15 border-slate-500/60 text-slate-300',
    },
]

const PRIORITY_OPTIONS: FilterOption[] = [
    {
        value: 'critical',
        label: 'Critical',
        inactive: 'border-border text-muted-foreground hover:border-rose-500/50 hover:text-rose-400',
        active: 'bg-rose-500/15 border-rose-500/60 text-rose-300',
    },
    {
        value: 'high',
        label: 'High',
        inactive: 'border-border text-muted-foreground hover:border-orange-500/50 hover:text-orange-400',
        active: 'bg-orange-500/15 border-orange-500/60 text-orange-300',
    },
    {
        value: 'medium',
        label: 'Medium',
        inactive: 'border-border text-muted-foreground hover:border-amber-500/50 hover:text-amber-400',
        active: 'bg-amber-500/15 border-amber-500/60 text-amber-300',
    },
    {
        value: 'low',
        label: 'Low',
        inactive: 'border-border text-muted-foreground hover:border-sky-500/50 hover:text-sky-400',
        active: 'bg-sky-500/15 border-sky-500/60 text-sky-300',
    },
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
            <!-- Toggle panel button -->
            <button
                class="flex items-center gap-1.5 text-xs font-medium transition-colors focus-visible:outline-none shrink-0"
                :class="panelOpen ? 'text-foreground' : 'text-muted-foreground hover:text-foreground'"
                @click="panelOpen = !panelOpen"
            >
                <SlidersHorizontal class="size-3.5" />
                <span>Filters</span>
                <span
                    v-if="activeChips.length"
                    class="size-4 rounded-full bg-primary text-primary-foreground text-[10px] font-semibold flex items-center justify-center tabular-nums leading-none"
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
                    :style="chip.color ? { borderColor: chip.color + '80', color: chip.color } : {}"
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

            <div v-else class="flex-1" />

            <!-- Trailing slot: view switcher -->
            <slot name="trailing" />
        </div>

        <!-- Collapsible filter panel -->
        <div
            v-show="panelOpen"
            class="flex flex-wrap items-start gap-x-6 gap-y-4 px-4 py-3 border-t border-border/50 bg-muted/20"
        >
            <!-- Status -->
            <div class="shrink-0">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground mb-2">Status</p>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="opt in STATUS_OPTIONS"
                        :key="opt.value"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full border text-[11px] font-medium transition-colors"
                        :class="selectedStatuses.includes(opt.value) ? opt.active : opt.inactive"
                        @click="toggleStatus(opt.value)"
                    >
                        {{ opt.label }}
                    </button>
                </div>
            </div>

            <!-- Separator -->
            <div class="self-stretch border-l border-border/40 shrink-0" />

            <!-- Priority -->
            <div class="shrink-0">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground mb-2">Priority</p>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="opt in PRIORITY_OPTIONS"
                        :key="opt.value"
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full border text-[11px] font-medium transition-colors"
                        :class="selectedPriorities.includes(opt.value) ? opt.active : opt.inactive"
                        @click="togglePriority(opt.value)"
                    >
                        {{ opt.label }}
                    </button>
                </div>
            </div>

            <!-- Separator -->
            <div v-if="tags.length" class="self-stretch border-l border-border/40 shrink-0" />

            <!-- Tags -->
            <div v-if="tags.length" class="shrink-0 max-w-90">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground mb-2">Tags</p>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="tag in tags"
                        :key="tag.id"
                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full border text-[11px] font-medium transition-colors"
                        :class="
                            selectedTags.includes(tag.id)
                                ? 'border-transparent text-white'
                                : 'border-border text-muted-foreground hover:border-foreground/30 hover:text-foreground'
                        "
                        :style="selectedTags.includes(tag.id) && tag.color ? { backgroundColor: tag.color, borderColor: tag.color } : {}"
                        @click="toggleTag(tag.id)"
                    >
                        <span
                            v-if="!selectedTags.includes(tag.id) && tag.color"
                            class="size-1.5 rounded-full shrink-0"
                            :style="{ backgroundColor: tag.color }"
                        />
                        {{ tag.name }}
                    </button>
                </div>
            </div>

            <!-- Separator -->
            <div class="self-stretch border-l border-border/40 shrink-0" />

            <!-- Date range + Snoozed -->
            <div class="shrink-0 flex flex-col gap-2.5">
                <div>
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground mb-2">Date range</p>
                    <div class="flex items-center gap-1.5">
                        <input
                            v-model="dateFrom"
                            type="date"
                            class="h-7 rounded-md border border-border bg-background px-2 text-[11px] text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        />
                        <span class="text-xs text-muted-foreground">→</span>
                        <input
                            v-model="dateTo"
                            type="date"
                            class="h-7 rounded-md border border-border bg-background px-2 text-[11px] text-foreground focus:outline-none focus:ring-1 focus:ring-ring"
                        />
                    </div>
                </div>

                <!-- Snoozed toggle -->
                <button
                    class="flex items-center gap-2 text-[11px] font-medium transition-colors w-fit"
                    :class="showSnoozed ? 'text-foreground' : 'text-muted-foreground hover:text-foreground'"
                    @click="showSnoozed = !showSnoozed"
                >
                    <span
                        class="inline-flex size-4 items-center justify-center rounded border transition-colors shrink-0"
                        :class="showSnoozed ? 'bg-primary border-primary' : 'border-border'"
                    >
                        <svg
                            v-if="showSnoozed"
                            viewBox="0 0 10 10"
                            class="size-2.5 text-primary-foreground"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M1.5 5l2.5 2.5 4.5-4" />
                        </svg>
                    </span>
                    Include snoozed
                </button>
            </div>
        </div>
    </div>
</template>
