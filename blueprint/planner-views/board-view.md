# Planner Views — Board View (Kanban) Implementation

**Depends on:** [`blueprint/planner-views/component-tree.md`](./component-tree.md)
**Status:** Blueprint / Design (Phase 2)

---

## Overview

The Board View is a **Kanban-style drag-and-drop board** — columns represent status values (or any single-select custom field), and cards represent planner items. Dragging a card between columns updates the corresponding field value optimistically.

Design goals:
- Smooth drag with visual placeholder and drop indicator
- Column scroll independently (tall columns don't break layout)
- Drag cards within a column to reorder
- Drag columns to reorder
- Click a card to open the event drawer
- Inline add at the bottom of each column

---

## Library: dnd-kit (Vue)

The official `@dnd-kit` ecosystem targets React. For Vue 3 we use the **Vue port: `@dnd-kit/vue`** or the maintained community fork **`vue-dndkit`**.

> **Decision:** Evaluate `@dnd-kit/vue` first (official). Fall back to a thin wrapper around `@dnd-kit/core` using Vue's reactivity system if the Vue port is immature. The algorithm is identical — only the binding layer differs.

Alternative: **`vuedraggable`** (Sortable.js wrapper) is simpler but has less flexibility for cross-column drag with custom sensors. Prefer dnd-kit for its composable API.

---

## Component Structure

```
PlannerBoardView
├── PlannerBoardToolbar
│   ├── GroupByField selector
│   └── CardFields selector
└── DndContext  [drag context, keyboard + pointer sensors]
    ├── PlannerBoardColumns  [horizontal scroll container]
    │   └── PlannerBoardColumn × N  [droppable]
    │       ├── PlannerBoardColumnHeader
    │       │   ├── Status badge + count
    │       │   └── Column menu (rename, hide, delete)
    │       ├── SortableContext  [card sort within column]
    │       │   └── PlannerBoardCard × M  [draggable]
    │       │       ├── Card title
    │       │       ├── Card meta (priority, type, dates)
    │       │       ├── Card tags
    │       │       └── Card milestone badge
    │       └── PlannerBoardAddCard
    └── DragOverlay  [rendered while dragging — floating card clone]
```

---

## Column Definition

Columns are derived from the **groupBy field's option values**. Default: `status` field.

```typescript
// resources/js/composables/usePlannerBoardDnd.ts

interface BoardColumn {
    id: string           // option ID (e.g. 'upcoming', 'in_progress', or custom option ULID)
    label: string        // display name
    color: string | null
    items: PlannerItem[]
}

export function usePlannerBoardColumns() {
    const store = usePlannerStore()

    const groupField = computed(() =>
        store.fieldSchema.find(f => f.id === store.activeView?.layout?.group_field_id)
        ?? store.fieldSchema.find(f => f.name === 'Status')
    )

    const columns = computed<BoardColumn[]>(() => {
        const options = groupField.value?.options ?? statusOptions   // fallback to system status

        return options.map(opt => ({
            id: opt.id,
            label: opt.name,
            color: opt.color,
            items: store.items.filter(item => {
                const value = getFieldValue(item, groupField.value!)
                return value === opt.id || value === opt.name  // system fields store enum string
            }),
        }))
    })

    return { columns, groupField }
}
```

---

## Drag Logic

### dnd-kit Setup

```typescript
// resources/js/composables/usePlannerBoardDnd.ts
import { DndContext, DragOverlay, PointerSensor, KeyboardSensor, useSensor, useSensors } from '@dnd-kit/vue'
import { SortableContext, verticalListSortingStrategy, arrayMove } from '@dnd-kit/sortable'

export function usePlannerBoardDnd() {
    const store = usePlannerStore()
    const { mutate } = useOptimisticUpdate()

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 8 } }),
        useSensor(KeyboardSensor),
    )

    const activeItem = ref<PlannerItem | null>(null)
    const overId = ref<string | null>(null)

    function onDragStart({ active }: DragStartEvent) {
        activeItem.value = store.items.find(i => i.id === active.id) ?? null
    }

    function onDragOver({ active, over }: DragOverEvent) {
        overId.value = over?.id ?? null

        if (!over || !activeItem.value) { return }

        const targetColumnId = getColumnId(over.id as string)   // column or card id → column
        if (!targetColumnId) { return }

        // Preview: temporarily move item to target column in store
        store.setItemColumnPreview(active.id as string, targetColumnId)
    }

    async function onDragEnd({ active, over }: DragEndEvent) {
        activeItem.value = null
        overId.value = null

        if (!over || !active) {
            store.clearColumnPreview()
            return
        }

        const item = store.items.find(i => i.id === active.id)
        if (!item) { return }

        const targetColumnId = getColumnId(over.id as string)
        if (!targetColumnId || targetColumnId === getCurrentColumnId(item)) {
            store.clearColumnPreview()
            // Same column — handle reorder
            await handleReorder(active.id as string, over.id as string)
            return
        }

        // Cross-column drop — update the group-by field value
        await mutate(
            item.id,
            { [groupByFieldKey.value]: targetColumnId },
            () => updateItemGroupField(item.id, targetColumnId),
            () => toast.error('Failed to move item'),
        )
    }

    function onDragCancel() {
        activeItem.value = null
        store.clearColumnPreview()
    }

    return { sensors, activeItem, onDragStart, onDragOver, onDragEnd, onDragCancel }
}
```

### Column Detection

```typescript
// A "droppable target" can be a column header OR a card inside a column.
// We map both to the column ID.
function getColumnId(droppableId: string): string | null {
    // Column IDs are prefixed 'col-'
    if (droppableId.startsWith('col-')) { return droppableId.replace('col-', '') }
    // Card IDs are item ULIDs — look up which column they belong to
    const item = store.items.find(i => i.id === droppableId)
    return item ? getCurrentColumnId(item) : null
}
```

---

## Board Template

```vue
<!-- PlannerBoardView.vue -->
<template>
    <DndContext
        :sensors="sensors"
        collision-detection="closestCenter"
        @drag-start="onDragStart"
        @drag-over="onDragOver"
        @drag-end="onDragEnd"
        @drag-cancel="onDragCancel"
    >
        <!-- Horizontal column scroll -->
        <div class="flex h-full gap-3 overflow-x-auto p-4">
            <PlannerBoardColumn
                v-for="col in columns"
                :key="col.id"
                :column="col"
            />

            <PlannerBoardAddColumn />
        </div>

        <!-- Floating drag overlay -->
        <DragOverlay>
            <PlannerBoardCard
                v-if="activeItem"
                :item="activeItem"
                :is-overlay="true"
            />
        </DragOverlay>
    </DndContext>
</template>
```

```vue
<!-- PlannerBoardColumn.vue -->
<template>
    <div
        class="flex flex-col w-72 shrink-0 rounded-lg bg-muted/30"
        :class="{ 'ring-2 ring-primary': isOver }"
    >
        <PlannerBoardColumnHeader :column="column" />

        <SortableContext
            :id="`col-${column.id}`"
            :items="column.items.map(i => i.id)"
            :strategy="verticalListSortingStrategy"
        >
            <div class="flex flex-col gap-2 p-2 overflow-y-auto flex-1">
                <PlannerBoardCard
                    v-for="item in column.items"
                    :key="item.id"
                    :item="item"
                />
                <PlannerBoardAddCard :column-id="column.id" />
            </div>
        </SortableContext>
    </div>
</template>
```

---

## PlannerBoardCard

```vue
<!-- PlannerBoardCard.vue -->
<script setup lang="ts">
import { useSortable } from '@dnd-kit/sortable'
import { CSS } from '@dnd-kit/utilities'

const props = defineProps<{
    item: PlannerItem
    isOverlay?: boolean
}>()

const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: props.item.id,
    disabled: props.isOverlay,
})

const style = computed(() => ({
    transform: CSS.Transform.toString(transform.value),
    transition: transition.value,
    opacity: isDragging.value ? 0.4 : 1,
}))
</script>

<template>
    <div
        ref="setNodeRef"
        v-bind="attributes"
        :style="style"
        class="group rounded-md border border-border bg-card p-3 shadow-sm cursor-grab active:cursor-grabbing
               hover:border-primary/50 transition-colors select-none"
        :class="{ 'shadow-lg ring-1 ring-primary': isOverlay }"
        @click.stop="openEventDrawer(item)"
    >
        <!-- Drag handle (visible on hover) -->
        <div
            v-bind="listeners"
            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity cursor-grab"
        >
            <GripVertical class="h-3 w-3 text-muted-foreground" />
        </div>

        <!-- Title -->
        <p class="text-sm font-medium leading-snug line-clamp-2 mb-2">
            {{ item.title }}
        </p>

        <!-- Meta row -->
        <div class="flex items-center gap-1.5 flex-wrap">
            <PlannerBadge :priority="item.priority" size="xs" />
            <PlannerBadge :type="item.type" size="xs" />
            <PlannerDateRange :start="item.startAt" :end="item.endAt" class="text-xs text-muted-foreground" />
        </div>

        <!-- Tags -->
        <div v-if="item.tags.length" class="flex gap-1 mt-2 flex-wrap">
            <Badge
                v-for="tag in item.tags.slice(0, 3)"
                :key="tag.id"
                variant="secondary"
                class="text-xs px-1.5 py-0"
                :style="tag.color ? { backgroundColor: `${tag.color}20`, color: tag.color } : {}"
            >
                {{ tag.name }}
            </Badge>
            <span v-if="item.tags.length > 3" class="text-xs text-muted-foreground">
                +{{ item.tags.length - 3 }}
            </span>
        </div>
    </div>
</template>
```

---

## Inline Add Card

Clicking `+ Add item` at the bottom of a column inserts a draft row with a text input. Pressing `Enter` creates the event in that column's status.

```vue
<!-- PlannerBoardAddCard.vue -->
<script setup lang="ts">
const props = defineProps<{ columnId: string }>()

const adding = ref(false)
const titleInput = ref('')

async function submit() {
    if (!titleInput.value.trim()) {
        adding.value = false
        return
    }

    await createEvent({
        title: titleInput.value,
        status: props.columnId,   // column ID = status value
        milestoneId: store.activeMilestoneId,
    })

    titleInput.value = ''
    adding.value = false
}
</script>

<template>
    <div v-if="!adding">
        <button
            class="flex items-center gap-1.5 w-full px-2 py-1.5 text-sm text-muted-foreground
                   hover:text-foreground hover:bg-accent rounded transition-colors"
            @click="adding = true"
        >
            <Plus class="h-3.5 w-3.5" />
            Add item
        </button>
    </div>
    <div v-else class="rounded-md border border-primary bg-card p-2">
        <input
            v-model="titleInput"
            class="w-full text-sm bg-transparent outline-none"
            placeholder="Event title..."
            autofocus
            @keydown.enter="submit"
            @keydown.escape="adding = false"
            @blur="submit"
        />
    </div>
</template>
```

---

## Column Reordering

Columns can be dragged horizontally to reorder. This updates `planner_views.layout.board.column_order`.

Use a second `SortableContext` at the columns level with `horizontalListSortingStrategy`.

---

## Accessibility

- All draggable items have `role="button"` and keyboard drag support via `KeyboardSensor`
- Drag instructions announced via `aria-roledescription="sortable item"`
- `DragOverlay` has `aria-live="polite"` for screen reader drop announcements
- Column headers have `role="columnheader"`

---

## Performance Notes

- Cards do not use virtual scrolling per-column (columns are expected to be short — 50–100 items max)
- If a column exceeds 100 items, show a "Load more" within that column
- `DragOverlay` renders a clone, so the original item can fade out without DOM removal
- Avoid re-rendering all columns when one changes — each column is its own reactive island
