<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { toast } from 'vue-sonner'
import { planner as plannerRoute } from '@/routes'
import PlannerMilestoneSelector from '@/components/planner/PlannerMilestoneSelector.vue'
import PlannerMilestoneExplorer from '@/components/planner/PlannerMilestoneExplorer.vue'
import PlannerMilestoneHeader from '@/components/planner/PlannerMilestoneHeader.vue'
import PlannerFilters from '@/components/planner/PlannerFilters.vue'
import PlannerEventList from '@/components/planner/PlannerEventList.vue'
import PlannerEventDrawer from '@/components/planner/PlannerEventDrawer.vue'
import PlannerMilestoneDrawer from '@/components/planner/PlannerMilestoneDrawer.vue'
import PlannerSnoozePopover from '@/components/planner/PlannerSnoozePopover.vue'
import PlannerViewSwitcher from '@/components/planner/PlannerViewSwitcher.vue'
import PlannerTableView from '@/components/planner/PlannerTableView.vue'
import PlannerBoardView from '@/components/planner/PlannerBoardView.vue'
import { Button } from '@/components/ui/button'
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { destroy, snooze as snoozeEvent, update as updateEvent, store as storeEvent } from '@/actions/App/Http/Controllers/Planner/EventController'
import { usePlannerFilters } from '@/composables/planner/usePlannerFilters'
import { usePlannerStore } from '@/stores/planner'
import type { PaginatedData, PlannerEvent, PlannerFilters as PlannerFilterValues, PlannerMilestone, PlannerTag } from '@/types/planner'

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Planner', href: plannerRoute() }],
    },
})

const props = defineProps<{
    milestones: PlannerMilestone[]
    activeMilestoneId: string | null
    filters: PlannerFilterValues
    events?: PaginatedData<PlannerEvent>
    tags?: PlannerTag[]
}>()

// ── Active milestone object ──────────────────────────────────────────────────
const activeMilestone = computed(() =>
    props.milestones.find((m) => m.id === props.activeMilestoneId) ?? null,
)

// ── View store ───────────────────────────────────────────────────────────────
const plannerStore = usePlannerStore()

// ── Filters ──────────────────────────────────────────────────────────────────
const { applyFilters, clearFilters, hasActiveFilters } = usePlannerFilters(props.filters, props.activeMilestoneId)

const currentFilters = computed(() => ({
    milestone: props.activeMilestoneId,
    ...props.filters,
}))

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
    loadingMore.value = true
    router.visit(props.events.next_page_url, {
        preserveScroll: true,
        preserveState: true,
        only: ['events'],
        onFinish: () => { loadingMore.value = false },
    })
}
</script>

<template>
    <Head title="Planner" />

    <!-- Full-height flex column inside the app layout content area -->
    <div class="flex flex-col h-full overflow-hidden">

        <!-- Milestone selector bar -->
        <PlannerMilestoneSelector
            :milestones="milestones"
            :active-milestone-id="activeMilestoneId"
            :current-filters="currentFilters"
            @create-milestone="openCreateMilestone"
            @open-explorer="milestoneExplorerOpen = true"
        />

        <!-- Active milestone header -->
        <PlannerMilestoneHeader
            v-if="activeMilestone"
            :milestone="activeMilestone"
            @edit="openEditMilestone"
            @create-event="openCreateEvent"
        />

        <!-- Backlog header (no active milestone) -->
        <div v-else class="flex items-center justify-between px-4 py-3 border-b border-border shrink-0">
            <h2 class="hidden sm:block text-sm font-semibold text-muted-foreground">Backlog — events without a milestone</h2>
            <Button variant="outline" size="sm" class="h-7 gap-1.5" @click="openCreateEvent">
                <span class="text-xs">Add event</span>
            </Button>
        </div>

        <!-- Filters + view switcher -->
        <PlannerFilters
            :filters="filters"
            :tags="tags ?? []"
            :is-active="hasActiveFilters(filters)"
            @change="applyFilters"
        >
            <template #trailing>
                <PlannerViewSwitcher />
            </template>
        </PlannerFilters>

        <!-- Viewport (swaps based on active view) -->
        <PlannerEventList
            v-if="plannerStore.activeView === 'list'"
            :events="events"
            :show-milestone="activeMilestoneId === null"
            :loading="loadingMore"
            @edit="openEditEvent"
            @snooze="openSnooze"
            @delete="openDelete"
            @toggle-status="toggleStatus"
            @duplicate="duplicateEvent"
            @move-to-backlog="moveToBacklog"
            @load-more="loadMore"
        />

        <!-- Table + Board views (stubs until implemented) -->
        <PlannerTableView
            v-else-if="plannerStore.activeView === 'table'"
            :events="events?.data ?? []"
            :show-milestone="activeMilestoneId === null"
            @edit="openEditEvent"
            @snooze="openSnooze"
            @delete="openDelete"
            @toggle-status="toggleStatus"
            @duplicate="duplicateEvent"
        />
        <div
            v-else
            class="flex-1 overflow-hidden"
        >
            <PlannerBoardView
                :events="events?.data ?? []"
                :active-milestone-id="activeMilestoneId"
                @edit="openEditEvent"
                @snooze="openSnooze"
                @delete="openDelete"
                @toggle-status="toggleStatus"
                @duplicate="duplicateEvent"
            />
        </div>
    </div>

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
