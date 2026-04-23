<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useElementSize } from '@vueuse/core'
import { router, usePage } from '@inertiajs/vue3'
import {
    Search, LayoutGrid, LayoutList, Plus, Archive,
    CalendarRange, CalendarDays, Activity, Flag, CalendarClock, Eye, Timer,
    SlidersHorizontal, ArrowUpDown, ArrowUp, ArrowDown, X,
} from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Tooltip, TooltipTrigger, TooltipContent } from '@/components/ui/tooltip'
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuTrigger,
    DropdownMenuCheckboxItem, DropdownMenuLabel, DropdownMenuSeparator,
    DropdownMenuRadioGroup, DropdownMenuRadioItem,
} from '@/components/ui/dropdown-menu'
import PlannerMilestoneCard from '@/components/planner/PlannerMilestoneCard.vue'
import type { PlannerMilestone, GroupByKey } from '@/types/planner'

const props = defineProps<{
    milestones: PlannerMilestone[]
    activeMilestoneId: string | null
    groupBy: GroupByKey
}>()

const emit = defineEmits<{
    createMilestone: []
    'update:groupBy': [value: GroupByKey]
}>()

// ── View mode: grid or list ──────────────────────────────────────────────────
const viewMode = ref<'grid' | 'list'>('grid')

// ── Grid columns ────────────────────────────────────────────────────────────
const listContainer = ref<HTMLElement | null>(null)
const { width: listContainerWidth } = useElementSize(listContainer)
const maxListCols = computed(() => Math.max(1, Math.min(4, Math.floor(listContainerWidth.value / 240))) as 1 | 2 | 3 | 4)
const gridColumns = ref<1 | 2 | 3 | 4>(2)
watch(maxListCols, (max) => {
    if (gridColumns.value > max) gridColumns.value = max
}, { immediate: true })

// ── Search ───────────────────────────────────────────────────────────────────
const search = ref('')

// ── Group by ─────────────────────────────────────────────────────────────────

const GROUP_OPTIONS: { value: GroupByKey; label: string; icon: unknown }[] = [
    { value: 'quarter', label: 'Quarter', icon: CalendarRange },
    { value: 'month', label: 'Month', icon: CalendarDays },
    { value: 'status', label: 'Status', icon: Activity },
    { value: 'priority', label: 'Priority', icon: Flag },
    { value: 'deadline', label: 'Deadline', icon: CalendarClock },
    { value: 'visibility', label: 'Visibility', icon: Eye },
    { value: 'duration', label: 'Duration', icon: Timer },
]

const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
const STATUS_ORDER = ['Active', 'Paused', 'Completed', 'Cancelled']
const PRIORITY_ORDER = ['Critical', 'High', 'Medium', 'Low']

// ── Filters ───────────────────────────────────────────────────────────────────
const filterStatus = ref<Set<string>>(new Set())
const filterPriority = ref<Set<string>>(new Set())

function toggleStatus(val: string) {
    const s = new Set(filterStatus.value)
    if (s.has(val)) { s.delete(val) } else { s.add(val) }
    filterStatus.value = s
}

function togglePriority(val: string) {
    const s = new Set(filterPriority.value)
    if (s.has(val)) { s.delete(val) } else { s.add(val) }
    filterPriority.value = s
}

const activeFilterCount = computed(() => filterStatus.value.size + filterPriority.value.size)

// ── Sort ─────────────────────────────────────────────────────────────────────
type SortField = 'title' | 'progress' | 'start_at' | 'end_at' | 'events'
const SORT_OPTIONS: { value: SortField; label: string }[] = [
    { value: 'title', label: 'Title' },
    { value: 'progress', label: 'Progress' },
    { value: 'start_at', label: 'Start date' },
    { value: 'end_at', label: 'End date' },
    { value: 'events', label: 'Event count' },
]
const sortBy = ref<SortField>('end_at')
const sortDir = ref<'asc' | 'desc'>('asc')

function toggleSortDir() {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
}

function getQuarter(dateStr: string | null | undefined): string {
    if (!dateStr) return 'Unscheduled'
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return 'Unscheduled'
    return `Q${Math.floor(d.getMonth() / 3) + 1} ${d.getFullYear()}`
}

function getMonth(dateStr: string | null | undefined): string {
    if (!dateStr) return 'Unscheduled'
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return 'Unscheduled'
    return d.toLocaleString('default', { month: 'short', year: 'numeric' })
}

function getGroupKey(m: PlannerMilestone, by: GroupByKey): string {
    if (by === 'quarter') return getQuarter(m.end_at ?? m.start_at)
    if (by === 'month') return getMonth(m.end_at ?? m.start_at)
    if (by === 'status') return m.status.charAt(0).toUpperCase() + m.status.slice(1)
    if (by === 'priority') return m.priority.charAt(0).toUpperCase() + m.priority.slice(1)
    if (by === 'deadline') return m.deadline_type === 'hard' ? 'Hard deadline' : 'Soft deadline'
    if (by === 'visibility') return m.visibility === 'shared' ? 'Shared' : 'Private'
    if (by === 'duration') return m.duration_source === 'manual' ? 'Manual' : 'Derived'
    return 'Other'
}

function sortKey(label: string, by: GroupByKey): string {
    if (by === 'quarter') {
        if (label === 'Unscheduled') return 'Z'
        const [q, year] = label.split(' ')
        return `${year}-${q.slice(1).padStart(2, '0')}`
    }
    if (by === 'month') {
        if (label === 'Unscheduled') return 'Z'
        const [mon, year] = label.split(' ')
        const mi = MONTH_NAMES.indexOf(mon)
        return `${year}-${String(mi + 1).padStart(2, '0')}`
    }
    if (by === 'status') {
        const i = STATUS_ORDER.indexOf(label)
        return i >= 0 ? String(i) : 'Z'
    }
    if (by === 'priority') {
        const i = PRIORITY_ORDER.indexOf(label)
        return i >= 0 ? String(i) : 'Z'
    }
    return label
}

const filtered = computed(() => {
    let result = props.milestones

    // Search
    const q = search.value.trim().toLowerCase()
    if (q) result = result.filter((m) => m.title.toLowerCase().includes(q))

    // Status filter
    if (filterStatus.value.size > 0)
        result = result.filter((m) => filterStatus.value.has(m.status))

    // Priority filter
    if (filterPriority.value.size > 0)
        result = result.filter((m) => filterPriority.value.has(m.priority))

    // Sort
    return [...result].sort((a, b) => {
        let cmp = 0
        if (sortBy.value === 'title') cmp = a.title.localeCompare(b.title)
        else if (sortBy.value === 'progress') cmp = a.progress - b.progress
        else if (sortBy.value === 'start_at') cmp = (a.start_at ?? '').localeCompare(b.start_at ?? '')
        else if (sortBy.value === 'end_at') cmp = (a.end_at ?? '').localeCompare(b.end_at ?? '')
        else if (sortBy.value === 'events') cmp = a.total_events_count - b.total_events_count
        return sortDir.value === 'asc' ? cmp : -cmp
    })
})

const grouped = computed(() => {
    const map = new Map<string, PlannerMilestone[]>()
    for (const m of filtered.value) {
        const key = getGroupKey(m, props.groupBy)
        if (!map.has(key)) map.set(key, [])
        map.get(key)!.push(m)
    }
    return [...map.entries()].sort(([a], [b]) =>
        sortKey(a, props.groupBy).localeCompare(sortKey(b, props.groupBy)),
    )
})

// ── Navigation ────────────────────────────────────────────────────────────────
const page = usePage()

function navigate(milestoneId: string | null) {
    const pathname = page.url.split('?')[0]
    router.visit(pathname, {
        data: { milestone: milestoneId ?? 'backlog' },
        preserveScroll: false,
        preserveState: false,
        replace: true,
    })
}

// ── Current group label (for breadcrumb parent) ───────────────────────────────
const selectedGroupOption = computed(() =>
    GROUP_OPTIONS.find((o) => o.value === props.groupBy) ?? GROUP_OPTIONS[0],
)

// ── Active tab ───────────────────────────────────────────────────────────────
const activeGroup = ref<string | null>(null)

// ── Pagination ───────────────────────────────────────────────────────────────
const STORAGE_DASH_PER_PAGE_KEY = 'planner:dashPerPage'
const _storedPerPage = typeof localStorage !== 'undefined' ? localStorage.getItem(STORAGE_DASH_PER_PAGE_KEY) : null
const perPage = ref<number>(_storedPerPage && [10, 25, 50, 100].includes(Number(_storedPerPage)) ? Number(_storedPerPage) : 25)
const currentPage = ref(1)

watch(perPage, (val) => {
    if (typeof localStorage !== 'undefined') localStorage.setItem(STORAGE_DASH_PER_PAGE_KEY, String(val))
    currentPage.value = 1
})

// Reset / init active tab when groups change
watch(
    grouped,
    (newGrouped) => {
        if (newGrouped.length === 0) {
            activeGroup.value = null
            return
        }
        // Keep current tab if it still exists, else select first
        if (!activeGroup.value || !newGrouped.some(([label]) => label === activeGroup.value)) {
            activeGroup.value = newGrouped[0][0]
        }
    },
    { immediate: true },
)

// Reset page when tab changes
watch(activeGroup, () => { currentPage.value = 1 })

const activeItems = computed(() => {
    const all = grouped.value.find(([label]) => label === activeGroup.value)?.[1] ?? []
    const start = (currentPage.value - 1) * perPage.value
    return all.slice(start, start + perPage.value)
})

const totalActiveItems = computed(() =>
    grouped.value.find(([label]) => label === activeGroup.value)?.[1].length ?? 0,
)

const totalPages = computed(() => Math.max(1, Math.ceil(totalActiveItems.value / perPage.value)))
</script>

<template>
    <div class="flex flex-col h-full overflow-hidden">
        <!-- Toolbar -->
        <div class="flex items-center gap-2 px-4 py-3 border-b border-border shrink-0 flex-wrap">
            <!-- Search -->
            <div class="relative flex-1 min-w-40 max-w-72">
                <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-muted-foreground pointer-events-none" />
                <Input
                    v-model="search"
                    placeholder="Search milestones…"
                    class="pl-8 h-8 text-sm"
                />
            </div>

            <!-- Group by picker -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="sm" class="h-8 gap-1.5 text-xs">
                        <component :is="selectedGroupOption.icon" class="size-3.5" />
                        Group: {{ selectedGroupOption.label }}
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" class="min-w-36">
                    <DropdownMenuRadioGroup :model-value="groupBy" @update:model-value="(v) => emit('update:groupBy', v as GroupByKey)">
                        <DropdownMenuRadioItem v-for="opt in GROUP_OPTIONS" :key="opt.value" :value="opt.value" class="text-xs gap-2">
                            <component :is="opt.icon" class="size-3.5 shrink-0" />
                            {{ opt.label }}
                        </DropdownMenuRadioItem>
                    </DropdownMenuRadioGroup>
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- Filter -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="sm" class="h-8 gap-1.5 text-xs" :class="activeFilterCount > 0 ? 'border-primary text-primary' : ''">
                        <SlidersHorizontal class="size-3.5" />
                        Filter
                        <span v-if="activeFilterCount > 0" class="inline-flex items-center justify-center size-4 rounded-full bg-primary text-primary-foreground text-[10px] font-medium">
                            {{ activeFilterCount }}
                        </span>
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" class="min-w-44">
                    <DropdownMenuLabel class="text-xs">Status</DropdownMenuLabel>
                    <DropdownMenuCheckboxItem
                        v-for="s in STATUS_ORDER"
                        :key="s"
                        :checked="filterStatus.has(s.toLowerCase())"
                        class="text-xs"
                        @update:checked="toggleStatus(s.toLowerCase())"
                    >{{ s }}</DropdownMenuCheckboxItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuLabel class="text-xs">Priority</DropdownMenuLabel>
                    <DropdownMenuCheckboxItem
                        v-for="p in PRIORITY_ORDER"
                        :key="p"
                        :checked="filterPriority.has(p.toLowerCase())"
                        class="text-xs"
                        @update:checked="togglePriority(p.toLowerCase())"
                    >{{ p }}</DropdownMenuCheckboxItem>

                    <template v-if="activeFilterCount > 0">
                        <DropdownMenuSeparator />
                        <button
                            class="flex items-center gap-1.5 w-full px-2 py-1.5 text-xs text-muted-foreground hover:text-foreground hover:bg-accent rounded"
                            @click="filterStatus = new Set(); filterPriority = new Set()"
                        >
                            <X class="size-3" /> Clear filters
                        </button>
                    </template>
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- Sort -->
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="outline" size="sm" class="h-8 gap-1.5 text-xs">
                        <component :is="sortDir === 'asc' ? ArrowUp : ArrowDown" class="size-3.5" />
                        Sort: {{ SORT_OPTIONS.find(o => o.value === sortBy)?.label }}
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="start" class="min-w-40">
                    <DropdownMenuLabel class="text-xs">Sort by</DropdownMenuLabel>
                    <DropdownMenuRadioGroup :model-value="sortBy" @update:model-value="(v) => { sortBy = v as SortField }">
                        <DropdownMenuRadioItem v-for="opt in SORT_OPTIONS" :key="opt.value" :value="opt.value" class="text-xs">
                            {{ opt.label }}
                        </DropdownMenuRadioItem>
                    </DropdownMenuRadioGroup>
                    <DropdownMenuSeparator />
                    <DropdownMenuLabel class="text-xs">Direction</DropdownMenuLabel>
                    <DropdownMenuRadioGroup :model-value="sortDir" @update:model-value="(v) => { sortDir = v as 'asc' | 'desc' }">
                        <DropdownMenuRadioItem value="asc" class="text-xs gap-1.5">
                            <ArrowUp class="size-3" /> Ascending
                        </DropdownMenuRadioItem>
                        <DropdownMenuRadioItem value="desc" class="text-xs gap-1.5">
                            <ArrowDown class="size-3" /> Descending
                        </DropdownMenuRadioItem>
                    </DropdownMenuRadioGroup>
                </DropdownMenuContent>
            </DropdownMenu>

            <!-- Column picker (list mode only) -->
            <div v-if="viewMode === 'list'" class="hidden sm:flex items-center border border-border rounded-md overflow-hidden">
                <Tooltip v-for="col in ([1, 2, 3, 4] as const).slice(0, maxListCols)" :key="col">
                    <TooltipTrigger as-child>
                        <button
                            type="button"
                            class="px-2 py-1.5 text-[11px] font-mono transition-colors"
                            :class="[
                                col > 1 ? 'border-l border-border' : '',
                                gridColumns === col ? 'bg-accent text-foreground' : 'text-muted-foreground hover:bg-accent/50',
                            ]"
                            @click="gridColumns = col"
                        >{{ col }}</button>
                    </TooltipTrigger>
                    <TooltipContent>{{ col }} column{{ col !== 1 ? 's' : '' }}</TooltipContent>
                </Tooltip>
            </div>

            <!-- Spacer -->
            <div class="flex-1" />

            <!-- Backlog button -->
            <Button
                variant="ghost"
                size="sm"
                class="h-8 gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                @click="navigate(null)"
            >
                <Archive class="size-3.5" />
                Backlog
            </Button>

            <!-- New milestone -->
            <Button size="sm" class="h-8 gap-1.5 text-xs" @click="emit('createMilestone')">
                <Plus class="size-3.5" />
                New milestone
            </Button>

            <!-- View toggle -->
            <div class="flex items-center border border-border rounded-md overflow-hidden">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <button
                            type="button"
                            class="px-2.5 py-1.5 text-xs transition-colors"
                            :class="viewMode === 'grid' ? 'bg-accent text-foreground' : 'text-muted-foreground hover:bg-accent/50'"
                            @click="viewMode = 'grid'"
                        >
                            <LayoutGrid class="size-3.5" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent>Grid view</TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <button
                            type="button"
                            class="px-2.5 py-1.5 text-xs transition-colors border-l border-border"
                            :class="viewMode === 'list' ? 'bg-accent text-foreground' : 'text-muted-foreground hover:bg-accent/50'"
                            @click="viewMode = 'list'"
                        >
                            <LayoutList class="size-3.5" />
                        </button>
                    </TooltipTrigger>
                    <TooltipContent>List view</TooltipContent>
                </Tooltip>
            </div>


        </div>

        <!-- Empty state -->
        <div v-if="milestones.length === 0" class="flex-1 flex flex-col items-center justify-center gap-3 text-center px-8">
            <CalendarRange class="size-10 text-muted-foreground/40" />
            <p class="text-sm font-medium">No milestones yet</p>
            <p class="text-xs text-muted-foreground">Create your first milestone to start planning.</p>
            <Button size="sm" class="mt-1" @click="emit('createMilestone')">
                <Plus class="size-3.5 mr-1.5" />
                New milestone
            </Button>
        </div>

        <!-- No search results -->
        <div v-else-if="filtered.length === 0" class="flex-1 flex flex-col items-center justify-center gap-2 text-center px-8">
            <p class="text-sm text-muted-foreground">No milestones match "{{ search }}"</p>
        </div>

        <template v-else>
            <!-- Group tabs -->
            <div class="shrink-0 border-b border-border px-4 overflow-x-auto">
                <Tabs :model-value="activeGroup ?? undefined" @update:model-value="(v) => activeGroup = v ?? null">
                    <TabsList class="h-auto gap-0 rounded-none bg-transparent p-0">
                        <TabsTrigger
                            v-for="[label, items] in grouped"
                            :key="label"
                            :value="label"
                            class="rounded-none border-b-2 border-transparent px-3 py-2.5 text-xs font-medium data-[state=active]:border-primary data-[state=active]:bg-transparent data-[state=active]:shadow-none data-[state=active]:text-foreground text-muted-foreground hover:text-foreground whitespace-nowrap gap-1.5"
                        >
                            {{ label }}
                            <span
                                class="inline-flex items-center justify-center rounded-full px-1.5 py-0.5 text-[10px] tabular-nums leading-none"
                                :class="activeGroup === label ? 'bg-primary/15 text-primary' : 'bg-muted text-muted-foreground'"
                            >{{ items.length }}</span>
                        </TabsTrigger>
                    </TabsList>
                </Tabs>
            </div>

            <!-- Active tab content -->
            <div ref="listContainer" class="flex-1 overflow-y-auto px-4 py-4">
                <!-- Grid view -->
                <div
                    v-if="viewMode === 'grid'"
                    class="grid gap-3"
                    style="grid-template-columns: repeat(auto-fill, minmax(300px, 1fr))"
                >
                    <PlannerMilestoneCard
                        v-for="m in activeItems"
                        :key="m.id"
                        :milestone="m"
                        :active="activeMilestoneId === m.id"
                        @select="navigate(m.id)"
                    />
                </div>

                <!-- List view -->
                <div
                    v-else
                    class="grid gap-y-0.5 gap-x-3"
                    :class="{
                        'grid-cols-1': gridColumns === 1,
                        'grid-cols-1 sm:grid-cols-2': gridColumns === 2,
                        'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3': gridColumns === 3,
                        'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4': gridColumns === 4,
                    }"
                >
                    <button
                        v-for="m in activeItems"
                        :key="m.id"
                        type="button"
                        class="relative w-full flex items-center gap-3 px-3 py-2.5 rounded-lg border text-left transition-colors overflow-hidden
                               hover:border-primary/30 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        :class="activeMilestoneId === m.id ? 'border-primary/40' : 'border-border'"
                        :style="m.color ? { borderLeftColor: m.color, borderLeftWidth: '3px' } : {}"
                        @click="navigate(m.id)"
                    >
                        <!-- subtle color tint -->
                        <div
                            v-if="m.color"
                            class="absolute inset-0 pointer-events-none"
                            :style="{ backgroundColor: m.color + '10' }"
                        />
                        <span class="relative flex-1 text-sm font-medium truncate">{{ m.title }}</span>
                        <div class="relative w-20 h-1.5 rounded-full bg-border overflow-hidden shrink-0">
                            <div
                                class="h-full rounded-full"
                                :class="m.is_breached ? 'bg-destructive' : m.progress >= 100 ? 'bg-green-500' : 'bg-primary'"
                                :style="{ width: `${Math.min(m.progress, 100)}%` }"
                            />
                        </div>
                        <span class="relative text-[10px] text-muted-foreground tabular-nums w-8 text-right shrink-0">{{ m.progress }}%</span>
                        <span class="relative text-[10px] text-muted-foreground shrink-0">
                            {{ m.completed_events_count }}/{{ m.total_events_count }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Pagination footer -->
            <div
                v-if="totalPages > 1 || totalActiveItems > 10"
                class="shrink-0 border-t border-border px-4 py-2.5 flex items-center justify-between gap-3"
            >
                <div class="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <span>{{ (currentPage - 1) * perPage + 1 }}–{{ Math.min(currentPage * perPage, totalActiveItems) }} of {{ totalActiveItems }}</span>
                    <select
                        :value="perPage"
                        class="ml-2 h-6 rounded border border-border bg-background text-xs px-1 cursor-pointer"
                        @change="perPage = Number(($event.target as HTMLSelectElement).value)"
                    >
                        <option v-for="n in [10, 25, 50, 100]" :key="n" :value="n">{{ n }} / page</option>
                    </select>
                </div>
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        class="h-6 px-2 text-xs rounded border border-border disabled:opacity-40 hover:bg-accent transition-colors"
                        :disabled="currentPage <= 1"
                        @click="currentPage--"
                    >←</button>
                    <span class="text-xs text-muted-foreground tabular-nums px-1">{{ currentPage }} / {{ totalPages }}</span>
                    <button
                        type="button"
                        class="h-6 px-2 text-xs rounded border border-border disabled:opacity-40 hover:bg-accent transition-colors"
                        :disabled="currentPage >= totalPages"
                        @click="currentPage++"
                    >→</button>
                </div>
            </div>
        </template>
    </div>
</template>
