<script setup lang="ts">
import { ref, computed } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { CalendarRange, X, Plus, Search } from 'lucide-vue-next'
import {
    Drawer,
    DrawerContent,
    DrawerHeader,
    DrawerTitle,
    DrawerDescription,
    DrawerClose,
    DrawerFooter,
} from '@/components/ui/drawer'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import type { PlannerMilestone } from '@/types/planner'

const props = defineProps<{
    open: boolean
    milestones: PlannerMilestone[]
    activeMilestoneId: string | null
    currentFilters: Record<string, unknown>
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
    createMilestone: []
}>()

// ── Search ───────────────────────────────────────────────────────────────────
const search = ref('')

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return props.milestones
    return props.milestones.filter((m) => m.title.toLowerCase().includes(q))
})

// ── Quarter grouping ─────────────────────────────────────────────────────────
function getQuarter(dateStr: string | null | undefined): string {
    if (!dateStr) return 'Unscheduled'
    const d = new Date(dateStr)
    if (isNaN(d.getTime())) return 'Unscheduled'
    const q = Math.floor(d.getMonth() / 3) + 1
    return `Q${q} ${d.getFullYear()}`
}

function quarterSortKey(label: string): string {
    if (label === 'Unscheduled') return 'Z'
    const [q, year] = label.split(' ')
    return `${year}-${q.slice(1)}`
}

const grouped = computed(() => {
    const map = new Map<string, PlannerMilestone[]>()
    for (const m of filtered.value) {
        const key = getQuarter(m.end_at ?? m.start_at)
        if (!map.has(key)) map.set(key, [])
        map.get(key)!.push(m)
    }
    return [...map.entries()].sort(([a], [b]) =>
        quarterSortKey(a).localeCompare(quarterSortKey(b)),
    )
})

// ── SVG arc ──────────────────────────────────────────────────────────────────
const CIRC = 2 * Math.PI * 5.5

function arcOffset(progress: number): number {
    return CIRC * (1 - Math.min(progress, 100) / 100)
}

// ── Status styles ─────────────────────────────────────────────────────────────
const statusClass: Record<string, string> = {
    active: 'bg-blue-500/15 text-blue-700 dark:text-blue-300',
    completed: 'bg-green-500/15 text-green-700 dark:text-green-300',
    paused: 'bg-yellow-500/15 text-yellow-700 dark:text-yellow-300',
    cancelled: 'bg-red-500/15 text-red-700 dark:text-red-300',
}

const priorityClass: Record<string, string> = {
    critical: 'text-destructive',
    high: 'text-orange-500',
    medium: 'text-yellow-500',
    low: 'text-muted-foreground',
}

// ── Navigate ──────────────────────────────────────────────────────────────────
const explorerPage = usePage()

function navigateTo(milestoneId: string | null) {
    emit('update:open', false)
    router.visit(explorerPage.url.split('?')[0], {
        data: { ...props.currentFilters, milestone: milestoneId ?? undefined },
        preserveScroll: false,
        preserveState: false,
        replace: true,
    })
}

// ── Date format ───────────────────────────────────────────────────────────────
function formatDate(d: string | null | undefined): string {
    if (!d) return '—'
    return new Date(d).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
}
</script>

<template>
    <Drawer :open="open" direction="bottom" @update:open="emit('update:open', $event)">
        <DrawerContent class="h-[95vh] flex flex-col p-0 overflow-hidden">
            <DrawerHeader class="flex-row items-center justify-between px-5 py-4 border-b border-border shrink-0">
                <div class="flex-1 min-w-0">
                    <DrawerTitle>Milestones</DrawerTitle>
                    <DrawerDescription class="text-xs mt-0.5">
                        {{ milestones.length }} milestone{{ milestones.length === 1 ? '' : 's' }} total
                    </DrawerDescription>
                </div>
                <DrawerClose as-child>
                    <Button variant="ghost" size="icon" class="size-8 shrink-0 text-muted-foreground hover:text-foreground">
                        <X class="size-4" />
                    </Button>
                </DrawerClose>
            </DrawerHeader>

            <!-- Search -->
            <div class="px-4 py-3 border-b border-border shrink-0">
                <div class="relative">
                    <Search class="absolute left-2.5 top-1/2 -translate-y-1/2 size-3.5 text-muted-foreground pointer-events-none" />
                    <Input v-model="search" placeholder="Search milestones…" class="pl-8 h-8 text-sm" />
                </div>
            </div>

            <!-- Grouped list -->
            <div class="flex-1 min-h-0 overflow-y-auto">
                <template v-for="[quarter, items] in grouped" :key="quarter">
                    <!-- Quarter header -->
                    <div class="sticky top-0 bg-background/95 backdrop-blur-sm px-4 py-2 border-b border-border/50">
                        <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                            {{ quarter }}
                        </span>
                    </div>

                    <!-- Milestone rows -->
                    <button
                        v-for="m in items"
                        :key="m.id"
                        class="w-full flex items-start gap-3 px-4 py-3 text-left hover:bg-accent/50 transition-colors border-b border-border/30 last:border-0"
                        :class="activeMilestoneId === m.id ? 'bg-accent/60' : ''"
                        @click="navigateTo(m.id)"
                    >
                        <!-- Color dot / icon -->
                        <div class="mt-0.5 shrink-0">
                            <span
                                v-if="m.color"
                                class="block size-3 rounded-full"
                                :style="{ backgroundColor: m.color }"
                            />
                            <CalendarRange v-else class="size-3.5 text-muted-foreground" />
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <!-- Title + badges row -->
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-sm font-medium truncate">{{ m.title }}</span>
                                <span
                                    class="text-[10px] px-1.5 py-0.5 rounded-full shrink-0"
                                    :class="statusClass[m.status] ?? 'bg-muted text-muted-foreground'"
                                >
                                    {{ m.status }}
                                </span>
                                <span v-if="m.is_breached" class="text-[10px] px-1.5 py-0.5 rounded-full bg-destructive/15 text-destructive shrink-0">
                                    breached
                                </span>
                            </div>

                            <!-- Dates row -->
                            <div class="flex items-center gap-3 mt-1 text-xs text-muted-foreground">
                                <span>{{ formatDate(m.start_at) }} → {{ formatDate(m.end_at) }}</span>
                                <span :class="priorityClass[m.priority]">{{ m.priority }}</span>
                            </div>

                            <!-- Progress bar + event count -->
                            <div class="flex items-center gap-2 mt-2">
                                <div class="flex-1 h-1 bg-border rounded-full overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="m.is_breached ? 'bg-destructive' : m.progress >= 100 ? 'bg-green-500' : 'bg-primary'"
                                        :style="{ width: `${Math.min(m.progress, 100)}%` }"
                                    />
                                </div>
                                <span class="text-[10px] text-muted-foreground shrink-0 tabular-nums">
                                    {{ m.progress }}%
                                </span>
                                <span
                                    v-if="m.total_events_count"
                                    class="text-[10px] text-muted-foreground shrink-0 tabular-nums"
                                >
                                    {{ m.completed_events_count }}/{{ m.total_events_count }} events
                                </span>
                            </div>
                        </div>

                        <!-- Mini arc -->
                        <svg class="size-5 shrink-0 -rotate-90 mt-0.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
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

                <!-- Backlog row -->
                <button
                    class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-accent/50 transition-colors border-t border-border text-muted-foreground"
                    :class="activeMilestoneId === null ? 'bg-accent/60 text-foreground font-medium' : ''"
                    @click="navigateTo(null)"
                >
                    <CalendarRange class="size-3.5 shrink-0" />
                    <span class="text-sm">Backlog</span>
                    <span class="text-xs ml-auto">events without a milestone</span>
                </button>

                <!-- Empty search state -->
                <div v-if="grouped.length === 0 && search" class="px-4 py-12 text-center text-sm text-muted-foreground">
                    No milestones match "{{ search }}"
                </div>
            </div>

            <!-- Footer -->
            <DrawerFooter class="px-4 py-3 border-t border-border shrink-0">
                <Button class="w-full gap-2" @click="emit('update:open', false); emit('createMilestone')">
                    <Plus class="size-4" />
                    New milestone
                </Button>
            </DrawerFooter>
        </DrawerContent>
    </Drawer>
</template>
