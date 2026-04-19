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
import { ArrowUpDown, ArrowUp, ArrowDown, CheckSquare, Square } from 'lucide-vue-next'
import PlannerBadge from '@/components/planner/PlannerBadge.vue'
import PlannerFieldCell from '@/components/planner/PlannerFieldCell.vue'
import PlannerBulkActionBar from '@/components/planner/PlannerBulkActionBar.vue'
import PlannerEmptyState from '@/components/planner/PlannerEmptyState.vue'
import type { PlannerEvent } from '@/types/planner'

const props = defineProps<{
    events: PlannerEvent[]
    showMilestone?: boolean
}>()

const emit = defineEmits<{
    edit: [event: PlannerEvent]
    snooze: [event: PlannerEvent]
    delete: [event: PlannerEvent]
    toggleStatus: [event: PlannerEvent]
    duplicate: [event: PlannerEvent]
}>()

const sorting = ref<SortingState>([])
const rowSelection = ref<RowSelectionState>({})

const columns: ColumnDef<PlannerEvent>[] = [
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
    columns.push({
        accessorKey: 'milestone',
        header: 'Milestone',
        cell: ({ row }) =>
            h('span', { class: 'text-sm truncate' }, row.original.milestone?.title ?? '—'),
        size: 160,
    })
}

const table = useVueTable({
    get data() { return props.events },
    columns,
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
</script>

<template>
    <div class="flex flex-col h-full overflow-hidden">
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

        <!-- Bulk action bar -->
        <PlannerBulkActionBar
            v-if="selectedEvents.length > 0"
            :events="selectedEvents"
            @clear="clearSelection"
            @delete="(ev) => emit('delete', ev)"
        />
    </div>
</template>
