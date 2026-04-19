# Planner Views — Component Tree

**Depends on:** [`blueprint/planner-views.md`](../planner-views.md)
**Status:** Blueprint / Design

---

## Overview

All views share a single `Planner/Index.vue` Inertia page. The view type is stored in a Pinia store (persisted to `planner_views`) and the correct view component is rendered dynamically. Shared state (active milestone, filters, sorts, group-by) flows from the store downward.

---

## Full Tree

```
Planner/Index.vue  [Inertia page]
│
├── PlannerShell
│   ├── PlannerSidebar  (milestone tree, view list, field manager)
│   │   ├── PlannerMilestoneSidebar
│   │   │   ├── PlannerMilestoneItem (per milestone, collapsible)
│   │   │   └── PlannerAddMilestone
│   │   ├── PlannerViewList  (saved named views, tabs)
│   │   │   ├── PlannerViewItem (per saved view)
│   │   │   └── PlannerAddView
│   │   └── PlannerFieldManager  (Phase 3 — manage custom field definitions)
│   │       ├── PlannerFieldItem (type icon, name, settings)
│   │       └── PlannerAddField
│   │
│   └── PlannerMain
│       ├── PlannerTopbar
│       │   ├── PlannerMilestoneHeader  (title, progress, dates, actions)
│       │   ├── PlannerViewSwitcher     (List / Table / Board / Roadmap)
│       │   └── PlannerToolbar
│       │       ├── PlannerFilterBar    (active filter chips + add filter)
│       │       │   ├── PlannerFilterChip (per active filter, removable)
│       │       │   └── PlannerFilterPopover (field → operator → value)
│       │       ├── PlannerSortMenu     (sort field + direction + multi-sort)
│       │       ├── PlannerGroupMenu    (group by field)
│       │       └── PlannerSearchInput  (local search across visible items)
│       │
│       └── PlannerViewport  [dynamic — swaps on view type]
│           │
│           ├── ── Table View ──────────────────────────────────────────
│           │   PlannerTableView
│           │   ├── PlannerTableToolbar
│           │   │   ├── PlannerColumnVisibilityToggle (show/hide columns)
│           │   │   ├── PlannerColumnResizeHandle     (drag to resize)
│           │   │   └── PlannerAddColumn              (add custom field column)
│           │   │
│           │   └── PlannerVirtualTable  [TanStack Table + Vue Virtual Scroller]
│           │       ├── PlannerTableHead  (sticky header row)
│           │       │   └── PlannerColumnHeader (per column)
│           │       │       ├── PlannerColumnSortIndicator
│           │       │       └── PlannerColumnContextMenu (sort, hide, pin, resize)
│           │       │
│           │       ├── PlannerTableBody  (virtualised rows)
│           │       │   ├── PlannerTableGroupRow      (group separator, when grouped)
│           │       │   ├── PlannerTableRow
│           │       │   │   └── PlannerTableCell (per column)
│           │       │   │       └── PlannerFieldCell  [type-aware, switches editor]
│           │       │   │           ├── PlannerFieldText        (text input)
│           │       │   │           ├── PlannerFieldNumber      (number input)
│           │       │   │           ├── PlannerFieldDate        (date picker)
│           │       │   │           ├── PlannerFieldSelect      (single-select popover)
│           │       │   │           ├── PlannerFieldMultiSelect (multi-select popover)
│           │       │   │           ├── PlannerFieldCheckbox    (toggle)
│           │       │   │           ├── PlannerFieldUrl         (link + preview)
│           │       │   │           ├── PlannerFieldIteration   (iteration picker)
│           │       │   │           └── PlannerFieldPerson      (user avatar picker)
│           │       │   └── PlannerTableAddRow  (inline "+" row at bottom)
│           │       │
│           │       └── PlannerTableFoot  (row count, bulk action bar when rows selected)
│           │
│           ├── ── Board View ──────────────────────────────────────────
│           │   PlannerBoardView
│           │   ├── PlannerBoardToolbar
│           │   │   ├── PlannerGroupByField  (which field drives columns)
│           │   │   └── PlannerCardFields    (which fields appear on cards)
│           │   │
│           │   └── PlannerBoardColumns  [dnd-kit DndContext]
│           │       ├── PlannerBoardColumn  [droppable zone, per status value]
│           │       │   ├── PlannerBoardColumnHeader
│           │       │   │   ├── PlannerStatusBadge
│           │       │   │   ├── PlannerColumnCount
│           │       │   │   └── PlannerColumnMenu (rename, hide, delete status)
│           │       │   ├── PlannerBoardCards  [SortableContext]
│           │       │   │   └── PlannerBoardCard  [draggable]
│           │       │   │       ├── PlannerCardTitle
│           │       │   │       ├── PlannerCardMeta
│           │       │   │       │   ├── PlannerBadge (priority)
│           │       │   │       │   ├── PlannerBadge (type)
│           │       │   │       │   └── PlannerDateRange
│           │       │   │       ├── PlannerCardTags
│           │       │   │       ├── PlannerCardAssignees (Phase 6 — participants)
│           │       │   │       └── PlannerCardProgress (milestone progress bar)
│           │       │   └── PlannerBoardAddCard  (inline add at column bottom)
│           │       └── PlannerBoardAddColumn  (add new status/value)
│           │
│           ├── ── Roadmap View (Phase 4) ──────────────────────────────
│           │   PlannerRoadmapView
│           │   ├── PlannerRoadmapToolbar
│           │   │   ├── PlannerZoomControl    (day / week / month / quarter)
│           │   │   └── PlannerRoadmapToday   (scroll to today)
│           │   │
│           │   └── PlannerRoadmapCanvas
│           │       ├── PlannerRoadmapSidebar  (frozen left column: item titles)
│           │       │   └── PlannerRoadmapSidebarRow  (per milestone or event)
│           │       └── PlannerRoadmapTimeline  [SVG or Canvas renderer]
│           │           ├── PlannerTimelineHeader   (date markers, today line)
│           │           ├── PlannerTimelineGrid     (background grid lines)
│           │           └── PlannerTimelineRow      (per milestone/event)
│           │               ├── PlannerTimelineBar  (draggable + resizable)
│           │               └── PlannerDependencyArrow (blocking dependency lines)
│           │
│           └── ── List View (Phase 1, existing) ───────────────────────
│               PlannerListView
│               ├── PlannerMilestoneTabs      (existing)
│               ├── PlannerFilters            (existing — to be unified)
│               └── PlannerEventList
│                   └── PlannerEventRow       (existing)
│
└── Shared Overlays (rendered at page root, above viewport)
    ├── PlannerEventDrawer    (existing — side panel for event details)
    ├── PlannerMilestoneDrawer (existing)
    ├── PlannerContextMenu    (right-click menu on rows/cards)
    ├── PlannerBulkActionBar  (floats at bottom when rows selected in table)
    ├── PlannerTagInput       (TODO — shared across drawers)
    ├── PlannerEmptyState     (TODO — per-view empty state)
    └── PlannerSnoozePopover  (existing)
```

---

## Shared Composables

These composables contain logic consumed by multiple view components:

| Composable | Responsibility |
|---|---|
| `usePlannerStore` | Pinia store — active milestone, selected view, filters, sorts, group-by, selected items |
| `usePlannerItems` | TanStack Query — fetches + caches planner items via GraphQL (Phase 3) or REST |
| `usePlannerFields` | Field schema — system + custom fields, ordered for column display |
| `usePlannerFilters` | Filter state — add/remove/clear filter rules |
| `usePlannerSorts` | Sort state — multi-column sort rules |
| `useInlineEdit` | Manages focus, edit mode, and save for a single cell/field |
| `useOptimisticUpdate` | Wraps mutations with optimistic local state + rollback |
| `usePlannerRealtime` | Echo subscription management, event dispatch to store |
| `usePlannerBoardDnd` | dnd-kit drag state, column/card reorder logic |
| `useRoadmapLayout` | Timeline bar positioning, zoom level calculations |

---

## State Flow

```
Pinia (usePlannerStore)
  ├── activeView: PlannerView          ← which named view is active
  ├── activeMilestoneId: string|null   ← current milestone tab
  ├── filters: FilterRule[]            ← shared across all views
  ├── sorts: SortRule[]                ← shared across all views
  ├── groupBy: GroupByConfig|null      ← shared across all views
  ├── selectedItemIds: Set<string>     ← for bulk actions
  └── fieldSchema: PlannerField[]      ← ordered columns / system + custom

TanStack Query (usePlannerItems)
  ├── query key: [milestone_id, filters, sorts]
  ├── staleTime: 30s
  └── data: PlannerItem[]              ← flows into table/board/roadmap
```

---

## Naming Conventions

- All planner-specific Vue components are prefixed `Planner`
- Shared UI primitives (Button, Badge, Input) come from shadcn-vue — no `Planner` prefix
- Composables are prefixed `usePlanner` when planner-specific
- View-specific sub-components live in `resources/js/components/planner/table/`, `board/`, `roadmap/`
