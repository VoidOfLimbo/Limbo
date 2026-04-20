# Planner — Phase 3: GraphQL + Custom Fields + Real-time

**Goal:** Replace the REST planner API with Lighthouse GraphQL, add user-defined custom fields (stored in JSONB), persist named view configurations per milestone, and enable real-time sync across tabs/devices via Laravel Echo + WebSockets.

**Status: Blueprint complete — not yet started ❌**

**Depends on:** [`blueprint/planner/phase-2.md`](./phase-2.md)

**Implementation docs:**
- [`blueprint/planner-views/data-model.md`](../planner-views/data-model.md) — New tables: `planner_fields`, `planner_field_values`, `planner_views`
- [`blueprint/planner-views/graphql-schema.md`](../planner-views/graphql-schema.md) — Lighthouse schema (types, queries, mutations, subscriptions)
- [`blueprint/planner-views/realtime-sync.md`](../planner-views/realtime-sync.md) — Echo + WebSocket strategy, optimistic update pattern
- [`blueprint/planner-views/component-tree.md`](../planner-views/component-tree.md) — Full Vue component hierarchy

---

## Scope

Phase 3 is a significant infrastructure upgrade. It does not remove existing functionality — it layers a new API and data model on top of what Phases 1 and 2 built.

**Key principle:** Inertia + REST remains for page navigation and initial data load. GraphQL handles *all data operations inside the planner views* — queries, mutations, real-time subscriptions.

---

## New Dependencies

### Backend
| Package | Purpose |
|---|---|
| `nuwave/lighthouse` | Laravel GraphQL server (schema-first SDL) |
| Soketi (Docker service in `compose.yaml`) | Self-hosted WebSocket server (Pusher-compatible) |

### Frontend
| Package | Purpose |
|---|---|
| `laravel-echo` | WebSocket client (Pusher protocol) |
| `pusher-js` | Required peer dependency for Echo Pusher driver |

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

## GraphQL API (Lighthouse)

Schema lives at `graphql/schema.graphql`. Key design:
- `@paginate`, `@where`, `@orderBy` directives for filtering/sorting
- `@can` directives for policy-driven authorization
- `@broadcast` on mutations → triggers Echo subscriptions
- Nested resolvers for `milestone → events → field_values`

→ Full schema: [`blueprint/planner-views/graphql-schema.md`](../planner-views/graphql-schema.md)

---

## Real-time Sync

Private channel per user: `private-planner.{userId}`.

On any mutation (create/update/delete event or field value), the server:
1. Persists the change
2. Dispatches a `ShouldBroadcast` event on the user's channel

The frontend:
1. Applies the change optimistically in Pinia before the server responds
2. Subscribes to the private channel via Echo on mount
3. Reconciles server-confirmed state with local state on broadcast
4. Rolls back if the server returns an error

→ Full strategy: [`blueprint/planner-views/realtime-sync.md`](../planner-views/realtime-sync.md)

---

## Frontend Changes

### Pinia store upgrade
`usePlannerStore` gains:
- `fieldSchema: PlannerField[]` — loaded once on mount
- `pendingMutations: Map<string, OptimisticMutation>` — tracks in-flight changes for rollback
- `useOptimisticUpdate` composable — wraps any mutation with optimistic apply + rollback

### New composables
- `usePlannerRealtime` — subscribes to the private Echo channel, patches Pinia on broadcast
- `useOptimisticUpdate` — generic optimistic mutation wrapper

### Table view additions (Phase 3 column layer)
- Custom field columns rendered alongside system columns
- Column visibility / reorder / resize (persisted to `planner_views`)
- `PlannerFieldManager` — UI for creating/editing/deleting custom fields (drawer)

### Board view additions
- Group by any single-select custom field (not just `status`)
- Column reorder persisted to `planner_views`

---

## Checklist

### Infrastructure
- [ ] Install `nuwave/lighthouse` — `composer require nuwave/lighthouse`
- [ ] Add Soketi service to `compose.yaml`
- [ ] Install `laravel-echo` + `pusher-js` — `npm install laravel-echo pusher-js`
- [ ] Configure `config/broadcasting.php` with Pusher/Soketi driver
- [ ] Add `BROADCAST_CONNECTION=pusher` + Soketi env vars to `.env`

### Database
- [ ] Migration: `planner_fields`
- [ ] Migration: `planner_field_values`
- [ ] Migration: `planner_views`
- [ ] Models: `PlannerField`, `PlannerFieldValue`, `PlannerView`
- [ ] `PlannerSystemFieldsSeeder` — seeds the 7 built-in system fields

### GraphQL API
- [ ] `graphql/schema.graphql` — full schema
- [ ] Resolver classes for all queries and mutations
- [ ] Subscription support — `PlannerItemUpdated`, `PlannerFieldValueUpdated`
- [ ] Broadcast events + model observers
- [ ] Channel authorization (`routes/channels.php`)

### Frontend
- [ ] Upgrade `usePlannerStore` — add `fieldSchema`, `pendingMutations`
- [ ] `useOptimisticUpdate` composable
- [ ] `usePlannerRealtime` composable (Echo subscription + Pinia reconcile)
- [ ] `PlannerFieldManager` — custom field CRUD drawer
- [ ] Custom field columns in Table view
- [ ] Custom field grouping in Board view
- [ ] Named/saved views — replace `localStorage` with `planner_views` (view tabs in selector)

### Testing
- [ ] GraphQL query/mutation tests (Pest)
- [ ] Broadcast event tests
- [ ] Optimistic rollback tests
