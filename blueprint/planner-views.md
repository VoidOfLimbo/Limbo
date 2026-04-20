# Planner Views — GitHub Projects-Style UI

**Status:** Blueprint / Design
**First discussed:** 2026-04-19
**Depends on:** [`blueprint/planner.md`](./planner.md), [`blueprint/planner/phase-1.md`](./planner/phase-1.md)

---

## Overview

The Planner evolves from a simple list view into a **full GitHub Projects-style workspace** — a data-centric multi-view interface where events and milestones are treated as structured records. Like GitHub Projects (and Notion/Airtable), the same underlying data renders into completely different visual paradigms based on user preference.

This is not just a UI change. It requires a new data layer (custom fields, view configurations), a new API strategy (GraphQL via Lighthouse), and real-time sync (WebSockets via Echo).

The scope of the life planner is deliberately broader than project management tools: it tracks *everything going on in a user's life* — work, health, finance, relationships, personal goals — not just code projects.

---

## Architecture

```
Frontend (Vue 3 + shadcn-vue + TanStack Table + dnd-kit)
   │
   │ GraphQL Queries / Mutations
   ▼
Laravel GraphQL API (Lighthouse)
   │
   ├── PostgreSQL (core tables + JSONB custom fields)
   │
   └── WebSocket Subscriptions (Laravel Echo + Pusher/Soketi)
            │
            ▼
   Frontend updates optimistically in real-time
```

---

## Views

All views display the same underlying data with shared filtering, sorting, and grouping state.

| View | Description | Phase |
|---|---|---|
| **List** | Chronological event list (existing) | Phase 1 ✅ |
| **Table** | Spreadsheet-style grid with TanStack Table + virtual scroll | Phase 2 |
| **Board** | Kanban columns by status or any single-select field | Phase 2 |
| **Roadmap** | Horizontal timeline bars mapped to milestones and events | Phase 3 |
| **Calendar** | Month/week/day grid | Existing blueprint |
| **Gantt** | Formal dependency-aware Gantt chart | Future |
| **Graph** | Data visualisation — density, completion rates | Future |

---

## Phase Breakdown

### Phase 2 — Table View + Board View
- TanStack Table with Vue Virtual Scroller
- dnd-kit drag-and-drop board
- Inline editing of all standard fields
- View switcher (List / Table / Board)
- Column visibility, ordering, resizing
- REST API (existing endpoints, extended)
- No custom fields yet — only system fields displayed

### Phase 3 — GraphQL + Custom Fields + Real-time
- Lighthouse GraphQL API (replaces REST for planner data)
- Custom field schema (`planner_fields` + `planner_field_values`)
- View configuration persistence (`planner_views`)
- WebSocket subscriptions for live updates
- Optimistic UI with automatic rollback
- Cross-milestone item linking

### Phase 4 — Roadmap View
- SVG/Canvas timeline renderer
- Iteration field type
- Milestone bars with dependency arrows
- Zoom levels (day / week / month / quarter)

---

## Key Design Decisions

### Custom Fields (JSONB)
Custom fields mirror GitHub Projects' "custom properties" — user-defined metadata attached to any planner item. Values are stored in a `planner_field_values` table with JSONB `value` column, keyed by `(field_id, item_id, item_type)`.

System fields (title, status, priority, start_at, end_at, type) are always present. Custom fields extend the schema without migration.

### GraphQL as the API Layer
The REST controllers built in Phase 1 handle Inertia page loads. GraphQL handles *data operations* inside the planner views:
- Complex filtered/sorted/paginated queries
- Nested field resolution (milestone → events → field values)
- Subscriptions for real-time push

Inertia + REST remains for page navigation and non-planner features.

### View Configuration Persistence
Each saved view is a row in `planner_views` — storing `type`, `filters`, `sorts`, `group_by`, and `layout` (column widths/visibility/order). Multiple named views per milestone, similar to GitHub Projects' view tabs.

### Optimistic UI
All mutations optimistically update local state before the server responds. On server error, the frontend rolls back to the last known server state. This is handled via Pinia stores + TanStack Query integration.

---

## Sub-Documents

| Document | Contents |
|---|---|
| [`planner-views/component-tree.md`](./planner-views/component-tree.md) | Full Vue component hierarchy for all views |
| [`planner-views/data-model.md`](./planner-views/data-model.md) | New tables: custom fields, field values, view configs |
| [`planner-views/graphql-schema.md`](./planner-views/graphql-schema.md) | Lighthouse GraphQL schema (types, queries, mutations, subscriptions) |
| [`planner-views/realtime-sync.md`](./planner-views/realtime-sync.md) | Echo + WebSocket strategy, optimistic update pattern |
| [`planner-views/table-view.md`](./planner-views/table-view.md) | TanStack Table + Vue Virtual Scroller implementation guide |
| [`planner-views/board-view.md`](./planner-views/board-view.md) | dnd-kit Kanban board implementation guide |
| [`planner-views/roadmap-view.md`](./planner-views/roadmap-view.md) | Roadmap/Timeline view — scoped for Phase 4 |
