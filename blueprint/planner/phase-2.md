# Planner — Phase 2: Table View + Board View

**Goal:** Extend the Planner with two additional view paradigms — a spreadsheet-style Table view and a Kanban Board view — while sharing the same underlying event/milestone data. Add a view switcher, inline field editing, drag-and-drop within the board, and bulk actions.

**Status: Complete ✅**

**Depends on:** [`blueprint/planner/phase-1.md`](./phase-1.md)

**Implementation docs:**
- [`blueprint/planner-views/table-view.md`](../planner-views/table-view.md) — TanStack Table implementation guide
- [`blueprint/planner-views/board-view.md`](../planner-views/board-view.md) — Kanban board implementation guide
- [`blueprint/planner-views/component-tree.md`](../planner-views/component-tree.md) — Full Vue component hierarchy

---

## Scope

Phase 2 adds views only — no new backend tables, no custom fields. All views render system fields (status, priority, type, start_at, end_at) via the existing REST endpoints.

Custom fields and view persistence are deferred to Phase 3.

---

## New Dependencies

| Package | Purpose |
|---|---|
| `@tanstack/vue-table` v8 | Column management, sorting, filtering, row selection |
| `vue-draggable-plus` v0.6.1 | SortableJS-based drag-and-drop (chosen over `@dnd-kit/vue` which was immature at build time) |
| `vue-sonner` | Toast notifications |
| `pinia` | Global view state, persisted to `localStorage` |

---

## Architecture

- The active view (`list` / `table` / `board`) is stored in a Pinia store (`usePlannerStore`) and persisted to `localStorage` — no URL param needed
- All three views receive the same `events` and `milestones` props from the Inertia controller
- Mutations (status change, card move) trigger `router.visit` with `only: ['events', 'milestones']` partial reloads — no separate GraphQL layer yet
- `PlannerViewSwitcher` lives in the page header trailing slot

---

## Table View

A spreadsheet-style grid built on TanStack Table (`@tanstack/vue-table`).

**Features built:**
- All system field columns: title, status, priority, type, start_at, end_at, milestone, tags
- Column sorting (click header)
- Row selection (checkbox column)
- Inline title editing via `PlannerFieldCell` → `PlannerFieldEditor`
- `PlannerBulkActionBar` appears when rows are selected — bulk delete, clear selection
- Empty state with `PlannerEmptyState`
- Milestone visibility toggle (`showMilestone` prop for backlog view)

**Not in Phase 2 (deferred to Phase 3):**
- Column resizing / reordering
- Custom field columns
- Row virtualization (`vue-virtual-scroller`)
- Named/saved views

---

## Board View

A Kanban board with columns per `EventStatus`, built on `vue-draggable-plus` (SortableJS).

**Features built:**
- Columns: `draft`, `upcoming`, `in_progress`, `completed`, `cancelled`, `skipped`
- `PlannerBoardCard` — priority/type badges, date range, tags display
- `PlannerBoardAddCard` — inline add at column bottom; creates event via `store` endpoint
- Drag card between columns → updates `status` via `router.visit` partial reload
- Drag card within column → reorders (optimistic; no `sort_order` persistence in board view)
- `PlannerBoardColumn` scroll independently

**Not in Phase 2 (deferred to Phase 3):**
- Group by any custom single-select field
- Column drag-to-reorder
- Drag overlay (floating card clone)
- Custom column color/rename

---

## Checklist

### New packages
- [x] Install `@tanstack/vue-table`
- [x] Install `vue-draggable-plus`
- [x] Install `vue-sonner`
- [x] Install `pinia`

### State & routing
- [x] `resources/js/stores/planner.ts` — Pinia store, `activeView` persisted to `localStorage`
- [x] `PlannerViewSwitcher` component (List / Table / Board tabs)

### Table view components
- [x] `PlannerTableView` — TanStack Table with system field columns
- [x] `PlannerFieldCell` — read mode cell, opens editor on click/focus
- [x] `PlannerBulkActionBar` — bulk delete when rows are selected

### Board view components
- [x] `PlannerBoardView` — column grid container
- [x] `PlannerBoardColumn` — droppable, scrollable column per status
- [x] `PlannerBoardCard` — draggable card with badges + metadata
- [x] `PlannerBoardAddCard` — inline "Add item" at column bottom

### Shared improvements (landed in Phase 2)
- [x] `PlannerMilestoneSelector` — compact dropdown replacing old tab strip; groupBy quarter/status/priority with collapsible groups and arc progress indicator
- [x] `PlannerMilestoneExplorer` — bottom vaul-vue drawer for browsing/selecting milestones
- [x] `PlannerChildRow` — collapsible nested event row with chevron expand toggle
- [x] Drag-to-reorder in List view (`sort_order` + `POST /events/reorder`)
