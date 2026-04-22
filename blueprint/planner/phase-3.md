# Planner — Phase 3: Custom Fields + Named Views

**Goal:** Add user-defined custom fields (stored in JSONB), persist named view configurations per milestone (replacing `localStorage`), and expose custom field columns in Table view and custom grouping in Board view — all via REST + Inertia.

> **Architecture decision:** GraphQL (Lighthouse) was evaluated but deferred. REST + Inertia partial reloads handle Calendar and Timeline views equally well, with significantly less complexity. GraphQL may be reconsidered if custom-field query complexity demands it in a future phase.
> Real-time sync (WebSockets) is also deferred — most useful once collaboration (Phase 6) lands.

**Status: Complete ✅**

**Depends on:** [`blueprint/planner/phase-2.md`](./phase-2.md)

**Implementation docs:**
- [`blueprint/planner-views/data-model.md`](../planner-views/data-model.md) — Table schemas for `planner_fields`, `planner_field_values`, `planner_views`
- [`blueprint/planner-views/component-tree.md`](../planner-views/component-tree.md) — Vue component hierarchy

---

## Scope

Phase 3 adds a new data layer on top of Phases 1 and 2. No existing columns are modified, no existing endpoints are changed.

**What this phase delivers:**
1. User-defined custom fields per milestone (or global across all milestones)
2. Custom field values stored in JSONB — flexible without migrations
3. Named, persisted view configurations (replace `localStorage` with `planner_views` table)
4. Custom field columns rendered in Table view
5. Custom field group-by in Board view

---

## No New Dependencies

No new PHP or npm packages required. Everything is built on the existing stack.

---

## Database

Three new tables — purely additive, no existing columns changed.

### `planner_fields` — Custom field definitions
Scoped to a milestone (`milestone_id`) or global (user-level, `milestone_id = null`).

Field types: `text`, `number`, `date`, `single_select`, `multi_select`, `iteration`, `url`, `person`, `checkbox`

System fields (`is_system = true`) are seeded automatically: Title, Status, Priority, Type, Start Date, End Date, Milestone. They cannot be deleted and their type is fixed.

→ Full schema: [`blueprint/planner-views/data-model.md`](../planner-views/data-model.md)

### `planner_field_values` — Custom field values
One row per (item, field) pair. Polymorphic — supports both `Event` and `Milestone`. Values stored as JSONB.

### `planner_views` — Named, persisted view configurations
Replaces the `localStorage` view state from Phase 2. Each row stores: `type` (list/table/board/roadmap), `filters`, `sorts`, `group_by`, `layout` (column widths/visibility/order for Table; group field for Board). Multiple named views per milestone, similar to GitHub Projects' view tabs.

---

## REST API

All planner operations stay on existing REST endpoints. Phase 3 adds new endpoints only for the new resources.

### New endpoints

#### `PlannerFieldController`
```
GET    /planner/fields                    → list user's fields (system + custom, scoped)
POST   /planner/fields                    → create custom field
PUT    /planner/fields/{field}            → update field (name, options, settings, position)
DELETE /planner/fields/{field}            → delete custom field (system fields: 403)
POST   /planner/fields/{field}/options    → add a select option
DELETE /planner/fields/{field}/options/{option} → remove a select option
```

#### `PlannerFieldValueController`
```
PUT    /planner/field-values/{field}/{itemType}/{itemId}  → upsert a field value
DELETE /planner/field-values/{field}/{itemType}/{itemId}  → clear a field value
```

#### `PlannerViewController`
```
GET    /planner/views                     → list user's saved views (for milestone or global)
POST   /planner/views                     → create a named view
PUT    /planner/views/{view}              → update view config (layout, filters, sorts, group_by)
DELETE /planner/views/{view}              → delete a view
POST   /planner/views/{view}/activate     → set as default for the milestone
```

### `PlannerController` changes
- `fields` prop added to page data (Inertia::defer) — loads system + custom fields for the milestone
- `views` prop added — loads saved views for the milestone
- Active view ID comes from `?view=` query param (UUID) instead of only `localStorage`

---

## Frontend Changes

### Pinia store (`usePlannerStore`)
- Add `activeViewId: string | null` — synced with `?view=` URL param; falls back to `localStorage` for "last used"
- `fields: PlannerField[]` — loaded from Inertia deferred prop
- `savedViews: PlannerView[]` — loaded from Inertia deferred prop

### New components
- `PlannerViewTabs` — tab strip above the event list showing saved view names; "+" to create a new view
- `PlannerFieldManager` — drawer for creating/editing/deleting custom fields

### Table view additions
- Custom field columns rendered alongside system columns (from `fields` prop)
- Column visibility / ordering persisted via `PUT /planner/views/{view}`

### Board view additions
- Group by any single-select custom field (not just `status`)
- Group-by selection persisted to the active `planner_view`

---

## Checklist

### Database
- [x] Migration: `planner_fields`
- [x] Migration: `planner_field_values`
- [x] Migration: `planner_views`
- [x] Model: `PlannerField`
- [x] Model: `PlannerFieldValue`
- [x] Model: `PlannerView`
- [x] `PlannerSystemFieldsSeeder` — seeds the 7 built-in system fields per user
- [x] Add `fieldValues` relation to `Event` and `Milestone` models
- [x] Add `fields` + `plannerViews` relations to `Milestone` model

### Backend (REST)
- [x] `PlannerFieldController` (index, store, update, destroy, storeOption, destroyOption)
- [x] `PlannerFieldValueController` (upsert, destroy)
- [x] `PlannerViewController` (index, store, update, destroy, activate)
- [x] Form requests: `StorePlannerFieldRequest`, `UpdatePlannerFieldRequest`, `UpsertPlannerFieldValueRequest`, `StorePlannerViewRequest`, `UpdatePlannerViewRequest`
- [x] Policies: `PlannerFieldPolicy`, `PlannerViewPolicy`
- [x] Register new routes in `routes/planner.php`
- [x] Run `sail artisan wayfinder:generate --with-form`
- [x] `PlannerController` — add `fields` + `savedViews` as deferred Inertia props

### Frontend
- [x] TypeScript types: `PlannerField`, `PlannerFieldValue`, `PlannerView`, `PlannerFieldOption` in `resources/js/types/planner.ts`
- [x] Rename `PlannerView` type alias in store to `PlannerViewMode` to avoid collision
- [x] `PlannerViewTabs` component (tab strip + create button)
- [x] `PlannerFieldManager` component (drawer — create/edit/delete fields)
- [x] Custom field columns in `PlannerTableView` (deferred `fields` prop, custom cols appended after system cols)
- [x] Custom field group-by in `PlannerBoardView`
- [x] Wire `PlannerViewTabs` into `Planner/Index.vue`

### Testing
- [x] Can create / update / delete a custom field
- [x] Cannot modify system fields
- [x] Can upsert / clear a field value
- [x] Policy: cannot access another user's fields or views
- [x] Can create / update / delete a named view
- [x] `PlannerController` returns `fields` and `views` props (deferred — verified via dedicated endpoint isolation tests)

