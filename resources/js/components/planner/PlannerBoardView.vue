<script setup lang="ts">
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { ArrowDownToLine, Columns3, Loader2 } from 'lucide-vue-next'
import { update as updateEvent, store as storeEvent } from '@/actions/App/Http/Controllers/Planner/EventController'
import PlannerBoardColumn from '@/components/planner/PlannerBoardColumn.vue'
import type { PlannerEvent, PlannerField, PlannerFieldOption, EventStatus } from '@/types/planner'

const props = defineProps<{
    events: PlannerEvent[]
    activeMilestoneId?: string | null
    fields?: PlannerField[]
    hasMore?: boolean
    total?: number
    loadingAll?: boolean
}>()

const emit = defineEmits<{
    edit: [event: PlannerEvent]
    snooze: [event: PlannerEvent]
    delete: [event: PlannerEvent]
    toggleStatus: [event: PlannerEvent]
    duplicate: [event: PlannerEvent]
    loadAll: []
}>()

// ── Group-by selector ────────────────────────────────────────────────────────
type GroupById = 'status' | string  // string = plannerField.id

const groupById = ref<GroupById>('status')

interface GroupOption {
    id: GroupById
    label: string
    options?: PlannerFieldOption[]
}

const groupOptions = computed<GroupOption[]>(() => {
    const custom = (props.fields ?? [])
        .filter((f) => f.type === 'single_select')
        .map((f) => ({ id: f.id, label: f.name, options: f.options ?? [] }))
    return [{ id: 'status', label: 'Status' }, ...custom]
})

const showGroupPicker = ref(false)

function setGroupBy(id: GroupById) {
    groupById.value = id
    showGroupPicker.value = false
}

// ── Status column definitions ─────────────────────────────────────────────
interface BoardColumn {
    id: string
    label: string
    color: string
}

const STATUS_COLUMNS: BoardColumn[] = [
    { id: 'draft', label: 'Draft', color: 'text-muted-foreground' },
    { id: 'upcoming', label: 'Upcoming', color: 'text-blue-500' },
    { id: 'in_progress', label: 'In Progress', color: 'text-violet-500' },
    { id: 'completed', label: 'Completed', color: 'text-green-500' },
    { id: 'cancelled', label: 'Cancelled', color: 'text-muted-foreground' },
    { id: 'skipped', label: 'Skipped', color: 'text-muted-foreground' },
]

// ── Columns computed from groupById ────────────────────────────────────────
const columns = computed<BoardColumn[]>(() => {
    if (groupById.value === 'status') return STATUS_COLUMNS

    const field = (props.fields ?? []).find((f) => f.id === groupById.value)
    if (!field) return STATUS_COLUMNS

    const cols: BoardColumn[] = (field.options ?? []).map((opt) => ({
        id: opt.id,
        label: opt.label,
        color: '',
    }))
    cols.push({ id: '__none__', label: 'No value', color: 'text-muted-foreground' })
    return cols
})

// ── columnEvents ──────────────────────────────────────────────────────────
const columnEvents = computed(() =>
    columns.value.map((col) => ({
        ...col,
        events: props.events.filter((e) => {
            if (groupById.value === 'status') return e.status === col.id
            // custom field group-by: match via fieldValues
            const val = e.field_values?.find((fv) => fv.field_id === groupById.value)
            const selected = val?.value ?? null
            if (col.id === '__none__') return !selected
            return selected === col.id || (Array.isArray(selected) && selected.includes(col.id))
        }),
    })),
)

// ── Card moved ─────────────────────────────────────────────────────────────
function onCardMoved(eventId: string, newColumnId: string) {
    if (groupById.value === 'status') {
        const def = updateEvent(eventId)
        router.visit(def.url, {
            method: def.method,
            data: { status: newColumnId as EventStatus },
            preserveScroll: true,
            only: ['events', 'milestones'],
        })
    }
    // custom field moves would require field-value upsert — omitted for now
}

function onAddCard(columnId: string, title: string) {
    const data: Record<string, unknown> = {
        title,
        type: 'task',
        priority: 'medium',
        milestone_id: props.activeMilestoneId ?? undefined,
    }
    if (groupById.value === 'status') {
        data.status = columnId
    } else {
        data.status = 'upcoming'
    }
    router.post(
        storeEvent().url,
        data,
        { preserveScroll: true, only: ['events', 'milestones'] },
    )
}

const activeGroupLabel = computed(
    () => groupOptions.value.find((o) => o.id === groupById.value)?.label ?? 'Status',
)
</script>

<template>
    <div class="flex flex-col h-full overflow-hidden">
        <!-- Group-by toolbar -->
        <div class="flex items-center gap-2 px-4 py-2 border-b border-border shrink-0 relative">
            <div class="relative">
                <button
                    class="flex items-center gap-1.5 text-xs text-muted-foreground hover:text-foreground transition-colors cursor-pointer"
                    @click.stop="showGroupPicker = !showGroupPicker"
                >
                    <Columns3 class="size-3.5" />
                    Group by: <span class="font-medium text-foreground">{{ activeGroupLabel }}</span>
                </button>

                <div
                    v-if="showGroupPicker"
                    class="absolute left-0 top-full mt-1 z-10 bg-popover border border-border rounded-md shadow-md py-1 min-w-36"
                    @click.stop
                >
                    <button
                        v-for="opt in groupOptions"
                        :key="opt.id"
                        class="w-full flex items-center gap-2 px-3 py-1.5 text-xs hover:bg-accent hover:text-accent-foreground text-left transition-colors cursor-pointer"
                        :class="groupById === opt.id ? 'text-foreground font-medium' : 'text-muted-foreground'"
                        @click="setGroupBy(opt.id)"
                    >
                        <span
                            class="size-1.5 rounded-full shrink-0"
                            :class="groupById === opt.id ? 'bg-primary' : 'bg-muted-foreground/40'"
                        />
                        {{ opt.label }}
                    </button>
                </div>
            </div>

            <div class="flex-1" />

            <span class="text-[11px] text-muted-foreground tabular-nums">
                {{ events.length }}<template v-if="total"> of {{ total }}</template> loaded
            </span>
            <button
                v-if="hasMore"
                type="button"
                class="group flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md border border-primary/40 bg-primary/10 text-primary shadow-sm hover:bg-primary hover:text-primary-foreground hover:border-primary transition-all cursor-pointer animate-in fade-in slide-in-from-right-2 duration-300 disabled:opacity-60 disabled:cursor-wait"
                :disabled="loadingAll"
                @click="emit('loadAll')"
            >
                <Loader2 v-if="loadingAll" class="size-3.5 animate-spin" />
                <ArrowDownToLine v-else class="size-3.5 transition-transform group-hover:translate-y-0.5" />
                {{ loadingAll ? 'Loading…' : 'Load all available' }}
                <span
                    v-if="!loadingAll && total"
                    class="ml-0.5 inline-flex items-center justify-center min-w-5 h-4 px-1 rounded-full bg-primary/20 text-primary text-[10px] font-semibold tabular-nums group-hover:bg-primary-foreground/20 group-hover:text-primary-foreground"
                >
                    +{{ Math.max(0, total - events.length) }}
                </span>
            </button>
        </div>

        <!-- Board columns -->
        <div class="flex-1 min-h-0 overflow-x-auto overflow-y-hidden" @click="showGroupPicker = false">
            <div class="flex h-full gap-3 p-4 min-w-max">
                <PlannerBoardColumn
                    v-for="col in columnEvents"
                    :key="col.id"
                    :column-id="col.id"
                    :label="col.label"
                    :color="col.color"
                    :events="col.events"
                    :all-events="events"
                    @card-moved="onCardMoved"
                    @add-card="(title) => onAddCard(col.id, title)"
                    @edit="emit('edit', $event)"
                    @snooze="emit('snooze', $event)"
                    @delete="emit('delete', $event)"
                    @toggle-status="emit('toggleStatus', $event)"
                    @duplicate="emit('duplicate', $event)"
                />
            </div>
        </div>
    </div>
</template>
