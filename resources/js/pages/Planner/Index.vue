<script setup lang="ts">
import { Head, usePage, setLayoutProps } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useElementSize } from '@vueuse/core'
import { toast } from 'vue-sonner'
import { planner as plannerRoute } from '@/routes'
import PlannerMilestoneSelector from '@/components/planner/PlannerMilestoneSelector.vue'
import PlannerMilestoneExplorer from '@/components/planner/PlannerMilestoneExplorer.vue'
import PlannerFilters from '@/components/planner/PlannerFilters.vue'
import PlannerEventList from '@/components/planner/PlannerEventList.vue'
import PlannerEventDrawer from '@/components/planner/PlannerEventDrawer.vue'
import PlannerMilestoneDrawer from '@/components/planner/PlannerMilestoneDrawer.vue'
import PlannerSnoozePopover from '@/components/planner/PlannerSnoozePopover.vue'
import PlannerViewSwitcher from '@/components/planner/PlannerViewSwitcher.vue'
import PlannerViewTabs from '@/components/planner/PlannerViewTabs.vue'
import PlannerFieldManager from '@/components/planner/PlannerFieldManager.vue'
import PlannerTableView from '@/components/planner/PlannerTableView.vue'
import PlannerBoardView from '@/components/planner/PlannerBoardView.vue'
import PlannerRoadmapView from '@/components/planner/PlannerRoadmapView.vue'
import PlannerMilestoneDashboard from '@/components/planner/PlannerMilestoneDashboard.vue'
import { CalendarDays, Check, ChevronDown, Maximize2, Search, Share2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { destroy, snooze as snoozeEvent, update as updateEvent, store as storeEvent } from '@/actions/App/Http/Controllers/Planner/EventController'
import { update as updateMilestone } from '@/actions/App/Http/Controllers/Planner/MilestoneController'
import { usePlannerFilters } from '@/composables/planner/usePlannerFilters'
import { usePlannerStore } from '@/stores/planner'
import type { PaginatedData, PlannerEvent, PlannerField, PlannerFilters as PlannerFilterValues, PlannerMilestone, PlannerTag, PlannerView } from '@/types/planner'
import type { ZoomLevel } from '@/composables/planner/useRoadmapLayout'

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Planner', href: plannerRoute() }],
    },
})

const props = defineProps<{
    milestones: PlannerMilestone[]
    activeMilestoneId: string | null
    showingDashboard: boolean
    filters: PlannerFilterValues
    perPage?: number
    events?: PaginatedData<PlannerEvent>
    tags?: PlannerTag[]
    fields?: PlannerField[]
    savedViews?: PlannerView[]
}>()

// ── Active milestone object ────────────────────────────────────────────────────────────────
const activeMilestone = computed(() =>
    props.milestones.find((m) => m.id === props.activeMilestoneId) ?? null,
)

// ── Dynamic breadcrumbs ────────────────────────────────────────────────────────────────
watch(
    [() => props.showingDashboard, () => props.activeMilestoneId, () => activeMilestone.value],
    () => {
        if (props.showingDashboard) {
            setLayoutProps({ breadcrumbs: [{ title: 'Planner', href: plannerRoute() }] })
            return
        }
        if (props.activeMilestoneId === null) {
            setLayoutProps({
                breadcrumbs: [
                    { title: 'Planner', href: plannerRoute() },
                    { title: 'Backlog' },
                ],
            })
            return
        }
        const milestone = activeMilestone.value
        if (!milestone) return
        setLayoutProps({
            breadcrumbs: [
                { title: 'Planner', href: plannerRoute() },
                { title: milestone.title },
            ],
        })
    },
    { immediate: true },
)

// ── View store ───────────────────────────────────────────────────────────────
const plannerStore = usePlannerStore()

// ── Roadmap controls ─────────────────────────────────────────────────────────
const ROADMAP_STORAGE_ZOOM_KEY = 'planner:roadmapZoom'
const ROADMAP_STORAGE_FIT_KEY = 'planner:roadmapFit'
const VALID_ZOOMS: ZoomLevel[] = ['week', 'month', 'quarter', 'year']
const _storedZoom = typeof localStorage !== 'undefined' ? localStorage.getItem(ROADMAP_STORAGE_ZOOM_KEY) : null
const _storedFit = typeof localStorage !== 'undefined' ? localStorage.getItem(ROADMAP_STORAGE_FIT_KEY) === '1' : false
const roadmapZoom = ref<ZoomLevel>(VALID_ZOOMS.includes(_storedZoom as ZoomLevel) ? (_storedZoom as ZoomLevel) : 'month')
const roadmapFit = ref<boolean>(_storedFit)
const showDependencies = ref(false)
const roadmapViewRef = ref<InstanceType<typeof PlannerRoadmapView> | null>(null)
const zoomPickerOpen = ref(false)

const ZOOM_LEVELS: { value: ZoomLevel; label: string }[] = [
    { value: 'week', label: 'Week' },
    { value: 'month', label: 'Month' },
    { value: 'quarter', label: 'Quarter' },
    { value: 'year', label: 'Year' },
]

function setRoadmapZoom(z: ZoomLevel) {
    roadmapZoom.value = z
    roadmapFit.value = false
    if (typeof localStorage !== 'undefined') {
        localStorage.setItem(ROADMAP_STORAGE_ZOOM_KEY, z)
        localStorage.setItem(ROADMAP_STORAGE_FIT_KEY, '0')
    }
    zoomPickerOpen.value = false
}

function toggleFitToScreen() {
    roadmapFit.value = !roadmapFit.value
    if (typeof localStorage !== 'undefined') {
        localStorage.setItem(ROADMAP_STORAGE_FIT_KEY, roadmapFit.value ? '1' : '0')
    }
    zoomPickerOpen.value = false
}

// ── List columns ─────────────────────────────────────────────────────────────
const listViewport = ref<HTMLElement | null>(null)
const { width: listViewportWidth } = useElementSize(listViewport)
const maxListCols = computed(() => Math.max(1, Math.min(4, Math.floor(listViewportWidth.value / 300))) as 1 | 2 | 3 | 4)
const STORAGE_COLUMNS_KEY = 'planner:listColumns'
const _storedCols = typeof localStorage !== 'undefined' ? Number(localStorage.getItem(STORAGE_COLUMNS_KEY)) : 1
const listColumns = ref<1 | 2 | 3 | 4>(([1, 2, 3, 4].includes(_storedCols) ? _storedCols : 1) as 1 | 2 | 3 | 4)
watch(maxListCols, (max) => {
    if (listViewportWidth.value > 0 && listColumns.value > max) listColumns.value = max as 1 | 2 | 3 | 4
}, { immediate: true })
function setListColumns(col: 1 | 2 | 3 | 4) {
    listColumns.value = col
    if (typeof localStorage !== 'undefined') localStorage.setItem(STORAGE_COLUMNS_KEY, String(col))
}
const activeViewId = ref<string | null>(null)
const fieldManagerOpen = ref(false)
const page = usePage()

// ── Per-page (resets on hard refresh; backend default = 20) ──────────────────
const localPerPage = ref<number>(props.perPage ?? 20)
const displayPerPage = ref<string>(String(localPerPage.value))
watch(localPerPage, (val) => {
    displayPerPage.value = String(val)
})

// ── Preferences helper: inject view + per_page as headers, not URL params ─────
function plannerVisit(url: string, options: Parameters<typeof router.visit>[1] = {}) {
    return router.visit(url, {
        ...options,
        headers: {
            'X-Planner-View': plannerStore.activeView,
            'X-Planner-Per-Page': String(localPerPage.value),
            ...(options.headers ?? {}),
        },
    })
}

// ── Filters ──────────────────────────────────────────────────────────────────
const { applyFilters, clearFilters, hasActiveFilters } = usePlannerFilters(props.filters, props.activeMilestoneId, () => ({
    'X-Planner-View': plannerStore.activeView,
    'X-Planner-Per-Page': String(localPerPage.value),
}))

const currentFilters = computed(() => ({
    milestone: props.activeMilestoneId,
    ...props.filters,
}))

// Reload events when switching between views so the deferred events prop is
// re-resolved (per-page now applies to all views consistently).
watch(() => plannerStore.activeView, () => {
    plannerVisit(page.url.split('?')[0], {
        data: { ...currentFilters.value },
        preserveScroll: true,
        only: ['events', 'perPage'],
        replace: true,
        reset: ['events'],
    })
})

// ── Client-side event accumulation (list view load more) ─────────────────────
const allListEvents = ref<PlannerEvent[]>([])

watch(
    () => props.events,
    (newEvents) => {
        if (!newEvents) {
            allListEvents.value = []
            return
        }
        if (newEvents.current_page === 1) {
            allListEvents.value = [...newEvents.data]
        } else {
            allListEvents.value = [...allListEvents.value, ...newEvents.data]
        }
    },
    { immediate: true },
)

const listEvents = computed(() => {
    if (!props.events) return undefined
    return { ...props.events, data: allListEvents.value }
})

// ── Event drawer ─────────────────────────────────────────────────────────────
const eventDrawerOpen = ref(false)
const editingEvent = ref<PlannerEvent | null>(null)

function openCreateEvent() {
    editingEvent.value = null
    eventDrawerOpen.value = true
}

function openEditEvent(event: PlannerEvent) {
    editingEvent.value = event
    eventDrawerOpen.value = true
}

// ── Milestone explorer drawer ─────────────────────────────────────────────────
const milestoneExplorerOpen = ref(false)

// ── Milestone drawer ─────────────────────────────────────────────────────────
const milestoneDrawerOpen = ref(false)
const editingMilestone = ref<PlannerMilestone | null>(null)

function openCreateMilestone() {
    editingMilestone.value = null
    milestoneDrawerOpen.value = true
}

function openEditMilestone(milestone: PlannerMilestone) {
    editingMilestone.value = milestone
    milestoneDrawerOpen.value = true
}

// ── Snooze popover ───────────────────────────────────────────────────────────
const snoozingEvent = ref<PlannerEvent | null>(null)

function openSnooze(event: PlannerEvent) {
    snoozingEvent.value = event
}

function handleSnooze(eventId: string, until: string) {
    const def = snoozeEvent(eventId)
    router.visit(def.url, {
        method: def.method,
        data: { snoozed_until: until },
        preserveScroll: true,
        only: ['events'],
        onSuccess: () => {
            const title = snoozingEvent.value?.title ?? 'Event'
            snoozingEvent.value = null
            toast.success('Event snoozed', {
                description: `"${title}" will resurface at the selected time.`,
            })
        },
    })
}

// ── Delete confirm ───────────────────────────────────────────────────────────
const deletingEvent = ref<PlannerEvent | null>(null)
const deleteProcessing = ref(false)

function openDelete(event: PlannerEvent) {
    deletingEvent.value = event
}

function confirmDelete() {
    if (!deletingEvent.value) return
    deleteProcessing.value = true
    const def = destroy(deletingEvent.value.id)
    router.visit(def.url, {
        method: def.method,
        preserveScroll: true,
        only: ['events', 'milestones'],
        onSuccess: () => { deletingEvent.value = null },
        onFinish: () => { deleteProcessing.value = false },
    })
}

// ── Status toggle ─────────────────────────────────────────────────────────────
function toggleStatus(event: PlannerEvent) {
    const newStatus = event.status === 'completed' ? 'upcoming' : 'completed'
    const def = updateEvent(event.id)
    router.visit(def.url, {
        method: def.method,
        data: { status: newStatus },
        preserveScroll: true,
        only: ['events', 'milestones'],
    })
}

// ── Duplicate ─────────────────────────────────────────────────────────────────
function duplicateEvent(event: PlannerEvent) {
    const def = storeEvent()
    router.visit(def.url, {
        method: def.method,
        data: {
            title: `${event.title} (copy)`,
            description: event.description ?? undefined,
            type: event.type,
            status: 'upcoming',
            priority: event.priority,
            milestone_id: event.milestone_id ?? undefined,
            start_at: event.start_at ?? undefined,
            end_at: event.end_at ?? undefined,
            is_all_day: event.is_all_day,
            location: event.location ?? undefined,
            visibility: event.visibility,
            tag_ids: event.tags.map((t) => t.id),
        },
        preserveScroll: true,
        only: ['events'],
    })
}

// ── Roadmap reschedule ───────────────────────────────────────────────────────
// Single Inertia visit: Inertia forwards `only` + headers through the back()
// redirect, so one round-trip is enough. preserveState keeps expanded rows +
// scroll position intact without a second plannerVisit call.
function handleRoadmapReschedule(id: string, kind: 'milestone' | 'event', newStart: string, newEnd: string) {
    const def = kind === 'event' ? updateEvent(id) : updateMilestone(id)

    const data: Record<string, unknown> = { start_at: newStart, end_at: newEnd }

    if (kind === 'milestone') {
        // Force manual so recalculateDerivedDates() won't overwrite our new dates
        data.duration_source = 'manual'

        // Hard-deadline milestones protect end_at — omit it to avoid silent discard
        const milestone = props.milestones.find((m) => m.id === id)
        if (milestone?.deadline_type === 'hard' && milestone.end_at !== null) {
            delete data.end_at
        }
    }

    router.visit(def.url, {
        method: def.method,
        data,
        preserveScroll: true,
        preserveState: true,
        only: ['events', 'milestones'],
        headers: {
            'X-Planner-View': 'roadmap',
            'X-Planner-Per-Page': String(localPerPage.value),
        },
    })
}

// ── Move to backlog ───────────────────────────────────────────────────────────
function moveToBacklog(event: PlannerEvent) {
    const def = updateEvent(event.id)
    router.visit(def.url, {
        method: def.method,
        data: { milestone_id: null },
        preserveScroll: true,
        only: ['events', 'milestones'],
        onSuccess: () => {
            toast.success('Moved to backlog', {
                description: `"${event.title}" has been removed from its milestone.`,
            })
        },
    })
}

// ── Load more ────────────────────────────────────────────────────────────────
const loadingMore = ref(false)

function loadMore() {
    if (!props.events?.next_page_url) return
    const nextUrl = new URL(props.events.next_page_url, 'http://x')
    const nextPage = nextUrl.searchParams.get('page') ?? '2'
    loadingMore.value = true
    plannerVisit(page.url.split('?')[0], {
        data: { ...currentFilters.value, page: nextPage },
        preserveScroll: true,
        preserveState: true,
        only: ['events'],
        replace: true,
        onFinish: () => { loadingMore.value = false },
    })
}

const loadingAll = ref(false)
function loadAll() {
    loadingAll.value = true
    router.reload({
        only: ['events'],
        reset: ['events'],
        preserveScroll: true,
        headers: {
            'X-Planner-View': plannerStore.activeView,
            'X-Planner-Per-Page': String(localPerPage.value),
            'X-Planner-All': '1',
        },
        onFinish: () => { loadingAll.value = false },
    })
}

// ── Per-page ──────────────────────────────────────────────────────────────────
let _perPageTimer: ReturnType<typeof setTimeout> | null = null

function changePerPage(n: string) {
    displayPerPage.value = n  // update display freely — no clamping mid-type
    if (_perPageTimer !== null) clearTimeout(_perPageTimer)
    _perPageTimer = setTimeout(() => {
        _perPageTimer = null
        const clamped = Math.min(100, Math.max(5, Number(n) || 20))
        localPerPage.value = clamped  // triggers watch → updates displayPerPage
        router.reload({
            only: ['events', 'perPage'],
            reset: ['events'],
            preserveScroll: true,
            headers: {
                'X-Planner-View': plannerStore.activeView,
                'X-Planner-Per-Page': String(clamped),
            },
        })
    }, 600)
}

// ── Table pagination ──────────────────────────────────────────────────────────
const loadingTablePage = ref(false)

function tableGoToPage(targetPage: number) {
    loadingTablePage.value = true
    plannerVisit(page.url.split('?')[0], {
        data: { ...currentFilters.value, page: targetPage },
        preserveScroll: true,
        only: ['events'],
        replace: true,
        onFinish: () => { loadingTablePage.value = false },
    })
}
</script>

<template>
    <Head title="Planner" />

    <!-- Full-height flex column inside the app layout content area -->
    <div class="flex flex-col h-full overflow-hidden">

        <!-- ── Dashboard: grid/list of all milestones ──────────────────────── -->
        <PlannerMilestoneDashboard
            v-if="showingDashboard"
            :milestones="milestones"
            :active-milestone-id="activeMilestoneId"            v-model:group-by="plannerStore.groupBy"            @create-milestone="openCreateMilestone"
        />

        <!-- ── Milestone detail view ───────────────────────────────────────── -->
        <template v-else>
            <!-- Single-row: milestone selector + status + stats + actions -->
            <PlannerMilestoneSelector
                :milestones="milestones"
                :active-milestone-id="activeMilestoneId"
                :current-filters="currentFilters"
                v-model:group-by="plannerStore.groupBy"
                @open-explorer="milestoneExplorerOpen = true"
                @edit="openEditMilestone"
                @create-event="openCreateEvent"
            />

            <!-- Saved view tabs -->
            <PlannerViewTabs
                v-if="(savedViews ?? []).length > 0"
                :views="savedViews ?? []"
                :active-view-id="activeViewId"
                :milestone-id="activeMilestoneId"
                @activate="activeViewId = $event"
                @delete="activeViewId === $event ? (activeViewId = null) : null"
            />

            <!-- Filters + view switcher -->
            <PlannerFilters
                :filters="filters"
                :tags="tags ?? []"
                :is-active="hasActiveFilters(filters)"
                @change="applyFilters"
            >
                <template #trailing>
                    <!-- Column picker (list view only) -->
                    <div v-if="plannerStore.activeView === 'list'" class="hidden sm:flex items-center border border-border rounded-md overflow-hidden">
                        <Tooltip v-for="col in ([1, 2, 3, 4] as const).slice(0, maxListCols)" :key="col">
                            <TooltipTrigger as-child>
                                <button
                                    type="button"
                                    class="px-2 py-1 text-[11px] font-mono transition-colors"
                                    :class="[
                                        col > 1 ? 'border-l border-border' : '',
                                        listColumns === col ? 'bg-accent text-foreground' : 'text-muted-foreground hover:bg-accent/50',
                                    ]"
                                    @click="setListColumns(col)"
                                >{{ col }}</button>
                            </TooltipTrigger>
                            <TooltipContent>{{ col }} column{{ col !== 1 ? 's' : '' }}</TooltipContent>
                        </Tooltip>
                    </div>
                    <Tooltip v-if="plannerStore.activeView === 'list' || plannerStore.activeView === 'table'">
                        <TooltipTrigger as-child>
                            <div class="flex items-center gap-1 text-xs text-muted-foreground">
                                <input
                                    type="number"
                                    :value="displayPerPage"
                                    min="5"
                                    max="100"
                                    aria-label="Events per page"
                                    class="w-12 h-7 rounded-md border border-border bg-transparent px-1.5 text-xs text-center text-muted-foreground focus:outline-none focus:ring-1 focus:ring-ring appearance-none [&::-webkit-inner-spin-button]:appearance-auto"
                                    @input="changePerPage(($event.target as HTMLInputElement).value)"
                                />
                                <span>/ page</span>
                            </div>
                        </TooltipTrigger>
                        <TooltipContent>Events per page (5–100)</TooltipContent>
                    </Tooltip>
                    <!-- Roadmap-specific controls -->
                    <template v-if="plannerStore.activeView === 'roadmap'">
                        <Popover v-model:open="zoomPickerOpen">
                            <PopoverTrigger as-child>
                                <button
                                    type="button"
                                    class="flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-md border border-border text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                                >
                                    <Search class="size-3 opacity-60" />
                                    {{ roadmapFit ? 'Fit' : (ZOOM_LEVELS.find((l) => l.value === roadmapZoom)?.label ?? roadmapZoom) }}
                                    <ChevronDown class="size-3 opacity-60" />
                                </button>
                            </PopoverTrigger>
                            <PopoverContent align="start" class="w-44 p-1" :side-offset="6">
                                <p class="px-2 py-1.5 text-[10px] font-semibold text-muted-foreground uppercase tracking-wide">Zoom level</p>
                                <button
                                    v-for="level in ZOOM_LEVELS"
                                    :key="level.value"
                                    type="button"
                                    class="flex items-center w-full px-2 py-1.5 text-sm rounded-sm transition-colors hover:bg-muted cursor-pointer"
                                    :class="!roadmapFit && roadmapZoom === level.value ? 'font-semibold text-foreground' : 'text-muted-foreground'"
                                    @click="setRoadmapZoom(level.value)"
                                >
                                    <Check v-if="!roadmapFit && roadmapZoom === level.value" class="size-3.5 mr-2 shrink-0 text-primary" />
                                    <span v-else class="size-3.5 mr-2 shrink-0" />
                                    {{ level.label }}
                                </button>
                                <div class="my-1 border-t border-border/60" />
                                <button
                                    type="button"
                                    class="flex items-center w-full px-2 py-1.5 text-sm rounded-sm transition-colors hover:bg-muted cursor-pointer"
                                    :class="roadmapFit ? 'font-semibold text-foreground' : 'text-muted-foreground hover:text-foreground'"
                                    @click="toggleFitToScreen"
                                >
                                    <Check v-if="roadmapFit" class="size-3.5 mr-2 shrink-0 text-primary" />
                                    <Maximize2 v-else class="size-3.5 mr-2 shrink-0" />
                                    Fit to screen
                                </button>
                            </PopoverContent>
                        </Popover>
                        <button
                            type="button"
                            class="flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-md border border-border text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                            @click="roadmapViewRef?.scrollToToday()"
                        >
                            <CalendarDays class="size-3.5" />
                            Today
                        </button>
                        <button
                            type="button"
                            class="flex items-center gap-1.5 px-2.5 py-1 text-xs rounded-md border transition-colors"
                            :class="showDependencies ? 'bg-primary/10 border-primary/30 text-primary' : 'border-border text-muted-foreground hover:bg-muted hover:text-foreground'"
                            @click="showDependencies = !showDependencies"
                        >
                            <Share2 class="size-3.5" />
                            Dependencies
                        </button>
                    </template>
                    <PlannerViewSwitcher />
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <Button
                                variant="ghost"
                                size="sm"
                                class="h-7 gap-1.5 text-xs text-muted-foreground hover:text-foreground"
                                @click="fieldManagerOpen = true"
                            >
                                Fields
                            </Button>
                        </TooltipTrigger>
                        <TooltipContent>Manage custom fields shown on events</TooltipContent>
                    </Tooltip>
                </template>
            </PlannerFilters>

            <!-- Viewport (swaps based on active view) -->
            <div ref="listViewport" class="flex-1 flex flex-col overflow-hidden min-h-0">
                <PlannerEventList
                    v-if="plannerStore.activeView === 'list'"
                    :events="listEvents"
                    :show-milestone="activeMilestoneId === null"
                    :loading="loadingMore"
                    :columns="listColumns"
                    @edit="openEditEvent"
                    @snooze="openSnooze"
                    @delete="openDelete"
                    @toggle-status="toggleStatus"
                    @duplicate="duplicateEvent"
                    @move-to-backlog="moveToBacklog"
                    @load-more="loadMore"
                />

                <!-- Table + Board views -->
                <PlannerTableView
                    v-else-if="plannerStore.activeView === 'table'"
                    :events="events"
                    :show-milestone="activeMilestoneId === null"
                    :fields="fields ?? []"
                    :loading="loadingTablePage"
                    @edit="openEditEvent"
                    @snooze="openSnooze"
                    @delete="openDelete"
                    @toggle-status="toggleStatus"
                    @duplicate="duplicateEvent"
                    @go-to-page="tableGoToPage"
                />
                <div
                    v-else-if="plannerStore.activeView === 'board'"
                    class="flex-1 overflow-hidden"
                >
                    <PlannerBoardView
                        :events="allListEvents"
                        :active-milestone-id="activeMilestoneId"
                        :fields="fields ?? []"
                        :has-more="!!events?.next_page_url"
                        :total="events?.total ?? allListEvents.length"
                        :loading-all="loadingAll"
                        @edit="openEditEvent"
                        @snooze="openSnooze"
                        @delete="openDelete"
                        @toggle-status="toggleStatus"
                        @duplicate="duplicateEvent"
                        @load-all="loadAll"
                    />
                </div>
                <div
                    v-else
                    class="flex-1 overflow-hidden min-h-0 flex flex-col"
                >
                    <PlannerRoadmapView
                        ref="roadmapViewRef"
                        :milestones="milestones"
                        :events="listEvents"
                        :active-milestone-id="activeMilestoneId"
                        :zoom="roadmapZoom"
                        :fit-mode="roadmapFit"
                        @create-event="openCreateEvent"
                        @reschedule="handleRoadmapReschedule"
                        @load-more="loadMore"
                    />
                </div>
            </div>
        </template>
    </div>

    <!-- Field manager drawer -->
    <PlannerFieldManager
        v-model:open="fieldManagerOpen"
        :fields="fields ?? []"
        :milestone-id="activeMilestoneId"
    />

    <!-- Event create/edit drawer -->
    <PlannerEventDrawer
        v-model:open="eventDrawerOpen"
        :event="editingEvent"
        :milestones="milestones"
        :tags="tags ?? []"
        :default-milestone-id="activeMilestoneId"
    />

    <!-- Milestone create/edit drawer -->
    <PlannerMilestoneDrawer
        v-model:open="milestoneDrawerOpen"
        :milestone="editingMilestone"
        :tags="tags ?? []"
    />

    <!-- Milestone explorer -->
    <PlannerMilestoneExplorer
        v-model:open="milestoneExplorerOpen"
        :milestones="milestones"
        :active-milestone-id="activeMilestoneId"
        :current-filters="currentFilters"
        @create-milestone="milestoneExplorerOpen = false; openCreateMilestone()"
    />

    <!-- Snooze popover (rendered as a modal overlay for simplicity) -->
    <Dialog :open="!!snoozingEvent" @update:open="(v) => !v && (snoozingEvent = null)">
        <DialogContent class="p-0 max-w-70">
            <DialogDescription class="sr-only">Snooze event</DialogDescription>
            <PlannerSnoozePopover
                v-if="snoozingEvent"
                :event="snoozingEvent"
                @snooze="handleSnooze"
                @cancel="snoozingEvent = null"
            />
        </DialogContent>
    </Dialog>

    <!-- Delete confirm dialog -->
    <Dialog :open="!!deletingEvent" @update:open="(v) => !v && (deletingEvent = null)">
        <DialogContent class="max-w-95">
            <DialogHeader>
                <DialogTitle>Delete event?</DialogTitle>
            </DialogHeader>
            <p class="text-sm text-muted-foreground">
                "<strong>{{ deletingEvent?.title }}</strong>" will be permanently deleted. This cannot be undone.
            </p>
            <DialogFooter>
                <Button variant="outline" @click="deletingEvent = null">Cancel</Button>
                <Button variant="destructive" :disabled="deleteProcessing" @click="confirmDelete">
                    {{ deleteProcessing ? 'Deleting…' : 'Delete' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
