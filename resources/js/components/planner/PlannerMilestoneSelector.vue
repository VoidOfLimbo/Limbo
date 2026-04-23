<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { ChevronDown, ChevronRight, CalendarRange, CalendarDays, CalendarClock, Plus, LayoutList, Group, Activity, Flag, Clock, Eye, Timer, AlertTriangle, Lock, CheckCircle2, Edit2 } from 'lucide-vue-next'
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Tooltip, TooltipTrigger, TooltipContent } from '@/components/ui/tooltip'
import type { PlannerMilestone, GroupByKey } from '@/types/planner'

const props = defineProps<{
    milestones: PlannerMilestone[]
    activeMilestoneId: string | null
    currentFilters: Record<string, unknown>
    groupBy: GroupByKey
}>()

const emit = defineEmits<{
    openExplorer: []
    'update:groupBy': [value: GroupByKey]
    edit: [milestone: PlannerMilestone]
    createEvent: []
}>()

// ── State ────────────────────────────────────────────────────────────────────
const open = ref(false)
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
const showGroupPicker = ref(false)

// collapsed group keys
const collapsed = ref(new Set<string>())
function toggleCollapse(key: string) {
    if (collapsed.value.has(key)) {
        collapsed.value.delete(key)
    } else {
        collapsed.value.add(key)
    }
    // trigger reactivity
    collapsed.value = new Set(collapsed.value)
}

// ── Active milestone label ───────────────────────────────────────────────────
const activeMilestone = computed(() =>
    props.milestones.find((m) => m.id === props.activeMilestoneId) ?? null,
)

// ── SVG arc ──────────────────────────────────────────────────────────────────
const CIRC = 2 * Math.PI * 5.5

function arcOffset(progress: number): number {
    return CIRC * (1 - Math.min(progress, 100) / 100)
}

// ── Grouping logic ───────────────────────────────────────────────────────────
function getQuarter(dateStr: string | null | undefined): string {
    if (!dateStr) return 'Unscheduled'
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return 'Unscheduled'
    const q = Math.floor(d.getMonth() / 3) + 1
    return `Q${q} ${d.getFullYear()}`
}

function getMonth(dateStr: string | null | undefined): string {
    if (!dateStr) return 'Unscheduled'
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return 'Unscheduled'
    return `${MONTH_NAMES[d.getMonth()]} ${d.getFullYear()}`
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

const STATUS_ORDER = ['Active', 'Paused', 'Completed', 'Cancelled']
const PRIORITY_ORDER = ['Critical', 'High', 'Medium', 'Low']

const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']

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
    const q = search.value.trim().toLowerCase()
    if (!q) return props.milestones
    return props.milestones.filter((m) => m.title.toLowerCase().includes(q))
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

// reset collapsed groups when groupBy changes
function setGroupBy(val: GroupByKey) {
    emit('update:groupBy', val)
    collapsed.value = new Set()
    showGroupPicker.value = false
}

const page = usePage()

// ── Navigation ───────────────────────────────────────────────────────────────
function navigateTo(milestoneId: string | null) {
    open.value = false
    search.value = ''
    const pathname = page.url.split('?')[0]
    router.visit(pathname, {
        data: { ...props.currentFilters, milestone: milestoneId ?? 'backlog' },
        preserveScroll: false,
        preserveState: false,
        replace: true,
    })
}

// ── Status colors (popover list items) ───────────────────────────────────────
const statusClass: Record<string, string> = {
    active: 'bg-blue-500/20 text-blue-700 dark:text-blue-300',
    completed: 'bg-green-500/20 text-green-700 dark:text-green-300',
    paused: 'bg-yellow-500/20 text-yellow-700 dark:text-yellow-300',
    cancelled: 'bg-red-500/20 text-red-700 dark:text-red-300',
}

// ── Header stats computed (merged from PlannerMilestoneHeader) ─────────────────────
const statusVariant = computed((): 'default' | 'secondary' | 'outline' | 'destructive' => {
    const map: Record<string, 'default' | 'secondary' | 'outline' | 'destructive'> = {
        active: 'default',
        completed: 'secondary',
        paused: 'outline',
        cancelled: 'destructive',
    }
    return map[activeMilestone.value?.status ?? ''] ?? 'outline'
})

const priorityClass = computed(() => {
    const map: Record<string, string> = {
        critical: 'text-destructive',
        high: 'text-orange-500',
        medium: 'text-yellow-500',
        low: 'text-muted-foreground',
    }
    return map[activeMilestone.value?.priority ?? ''] ?? ''
})

const deadlineLabel = computed(() => {
    if (!activeMilestone.value?.end_at) return null
    const d = new Date(activeMilestone.value.end_at)
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
})

const progressBarColor = computed(() => {
    if (activeMilestone.value?.is_breached) return 'bg-destructive'
    if ((activeMilestone.value?.progress ?? 0) >= 100) return 'bg-green-500'
    return 'bg-primary'
})
</script>

<template>
    <div
        class="relative flex items-center gap-2 border-b border-border px-4 py-2 shrink-0 bg-card/50"
        :style="activeMilestone?.color ? { borderLeftColor: activeMilestone.color, borderLeftWidth: '3px' } : {}"
    >
        <!-- Milestone dropdown trigger -->
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <button
                    class="flex items-center gap-2 rounded-md border border-input bg-background px-3 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring max-w-75"
                    aria-haspopup="listbox"
                >
                    <!-- Active milestone indicator -->
                    <template v-if="activeMilestone">
                        <span class="truncate font-medium">{{ activeMilestone.title }}</span>
                        <!-- Mini arc -->
                        <svg class="size-3.5 shrink-0 -rotate-90" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                            <circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="2" class="opacity-15" />
                            <circle
                                cx="8" cy="8" r="5.5"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                :stroke-dasharray="CIRC"
                                :stroke-dashoffset="arcOffset(activeMilestone.progress)"
                                :class="activeMilestone.is_breached ? 'stroke-destructive' : activeMilestone.progress >= 100 ? 'stroke-green-500' : ''"
                            />
                        </svg>
                        <span class="text-xs text-muted-foreground shrink-0">{{ activeMilestone.progress }}%</span>
                    </template>
                    <template v-else>
                        <span class="text-muted-foreground">Backlog</span>
                    </template>
                    <ChevronDown class="size-3.5 shrink-0 text-muted-foreground ml-1" :class="open ? 'rotate-180' : ''" style="transition: transform 0.15s" />
                </button>
            </PopoverTrigger>

            <PopoverContent align="start" class="w-[320px] p-0 shadow-lg" :side-offset="6">

                <!-- Search + Group by row -->
                <div class="p-2 border-b border-border flex items-center gap-1.5">
                    <Input
                        v-model="search"
                        placeholder="Search milestones…"
                        class="h-8 text-sm flex-1"
                        autofocus
                    />
                    <!-- Group by button -->
                    <div class="relative shrink-0">
                        <button
                            class="flex items-center gap-1 h-8 px-2 rounded-md border border-input text-xs text-muted-foreground hover:bg-accent hover:text-foreground transition-colors"
                            :class="showGroupPicker ? 'bg-accent text-foreground' : ''"
                            @click.stop="showGroupPicker = !showGroupPicker"
                        >
                            <component :is="GROUP_OPTIONS.find(o => o.value === groupBy)?.icon" class="size-3" />
                            {{ GROUP_OPTIONS.find(o => o.value === groupBy)?.label }}
                        </button>
                        <!-- Group picker dropdown (inline, no nested popover) -->
                        <div
                            v-if="showGroupPicker"
                            class="absolute right-0 top-full mt-1 z-10 bg-popover border border-border rounded-md shadow-md py-1 min-w-27.5"
                        >
                            <button
                                v-for="opt in GROUP_OPTIONS"
                                :key="opt.value"
                                class="w-full flex items-center gap-2 px-3 py-1.5 text-xs hover:bg-accent hover:text-accent-foreground text-left transition-colors"
                                :class="groupBy === opt.value ? 'text-foreground font-medium' : 'text-muted-foreground'"
                                @click.stop="setGroupBy(opt.value)"
                            >
                                <component :is="opt.icon" class="size-3 shrink-0" />
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Grouped list -->
                <div class="max-h-85 overflow-y-auto py-1" @click.stop="showGroupPicker = false">
                    <template v-for="[groupKey, items] in grouped" :key="groupKey">
                        <!-- Collapsible group header -->
                        <button
                            class="w-full flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-muted-foreground uppercase tracking-wider hover:text-foreground hover:bg-accent/50 transition-colors"
                            @click.stop="toggleCollapse(groupKey)"
                        >
                            <ChevronDown
                                class="size-3 shrink-0 transition-transform duration-150"
                                :class="collapsed.has(groupKey) ? '-rotate-90' : ''"
                            />
                            {{ groupKey }}
                            <span class="ml-auto font-normal normal-case tracking-normal opacity-60">{{ items.length }}</span>
                        </button>

                        <!-- Milestone items (collapsible) -->
                        <template v-if="!collapsed.has(groupKey)">
                            <button
                                v-for="m in items"
                                :key="m.id"
                                class="relative w-full flex items-center gap-2.5 px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground text-left transition-colors overflow-hidden"
                                :class="activeMilestoneId === m.id ? 'bg-accent/60 font-medium' : ''"
                                :style="m.color ? { borderLeftColor: m.color, borderLeftWidth: '3px' } : {}"
                                @click="navigateTo(m.id)"
                            >
                                <!-- color bg tint -->
                                <div v-if="m.color" class="absolute inset-0 pointer-events-none" :style="{ backgroundColor: m.color + '0D' }" />

                                <!-- Title -->
                                <span class="relative flex-1 truncate ml-0.5">{{ m.title }}</span>

                                <!-- Breach dot -->
                                <span v-if="m.is_breached" class="size-1.5 rounded-full bg-destructive shrink-0" />

                                <!-- Status badge -->
                                <span
                                    class="text-[10px] px-1.5 py-0.5 rounded-full shrink-0"
                                    :class="statusClass[m.status] ?? 'bg-muted text-muted-foreground'"
                                >
                                    {{ m.status }}
                                </span>

                                <!-- Mini arc progress -->
                                <svg class="size-3.5 shrink-0 -rotate-90" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                    <circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="2" class="opacity-15" />
                                    <circle
                                        cx="8" cy="8" r="5.5"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        :stroke-dasharray="CIRC"
                                        :stroke-dashoffset="arcOffset(m.progress)"
                                        :class="m.is_breached ? 'stroke-destructive' : m.progress >= 100 ? 'stroke-green-500' : ''"
                                    />
                                </svg>

                                <!-- Event counts: per-status + total -->
                                <span class="flex items-center gap-1 shrink-0">
                                    <span v-if="m.in_progress_events_count" class="text-[10px] tabular-nums text-violet-400">{{ m.in_progress_events_count }}</span>
                                    <span v-if="m.upcoming_events_count" class="text-[10px] tabular-nums text-blue-400">{{ m.upcoming_events_count }}</span>
                                    <span v-if="m.draft_events_count" class="text-[10px] tabular-nums text-muted-foreground/60">{{ m.draft_events_count }}</span>
                                    <span class="text-xs tabular-nums text-muted-foreground ml-0.5">/ {{ m.total_events_count }}</span>
                                </span>
                            </button>
                        </template>
                    </template>

                    <!-- Empty state -->
                    <div v-if="grouped.length === 0" class="px-3 py-6 text-center text-sm text-muted-foreground">
                        No milestones match "{{ search }}"
                    </div>

                    <!-- Backlog option -->
                    <div class="border-t border-border mt-1">
                        <button
                            class="w-full flex items-center gap-2.5 px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground text-left transition-colors"
                            :class="activeMilestoneId === null ? 'bg-accent/60 font-medium' : 'text-muted-foreground'"
                            @click="navigateTo(null)"
                        >
                            <CalendarRange class="size-3 shrink-0" />
                            <span class="flex-1">Backlog</span>
                        </button>
                    </div>
                </div>

                <!-- Footer -->
                <div class="border-t border-border p-2 flex items-center gap-2">
                    <button
                        class="flex-1 flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors px-1 py-1"
                        @click="open = false; emit('openExplorer')"
                    >
                        <LayoutList class="size-3.5" />
                        Explore all milestones
                    </button>
                </div>
            </PopoverContent>
        </Popover>

        <!-- Status + flags inline (milestone active) -->
        <template v-if="activeMilestone">
            <Badge :variant="statusVariant" class="capitalize text-[10px] h-5 px-1.5 shrink-0">{{ activeMilestone.status }}</Badge>
            <Tooltip v-if="activeMilestone.deadline_type === 'hard'">
                <TooltipTrigger as-child>
                    <Lock class="size-3 shrink-0 text-muted-foreground/50 cursor-help" />
                </TooltipTrigger>
                <TooltipContent>Hard deadline — events cannot exceed this date</TooltipContent>
            </Tooltip>
            <Tooltip v-if="activeMilestone.is_breached">
                <TooltipTrigger as-child>
                    <AlertTriangle class="size-3.5 shrink-0 text-destructive cursor-help" />
                </TooltipTrigger>
                <TooltipContent>An event exceeds this deadline. Adjust events or convert to a soft deadline.</TooltipContent>
            </Tooltip>
        </template>

        <!-- Spacer -->
        <div class="flex-1" />

        <!-- Stats + actions (milestone active) -->
        <template v-if="activeMilestone">
            <div class="flex items-center gap-3 shrink-0 text-xs text-muted-foreground">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <span class="tabular-nums font-medium cursor-help w-8 text-right" :class="{ 'text-destructive': activeMilestone.is_breached }">
                            {{ activeMilestone.progress }}%
                        </span>
                    </TooltipTrigger>
                    <TooltipContent>{{ activeMilestone.progress_source === 'manual' ? 'Manual' : 'Derived' }} progress</TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <span class="flex items-center gap-1 cursor-help">
                            <CheckCircle2 class="size-3" />
                            {{ activeMilestone.completed_events_count }}/{{ activeMilestone.total_events_count }}
                        </span>
                    </TooltipTrigger>
                    <TooltipContent>{{ activeMilestone.completed_events_count }} of {{ activeMilestone.total_events_count }} events completed</TooltipContent>
                </Tooltip>
                <Tooltip v-if="deadlineLabel">
                    <TooltipTrigger as-child>
                        <span class="flex items-center gap-1 cursor-help" :class="{ 'text-destructive font-medium': activeMilestone.is_breached }">
                            <Clock class="size-3" />
                            {{ deadlineLabel }}
                        </span>
                    </TooltipTrigger>
                    <TooltipContent>{{ activeMilestone.deadline_type === 'hard' ? 'Hard' : 'Soft' }} deadline</TooltipContent>
                </Tooltip>
                <Tooltip>
                    <TooltipTrigger as-child>
                        <span class="capitalize cursor-help" :class="priorityClass">{{ activeMilestone.priority }}</span>
                    </TooltipTrigger>
                    <TooltipContent>Priority</TooltipContent>
                </Tooltip>
            </div>
            <div class="flex items-center gap-1">
                <Tooltip>
                    <TooltipTrigger as-child>
                        <Button variant="ghost" size="icon-sm" @click="emit('edit', activeMilestone)">
                            <Edit2 class="size-3.5" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Edit milestone</TooltipContent>
                </Tooltip>
                <Button variant="outline" size="sm" class="h-7 gap-1 text-xs" @click="emit('createEvent')">
                    <Plus class="size-3.5" />
                    Add event
                </Button>
            </div>
        </template>

        <!-- Backlog: just Add event -->
        <template v-if="!activeMilestone">
            <Button variant="outline" size="sm" class="h-7 gap-1.5 text-xs" @click="emit('createEvent')">
                <Plus class="size-3.5" />
                Add event
            </Button>
        </template>

        <!-- Progress strip -->
        <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary/10">
            <div
                class="h-full transition-all duration-500"
                :class="progressBarColor"
                :style="activeMilestone ? { width: `${Math.min(activeMilestone.progress, 100)}%` } : { width: '0%' }"
            />
        </div>
    </div>

    <!-- Breach warning callout -->
    <div
        v-if="activeMilestone?.is_breached"
        class="flex items-center gap-2 px-4 py-2 bg-destructive/5 border-b border-destructive/20 text-destructive text-xs"
    >
        <AlertTriangle class="size-3.5 shrink-0" />
        <span class="flex-1">
            <span class="font-medium">{{ activeMilestone.breach_count }} event{{ activeMilestone.breach_count !== 1 ? 's' : '' }} exceed{{ activeMilestone.breach_count === 1 ? 's' : '' }} this hard deadline.</span>
            Adjust event dates, move events to backlog, or convert this milestone to a soft deadline.
        </span>
        <Button
            variant="outline"
            size="sm"
            class="h-6 px-2 text-[11px] border-destructive/30 text-destructive hover:bg-destructive/10 hover:text-destructive hover:border-destructive/50 shrink-0"
            @click="emit('edit', activeMilestone)"
        >
            Resolve
        </Button>
    </div>
</template>
