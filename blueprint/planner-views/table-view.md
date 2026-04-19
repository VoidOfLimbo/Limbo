# Planner Views — Table View Implementation

**Depends on:** [`blueprint/planner-views/component-tree.md`](./component-tree.md), [`blueprint/planner-views/data-model.md`](./data-model.md)
**Status:** Blueprint / Design (Phase 2 — REST; Phase 3 — GraphQL + custom fields)

---

## Overview

The Table View is a **high-performance, spreadsheet-style grid** powered by TanStack Table (Vue adapter) combined with Vue Virtual Scroller for row virtualization. It renders all planner items as rows, with columns for each system and custom field.

Design goals:
- Render 1,000+ rows without jank
- Inline editing — click any cell to edit without opening a drawer
- Column resizing, reordering, visibility toggle
- Multi-sort, multi-filter, grouping
- Sticky header + frozen first column (title)

---

## Libraries

| Library | Version | Role |
|---|---|---|
| `@tanstack/vue-table` | v8 | Column management, sorting, filtering, row selection |
| `vue-virtual-scroller` | v2 | Row virtualization (only renders visible rows in DOM) |
| `@vueuse/core` | existing | `useResizeObserver`, `useElementBounding` for column resize |

---

## Component Structure

```
PlannerTableView
├── PlannerTableToolbar
│   ├── Column visibility toggle  (shadcn DropdownMenu)
│   └── Add custom field column   (Phase 3)
└── PlannerVirtualTable
    ├── PlannerTableHead          (sticky, outside virtual scroll)
    │   └── PlannerColumnHeader × N
    └── RecycleScroller           (vue-virtual-scroller)
        └── PlannerTableRow × visible rows
            └── PlannerTableCell × N
                └── PlannerFieldCell  (read mode)
                    └── PlannerFieldEditor  (edit mode, on focus)
```

---

## TanStack Table Setup

### Column Definitions

```typescript
// resources/js/components/planner/table/columns.ts
import { createColumnHelper, type ColumnDef } from '@tanstack/vue-table'
import type { PlannerItem, PlannerField } from '@/types/planner'

const helper = createColumnHelper<PlannerItem>()

// System columns (always present)
export const systemColumns: ColumnDef<PlannerItem>[] = [
    helper.accessor('id', {
        id: 'select',
        header: ({ table }) => h(Checkbox, {
            checked: table.getIsAllPageRowsSelected(),
            onChange: (v: boolean) => table.toggleAllPageRowsSelected(v),
        }),
        cell: ({ row }) => h(Checkbox, {
            checked: row.getIsSelected(),
            onChange: (v: boolean) => row.toggleSelected(v),
        }),
        size: 40,
        enableSorting: false,
        enableResizing: false,
    }),

    helper.accessor('title', {
        id: 'title',
        header: 'Title',
        cell: ({ row, getValue }) => h(PlannerFieldCell, {
            item: row.original,
            fieldType: 'text',
            value: getValue(),
            fieldKey: 'title',
        }),
        size: 280,
        enablePinning: true,
    }),

    helper.accessor('status', {
        id: 'status',
        header: 'Status',
        cell: ({ row, getValue }) => h(PlannerFieldCell, {
            item: row.original,
            fieldType: 'single_select',
            value: getValue(),
            fieldKey: 'status',
        }),
        size: 140,
    }),

    helper.accessor('priority', {
        id: 'priority',
        header: 'Priority',
        size: 120,
    }),

    helper.accessor('startAt', {
        id: 'start_at',
        header: 'Start Date',
        size: 150,
    }),

    helper.accessor('endAt', {
        id: 'end_at',
        header: 'End Date',
        size: 150,
    }),

    helper.accessor('type', {
        id: 'type',
        header: 'Type',
        size: 110,
    }),
]

// Dynamic custom field columns (Phase 3)
export function buildCustomColumn(field: PlannerField): ColumnDef<PlannerItem> {
    return helper.display({
        id: `field_${field.id}`,
        header: field.name,
        cell: ({ row }) => h(PlannerFieldCell, {
            item: row.original,
            fieldType: field.type,
            fieldId: field.id,
            value: row.original.fieldValues?.find(fv => fv.field.id === field.id)?.value,
        }),
        size: 160,
    })
}
```

### Table Instance

```typescript
// resources/js/components/planner/table/PlannerVirtualTable.vue
<script setup lang="ts">
import {
    useVueTable,
    getCoreRowModel,
    getSortedRowModel,
    getGroupedRowModel,
    getFilteredRowModel,
    flexRender,
} from '@tanstack/vue-table'
import { RecycleScroller } from 'vue-virtual-scroller'
import { usePlannerStore } from '@/stores/planner'
import { systemColumns, buildCustomColumn } from './columns'

const store = usePlannerStore()

const columns = computed(() => [
    ...systemColumns,
    ...store.fieldSchema
        .filter(f => !f.isSystem)
        .map(buildCustomColumn),
])

const table = useVueTable({
    get data() { return store.items },
    get columns() { return columns.value },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getGroupedRowModel: getGroupedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    enableColumnResizing: true,
    columnResizeMode: 'onChange',
    state: {
        get sorting() { return store.sorts },
        get columnVisibility() { return store.activeView?.layout?.columnVisibility ?? {} },
        get grouping() { return store.groupBy ? [store.groupBy.fieldId] : [] },
        get rowSelection() { return Object.fromEntries([...store.selectedItemIds].map(id => [id, true])) },
    },
    onSortingChange: (updater) => store.setSorts(typeof updater === 'function' ? updater(store.sorts) : updater),
    onRowSelectionChange: (updater) => {
        const next = typeof updater === 'function' ? updater({}) : updater
        store.selectedItemIds = new Set(Object.keys(next).filter(k => next[k]))
    },
})
</script>
```

---

## Row Virtualization

Use `RecycleScroller` from `vue-virtual-scroller`. It recycles DOM nodes as the user scrolls, keeping only visible rows in the DOM.

```vue
<!-- PlannerVirtualTable.vue template (simplified) -->
<template>
    <div class="flex flex-col h-full overflow-hidden">
        <!-- Sticky header — outside scroller -->
        <div class="sticky top-0 z-10 border-b border-border bg-background">
            <div
                v-for="headerGroup in table.getHeaderGroups()"
                :key="headerGroup.id"
                class="flex"
            >
                <PlannerColumnHeader
                    v-for="header in headerGroup.headers"
                    :key="header.id"
                    :header="header"
                    :style="{ width: `${header.getSize()}px` }"
                />
            </div>
        </div>

        <!-- Virtualised body -->
        <RecycleScroller
            class="flex-1 overflow-auto"
            :items="table.getRowModel().rows"
            :item-size="36"
            key-field="id"
            v-slot="{ item: row }"
        >
            <PlannerTableRow :row="row" :table="table" />
        </RecycleScroller>

        <!-- Footer: row count + bulk bar -->
        <PlannerTableFoot :table="table" />
    </div>
</template>
```

> **Item size:** 36px for compact rows (matches GitHub Projects' density). Allow a "comfortable" toggle at 48px.

---

## Inline Editing

Every cell is read-only by default. Clicking a cell activates the editor for that field type. Pressing `Tab` moves to the next editable cell. `Escape` cancels. `Enter` / blur saves.

### PlannerFieldCell.vue (simplified)

```vue
<script setup lang="ts">
import { ref } from 'vue'
import { useInlineEdit } from '@/composables/useInlineEdit'
import { useOptimisticUpdate } from '@/composables/useOptimisticUpdate'

const props = defineProps<{
    item: PlannerItem
    fieldType: PlannerFieldType
    fieldKey?: string        // for system fields: 'title', 'status', etc.
    fieldId?: string         // for custom fields
    value: unknown
}>()

const { isEditing, startEdit, endEdit } = useInlineEdit()
const { mutate } = useOptimisticUpdate()

async function save(newValue: unknown) {
    endEdit()

    if (props.fieldKey) {
        // System field — direct property mutation
        await mutate(
            props.item.id,
            { [props.fieldKey]: newValue },
            () => updateEvent({ id: props.item.id, input: { [props.fieldKey]: newValue } }),
        )
    } else if (props.fieldId) {
        // Custom field — field value upsert
        await updateEventFieldValue({ eventId: props.item.id, fieldId: props.fieldId, value: newValue })
    }
}
</script>

<template>
    <div
        class="flex items-center h-full px-2 cursor-pointer hover:bg-accent/50 rounded"
        @click="startEdit"
    >
        <!-- Read mode -->
        <template v-if="!isEditing">
            <PlannerFieldDisplay :type="fieldType" :value="value" />
        </template>

        <!-- Edit mode — switch by type -->
        <template v-else>
            <PlannerFieldText v-if="fieldType === 'text'" :value="value" @save="save" @cancel="endEdit" />
            <PlannerFieldSelect v-else-if="fieldType === 'single_select'" :value="value" :field-id="fieldId" @save="save" @cancel="endEdit" />
            <PlannerFieldDate v-else-if="fieldType === 'date'" :value="value" @save="save" @cancel="endEdit" />
            <PlannerFieldNumber v-else-if="fieldType === 'number'" :value="value" @save="save" @cancel="endEdit" />
            <!-- ... other types -->
        </template>
    </div>
</template>
```

---

## Column Resizing

TanStack Table's `columnResizeMode: 'onChange'` dispatches resize events continuously as the user drags. Apply column width as inline style:

```vue
<!-- PlannerColumnHeader.vue -->
<template>
    <div
        class="relative flex items-center border-r border-border select-none"
        :style="{ width: `${header.getSize()}px` }"
    >
        <span class="truncate px-2 text-xs font-medium text-muted-foreground">
            {{ header.column.columnDef.header }}
        </span>

        <!-- Resize handle -->
        <div
            v-if="header.column.getCanResize()"
            class="absolute right-0 top-0 h-full w-1 cursor-col-resize hover:bg-primary"
            @mousedown="header.getResizeHandler()($event)"
        />
    </div>
</template>
```

Persist column widths to `planner_views.layout.columns[n].width` on `mouseup` (debounced, 500ms).

---

## Grouping

When `groupBy` is set, TanStack Table's `getGroupedRowModel` inserts group separator rows. Render these differently:

```vue
<!-- PlannerTableRow.vue -->
<template>
    <!-- Group header row -->
    <div v-if="row.getIsGrouped()" class="flex items-center gap-2 px-2 py-1 bg-muted/40 text-sm font-medium">
        <button @click="row.toggleExpanded()">
            <ChevronRight :class="{ 'rotate-90': row.getIsExpanded() }" />
        </button>
        <PlannerGroupLabel :value="row.groupingValue" :field="groupByField" />
        <span class="text-muted-foreground">({{ row.subRows.length }})</span>
    </div>

    <!-- Normal data row -->
    <div v-else class="flex border-b border-border hover:bg-accent/30 transition-colors">
        <div
            v-for="cell in row.getVisibleCells()"
            :key="cell.id"
            class="flex items-center"
            :style="{ width: `${cell.column.getSize()}px` }"
        >
            <component :is="flexRender(cell.column.columnDef.cell, cell.getContext())" />
        </div>
    </div>
</template>
```

---

## Bulk Actions

When `selectedItemIds` has items, a floating `PlannerBulkActionBar` appears at the bottom:

- Change status (all selected)
- Change priority (all selected)
- Move to milestone (all selected)
- Add tags
- Delete (with confirmation)
- Clear selection

Implemented via `bulkUpdateEvents` / `bulkDeleteEvents` GraphQL mutations (Phase 3) or REST (Phase 2).

---

## Keyboard Navigation

| Key | Action |
|---|---|
| `Click` | Activate cell edit |
| `Enter` | Save + move to cell below |
| `Tab` | Save + move to next cell right |
| `Shift+Tab` | Save + move to previous cell |
| `Escape` | Cancel edit |
| `Space` | Toggle row selection (when focused on row) |
| `Ctrl+A` | Select all rows |

---

## Performance Notes

- **Only visible rows render.** `RecycleScroller` with `item-size=36` and ~800px viewport = ~22 DOM rows maximum at any time.
- **Column definitions are memoized.** Rebuilt only when `fieldSchema` changes.
- **Sorting/filtering runs in-memory** for datasets ≤5,000 items. Beyond that, push sort/filter to GraphQL query.
- **Debounce search input** 300ms before triggering filter.
- **Avoid watchers on the full `items` array.** Use computed row model from TanStack Table instead.
