<script setup lang="ts">
import { computed, h, ref } from 'vue'
import {
    useVueTable,
    FlexRender,
    getCoreRowModel,
    getSortedRowModel,
    getFilteredRowModel,
    type ColumnDef,
    type SortingState,
    type RowSelectionState,
} from '@tanstack/vue-table'
import { ArrowUpDown, ArrowUp, ArrowDown, CheckSquare, Square, ChevronLeft, ChevronRight, Loader2 } from 'lucide-vue-next'
import PlannerBadge from '@/components/planner/PlannerBadge.vue'
import PlannerFieldCell from '@/components/planner/PlannerFieldCell.vue'
import PlannerBulkActionBar from '@/components/planner/PlannerBulkActionBar.vue'
import PlannerEmptyState from '@/components/planner/PlannerEmptyState.vue'
import { Skeleton } from '@/components/ui/skeleton'
import { Button } from '@/components/ui/button'
import type { PaginatedData, PlannerEvent, PlannerField } from '@/types/planner'

const props = defineProps<{
    events: PaginatedData<PlannerEvent> | undefined
    showMilestone?: boolean
    fields?: PlannerField[]
    loading?: boolean
}>()

const emit = defineEmits<{
    edit: [event: PlannerEvent]
    snooze: [event: PlannerEvent]
    delete: [event: PlannerEvent]
    toggleStatus: [event: PlannerEvent]
    duplicate: [event: PlannerEvent]
    goToPage: [page: number]
}>()

const sorting = ref<SortingState>([])
const rowSelection = ref<RowSelectionState>({})

const systemColumns: ColumnDef<PlannerEvent>[] = [
    {
        id: 'select',
        header: ({ table }) =>
            h('button', {
                type: 'button',
                class: 'flex items-center justify-center w-full',
                onClick: () => table.toggleAllRowsSelected(),
            }, [
                table.getIsAllRowsSelected()
                    ? h(CheckSquare, { class: 'size-4 text-primary' })
                    : h(Square, { class: 'size-4 text-muted-foreground' }),
            ]),
        cell: ({ row }) =>
            h('button', {
                type: 'button',
                class: 'flex items-center justify-center w-full',
                onClick: () => row.toggleSelected(),
            }, [
                row.getIsSelected()
                    ? h(CheckSquare, { class: 'size-4 text-primary' })
                    : h(Square, { class: 'size-4 text-muted-foreground opacity-0 group-hover:opacity-100' }),
            ]),
        size: 40,
        enableSorting: false,
        enableResizing: false,
    },
    {
        accessorKey: 'title',
        header: 'Title',
        cell: ({ row }) =>
            h(PlannerFieldCell, {
                event: row.original,
                fieldKey: 'title',
                value: row.original.title,
                onUpdate: (val: string) => emit('edit', { ...row.original, title: val }),
            }),
        size: 300,
        minSize: 150,
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: ({ row }) =>
            h(PlannerBadge, { kind: 'status', value: row.original.status }),
        size: 140,
    },
    {
        accessorKey: 'priority',
        header: 'Priority',
        cell: ({ row }) =>
            h(PlannerBadge, { kind: 'priority', value: row.original.priority }),
        size: 120,
    },
    {
        accessorKey: 'type',
        header: 'Type',
        cell: ({ row }) =>
            h(PlannerBadge, { kind: 'type', value: row.original.type }),
        size: 120,
    },
    {
        accessorKey: 'start_at',
        header: 'Start Date',
        cell: ({ row }) =>
            h('span', { class: 'text-sm text-muted-foreground' },
                row.original.start_at
                    ? new Date(row.original.start_at).toLocaleDateString()
                    : '—'),
        size: 130,
    },
    {
        accessorKey: 'end_at',
        header: 'End Date',
        cell: ({ row }) =>
            h('span', { class: 'text-sm text-muted-foreground' },
                row.original.end_at
                    ? new Date(row.original.end_at).toLocaleDateString()
                    : '—'),
        size: 130,
    },
]

if (props.showMilestone) {
    systemColumns.push({
        accessorKey: 'milestone',
        header: 'Milestone',
        cell: ({ row }) =>
            h('span', { class: 'text-sm truncate' }, row.original.milestone?.title ?? '—'),
        size: 160,
    })
}

const columns = computed<ColumnDef<PlannerEvent>[]>(() => {
    const customCols: ColumnDef<PlannerEvent>[] = (props.fields ?? [])
        .filter((f) => !f.is_system)
        .map((field) => ({
            id: `field_${field.id}`,
            header: field.name,
            cell: () => h('span', { class: 'text-sm text-muted-foreground' }, '—'),
            size: 140,
            enableSorting: false,
        }))

    return [...systemColumns, ...customCols]
})

const tableData = computed(() => props.events?.data ?? [])

const table = useVueTable({
    get data() { return tableData.value },
    get columns() { return columns.value },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    enableColumnResizing: true,
    columnResizeMode: 'onChange',
    state: {
        get sorting() { return sorting.value },
        get rowSelection() { return rowSelection.value },
    },
    onSortingChange: (updater) => {
        sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater
    },
    onRowSelectionChange: (updater) => {
        rowSelection.value = typeof updater === 'function' ? updater(rowSelection.value) : updater
    },
})

const selectedEvents = computed(() =>
    table.getSelectedRowModel().rows.map((r) => r.original),
)

function clearSelection() {
    table.resetRowSelection()
}

const paginationFrom = computed(() => {
    if (!props.events) return 0
    return (props.events.current_page - 1) * props.events.per_page + 1
})

const paginationTo = computed(() => {
    if (!props.events) return 0
    return Math.min(props.events.current_page * props.events.per_page, props.events.total)
})
</script>

<template>
    <div class="flex flex-col h-full overflow-hidden">
        <!-- Skeleton state (deferred props loading) -->
        <template v-if="events === undefined">
            <div class="flex-1 overflow-hidden">
                <div v-for="i in 8" :key="i" class="flex items-center gap-4 px-4 py-3 border-b border-border/50">
                    <Skeleton class="size-4 rounded shrink-0" />
                    <Skeleton class="h-3.5 w-1/3 rounded" />
                    <Skeleton class="h-5 w-20 rounded-full" />
                    <Skeleton class="h-5 w-16 rounded-full" />
                </div>
            </div>
        </template>

        <template v-else>
            <!-- Table wrapper — horizontally scrollable -->
            <div class="flex-1 overflow-auto">
                <table
                    class="w-full border-collapse text-sm"
                    :style="{ width: `${table.getTotalSize()}px`, minWidth: '100%' }"
                >
                    <!-- Sticky header -->
                    <thead class="sticky top-0 z-10 bg-background border-b border-border">
                        <tr
                            v-for="headerGroup in table.getHeaderGroups()"
                            :key="headerGroup.id"
                        >
                            <th
                                v-for="header in headerGroup.headers"
                                :key="header.id"
                                class="relative select-none px-3 py-2 text-left text-xs font-medium text-muted-foreground border-r border-border last:border-r-0"
                                :style="{ width: `${header.getSize()}px` }"
                            >
                                <div
                                    v-if="!header.isPlaceholder"
                                    class="flex items-center gap-1"
                                    :class="{ 'cursor-pointer hover:text-foreground': header.column.getCanSort() }"
                                    @click="header.column.getToggleSortingHandler()?.($event)"
                                >
                                    <FlexRender
                                        :render="header.column.columnDef.header"
                                        :props="header.getContext()"
                                    />
                                    <template v-if="header.column.getCanSort()">
                                        <ArrowUp v-if="header.column.getIsSorted() === 'asc'" class="size-3" />
                                        <ArrowDown v-else-if="header.column.getIsSorted() === 'desc'" class="size-3" />
                                        <ArrowUpDown v-else class="size-3 opacity-30" />
                                    </template>
                                </div>

                                <!-- Resize handle -->
                                <div
                                    v-if="header.column.getCanResize()"
                                    class="absolute right-0 top-0 h-full w-1 cursor-col-resize hover:bg-primary/50 active:bg-primary"
                                    @mousedown="header.getResizeHandler()?.($event)"
                                    @touchstart="header.getResizeHandler()?.($event)"
                                    @click.stop
                                />
                            </th>
                        </tr>
                    </thead>

                    <!-- Body -->
                    <tbody>
                        <template v-if="table.getRowModel().rows.length === 0">
                            <tr>
                                <td :colspan="columns.length" class="py-16">
                                    <PlannerEmptyState
                                        title="No events"
                                        description="Add your first event to get started."
                                    />
                                </td>
                            </tr>
                        </template>

                        <tr
                            v-for="row in table.getRowModel().rows"
                            :key="row.id"
                            class="group border-b border-border last:border-0 hover:bg-muted/40 transition-colors"
                            :class="{ 'bg-primary/5': row.getIsSelected() }"
                            @dblclick="emit('edit', row.original)"
                            @contextmenu.prevent
                        >
                            <td
                                v-for="cell in row.getVisibleCells()"
                                :key="cell.id"
                                class="px-3 py-2 border-r border-border last:border-r-0 align-middle"
                                :style="{ width: `${cell.column.getSize()}px` }"
                            >
                                <FlexRender
                                    :render="cell.column.columnDef.cell"
                                    :props="cell.getContext()"
                                />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination footer -->
            <div class="flex items-center justify-between px-4 py-2.5 border-t border-border shrink-0 text-xs text-muted-foreground">
                <span v-if="events.total > 0">
                    Showing {{ paginationFrom }}–{{ paginationTo }} of {{ events.total }} event{{ events.total !== 1 ? 's' : '' }}
                </span>
                <span v-else>No events</span>

                <div class="flex items-center gap-1">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-7"
                        :disabled="events.current_page <= 1 || loading"
                        @click="emit('goToPage', events.current_page - 1)"
                    >
                        <ChevronLeft class="size-3.5" />
                    </Button>
                    <span class="px-2 tabular-nums">
                        <Loader2 v-if="loading" class="size-3.5 animate-spin inline" />
                        <template v-else>{{ events.current_page }} / {{ events.last_page }}</template>
                    </span>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-7"
                        :disabled="events.current_page >= events.last_page || loading"
                        @click="emit('goToPage', events.current_page + 1)"
                    >
                        <ChevronRight class="size-3.5" />
                    </Button>
                </div>
            </div>
        </template>

        <!-- Bulk action bar -->
        <PlannerBulkActionBar
            v-if="selectedEvents.length > 0"
            :events="selectedEvents"
            @clear="clearSelection"
            @delete="(ev) => emit('delete', ev)"
        />
    </div>
</template>
