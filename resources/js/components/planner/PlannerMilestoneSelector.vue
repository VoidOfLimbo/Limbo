<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { ChevronDown, ChevronRight, CalendarRange, Plus, LayoutList, Group } from 'lucide-vue-next'
import { Popover, PopoverTrigger, PopoverContent } from '@/components/ui/popover'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import type { PlannerMilestone } from '@/types/planner'

const props = defineProps<{
    milestones: PlannerMilestone[]
    activeMilestoneId: string | null
    currentFilters: Record<string, unknown>
}>()

const emit = defineEmits<{
    createMilestone: []
    openExplorer: []
}>()

// ── State ────────────────────────────────────────────────────────────────────
const open = ref(false)
const search = ref('')

// ── Group by ─────────────────────────────────────────────────────────────────
type GroupByKey = 'quarter' | 'status' | 'priority'
const GROUP_OPTIONS: { value: GroupByKey; label: string }[] = [
    { value: 'quarter', label: 'Quarter' },
    { value: 'status', label: 'Status' },
    { value: 'priority', label: 'Priority' },
]
const groupBy = ref<GroupByKey>('quarter')
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

function getGroupKey(m: PlannerMilestone, by: GroupByKey): string {
    if (by === 'quarter') return getQuarter(m.end_at ?? m.start_at)
    if (by === 'status') return m.status.charAt(0).toUpperCase() + m.status.slice(1)
    if (by === 'priority') return m.priority.charAt(0).toUpperCase() + m.priority.slice(1)
    return 'Other'
}

const STATUS_ORDER = ['Active', 'Paused', 'Completed', 'Cancelled']
const PRIORITY_ORDER = ['Critical', 'High', 'Medium', 'Low']

function sortKey(label: string, by: GroupByKey): string {
    if (by === 'quarter') {
        if (label === 'Unscheduled') return 'Z'
        const [q, year] = label.split(' ')
        return `${year}-${q.slice(1).padStart(2, '0')}`
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
        const key = getGroupKey(m, groupBy.value)
        if (!map.has(key)) map.set(key, [])
        map.get(key)!.push(m)
    }
    return [...map.entries()].sort(([a], [b]) =>
        sortKey(a, groupBy.value).localeCompare(sortKey(b, groupBy.value)),
    )
})

// reset collapsed groups when groupBy changes
function setGroupBy(val: GroupByKey) {
    groupBy.value = val
    collapsed.value = new Set()
    showGroupPicker.value = false
}

// ── Navigation ───────────────────────────────────────────────────────────────
function navigateTo(milestoneId: string | null) {
    open.value = false
    search.value = ''
    router.visit(window.location.pathname, {
        data: { ...props.currentFilters, milestone: milestoneId ?? 'backlog' },
        preserveScroll: false,
        preserveState: false,
        replace: true,
    })
}

// ── Status colors ─────────────────────────────────────────────────────────────
const statusClass: Record<string, string> = {
    active: 'bg-blue-500/20 text-blue-700 dark:text-blue-300',
    completed: 'bg-green-500/20 text-green-700 dark:text-green-300',
    paused: 'bg-yellow-500/20 text-yellow-700 dark:text-yellow-300',
    cancelled: 'bg-red-500/20 text-red-700 dark:text-red-300',
}
</script>

<template>
    <div class="flex items-center gap-2 border-b border-border px-4 py-2 shrink-0">
        <!-- Milestone dropdown trigger -->
        <Popover v-model:open="open">
            <PopoverTrigger as-child>
                <button
                    class="flex items-center gap-2 rounded-md border border-input bg-background px-3 py-1.5 text-sm hover:bg-accent hover:text-accent-foreground transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring max-w-[300px]"
                    aria-haspopup="listbox"
                >
                    <!-- Active milestone indicator -->
                    <template v-if="activeMilestone">
                        <span
                            v-if="activeMilestone.color"
                            class="size-2 rounded-full shrink-0"
                            :style="{ backgroundColor: activeMilestone.color }"
                        />
                        <CalendarRange v-else class="size-3.5 shrink-0 text-muted-foreground" />
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
                        <CalendarRange class="size-3.5 shrink-0 text-muted-foreground" />
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
                            <Group class="size-3" />
                            {{ GROUP_OPTIONS.find(o => o.value === groupBy)?.label }}
                        </button>
                        <!-- Group picker dropdown (inline, no nested popover) -->
                        <div
                            v-if="showGroupPicker"
                            class="absolute right-0 top-full mt-1 z-10 bg-popover border border-border rounded-md shadow-md py-1 min-w-[110px]"
                        >
                            <button
                                v-for="opt in GROUP_OPTIONS"
                                :key="opt.value"
                                class="w-full flex items-center gap-2 px-3 py-1.5 text-xs hover:bg-accent hover:text-accent-foreground text-left transition-colors"
                                :class="groupBy === opt.value ? 'text-foreground font-medium' : 'text-muted-foreground'"
                                @click.stop="setGroupBy(opt.value)"
                            >
                                <span
                                    class="size-1.5 rounded-full shrink-0"
                                    :class="groupBy === opt.value ? 'bg-primary' : 'bg-muted-foreground/40'"
                                />
                                {{ opt.label }}
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Grouped list -->
                <div class="max-h-[340px] overflow-y-auto py-1" @click.stop="showGroupPicker = false">
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
                                class="w-full flex items-center gap-2.5 px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground text-left transition-colors"
                                :class="activeMilestoneId === m.id ? 'bg-accent/60 font-medium' : ''"
                                @click="navigateTo(m.id)"
                            >
                                <!-- Color dot -->
                                <span
                                    v-if="m.color"
                                    class="size-2 rounded-full shrink-0 ml-4"
                                    :style="{ backgroundColor: m.color }"
                                />
                                <CalendarRange v-else class="size-3 shrink-0 text-muted-foreground ml-4" />

                                <!-- Title -->
                                <span class="flex-1 truncate">{{ m.title }}</span>

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

        <!-- Spacer -->
        <div class="flex-1" />

        <!-- New milestone button (always visible) -->
        <Button variant="outline" size="sm" class="h-8 gap-1.5 shrink-0" @click="emit('createMilestone')">
            <Plus class="size-3.5" />
            <span class="hidden sm:inline text-xs">New milestone</span>
        </Button>
    </div>
</template>
