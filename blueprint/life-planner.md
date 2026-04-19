# Life Planner — Feature Blueprint

**Status:** Planning
**First discussed:** 2026-04-13
**Feature slug:** `life-planner`

---

## Overview

Life Planner lets users plan and track events across every dimension of their life — work, personal, health, finance, and beyond. Events are organized under **Milestones** (broad goals) and tagged freely. Events can be nested (sub-events/tasks inside a parent event), recurring, and shared with other users.

---

## Core Concepts

### Milestone
A milestone is a **broad goal** that may take a meaningful span of time to achieve. It is not a single moment — it has a start and, optionally, a calculated or explicit end date. Multiple events live inside a milestone. The total duration of the milestone can be automatically derived from its child events (earliest start → latest end), or manually set.

> Example: "Launch my freelance design business" is a milestone. It contains events like "Build portfolio site", "Register business name", "Land first client".

### Event
An event is a discrete unit of planned activity. Events live either directly on the planner or inside a milestone. Events can be nested (a parent event containing child events / tasks).

**Fields:**
| Field | Type | Notes |
|---|---|---|
| `id` | ULID | PK |
| `user_id` | ULID | Owner / creator |
| `milestone_id` | ULID | nullable — FK to milestones |
| `parent_event_id` | ULID | nullable — self-referencing FK for nesting |
| `title` | string | Required |
| `description` | text | nullable |
| `type` | enum | `event`, `task`, `milestone_marker` |
| `status` | enum | `draft`, `upcoming`, `in_progress`, `completed`, `cancelled`, `skipped` |
| `priority` | enum | `low`, `medium`, `high`, `critical` |
| `start_at` | datetime | nullable (milestone markers may have only a date) |
| `end_at` | datetime | nullable |
| `is_all_day` | boolean | default false |
| `is_milestone_marker` | boolean | zero-duration point-in-time flag |
| `recurrence_rule` | json | nullable — stores full recurrence definition |
| `recurrence_ends_at` | datetime | nullable — when recurring stops |
| `recurrence_count` | integer | nullable — max occurrences |
| `visibility` | enum | `private`, `shared` |
| `color` | string | nullable — hex; used in views |
| `location` | string | nullable |
| `snoozed_until` | datetime | nullable — event is suppressed from views until this datetime |
| `snooze_count` | integer | default 0 — how many times this event has been snoozed |
| `sort_order` | integer | nullable — manual list-view sort order; ordered `NULLS LAST, start_at ASC` |
| `created_at` / `updated_at` | timestamps | |
| `deleted_at` | timestamp | soft delete |

> **Snooze behaviour:** A snoozed event is hidden from active views (List, Calendar, Kanban) until `snoozed_until` passes. It remains visible in Table view and is always accessible via a "Snoozed" filter. When the snooze expires the event resurfaces normally.

### Tags
Free-form labels attached to any event or milestone. No fixed categories — users define their own taxonomy.

**`tags` table:**
| Field | Type | Notes |
|---|---|---|
| `id` | ULID | PK |
| `user_id` | ULID | tag belongs to the user |
| `name` | string | e.g. "work", "health", "urgent" |
| `color` | string | nullable hex |

**`taggables` (polymorphic pivot):**
| Field | Type |
|---|---|
| `tag_id` | ULID |
| `taggable_id` | ULID |
| `taggable_type` | string |

### Recurrence Rules
Stored as JSON on the event (inspired by iCalendar RRULE but schema-first):

```json
{
  "frequency": "weekly",          // daily | weekly | monthly | yearly | custom
  "interval": 1,                  // every N units
  "days_of_week": ["mon", "wed"], // for weekly
  "days_of_month": [1, 15],       // for monthly-by-date
  "week_of_month": null,          // for monthly-by-position (e.g. 2nd Tuesday)
  "months_of_year": [1, 6],       // for yearly
  "end_condition": "count",       // count | date | never
  "count": 10,
  "until": "2027-01-01T00:00:00Z"
}
```

Individual occurrences generated from the rule will be stored in an **`event_occurrences`** table (materialized, to support editing single/all/following occurrences).

### Event Dependencies

Events within a milestone can be formally linked to express ordering constraints. Two dependency modes exist:

- **Blocking (ordered):** Event B cannot start until Event A is marked `completed`. The dependent event is visually locked in views until its blocker is resolved.
- **Unordered:** Events can be completed in any sequence. No constraint is enforced; ordering is purely visual.

**`event_dependencies` table:**
| Field | Type | Notes |
|---|---|---|
| `id` | ULID | |
| `event_id` | ULID | FK — the dependent event ("B") |
| `depends_on_event_id` | ULID | FK — the blocking event ("A") |
| `type` | enum | `blocking`, `informational` |
| `created_at` | timestamp | |

> `informational` dependencies are shown visually (arrows in Flow Chart / Gantt) but do not enforce ordering constraints. `blocking` dependencies actively prevent the dependent event from transitioning to `in_progress` or `completed` until all its blockers are `completed`.

### Reminders
Reminders fire before or after an event. Multiple reminders per event.

**`event_reminders` table:**
| Field | Type | Notes |
|---|---|---|
| `id` | ULID | |
| `event_id` | ULID | FK |
| `offset_minutes` | integer | negative = before; positive = after |
| `channels` | json | `["in_app", "email", "push"]` |
| `sent_at` | timestamp | nullable — when actually dispatched |

### Collaboration / Sharing
Events can be shared with other **registered users** of the application. Invites are by username or email address lookup within the platform — external (non-registered) invites are not supported.

Shared users have one of three roles:

| Role | Can view | Can edit dates/details | Can invite others | Can delete |
|---|---|---|---|---|
| `viewer` | ✓ | — | — | — |
| `editor` | ✓ | ✓ | — | — |
| `co_owner` | ✓ | ✓ | ✓ | ✓ |

**`event_participants` table:**
| Field | Type | Notes |
|---|---|---|
| `id` | ULID | |
| `event_id` | ULID | FK |
| `user_id` | ULID | FK — invitee |
| `invited_by` | ULID | FK — inviter |
| `role` | enum | `viewer`, `editor`, `co_owner` |
| `status` | enum | `pending`, `accepted`, `declined` |
| `responded_at` | timestamp | nullable |

---

## Views

All views display the same underlying data — just different presentations. Switching between views should preserve active filters and date range.

| View | Description |
|---|---|
| **List** | Chronological list of events; collapsible by milestone; primary view |
| **Calendar** | Month/week/day grid with drag-to-create and drag-to-reschedule |
| **Timeline** | Horizontal Gantt-style bar chart — best for milestones and multi-day events |
| **Table** | Spreadsheet-style row/column; sortable and filterable |
| **Kanban** | Columns by status (`draft`, `upcoming`, `in_progress`, `completed`) |
| **Gantt Chart** | Formal project-style Gantt with dependency relationships (future) |
| **Flow Chart** | Dependency graph — events connected by relationships (future) |
| **Graph** | Data visualization — event density, completion rates over time (future) |

---

## Milestones

**`milestones` table:**
| Field | Type | Notes |
|---|---|---|
| `id` | ULID | |
| `user_id` | ULID | owner |
| `title` | string | |
| `description` | text | nullable |
| `status` | enum | `active`, `completed`, `paused`, `cancelled` |
| `priority` | enum | `low`, `medium`, `high`, `critical` |
| `start_at` | datetime | nullable — manual override; otherwise derived from events |
| `end_at` | datetime | nullable — manual override; otherwise derived from events |
| `duration_source` | enum | `manual`, `derived` — default `derived` |
| `deadline_type` | enum | `soft`, `hard` — default `soft` |
| `progress_source` | enum | `derived`, `manual` — default `derived` |
| `progress_override` | integer | nullable — 0–100; only used when `progress_source = 'manual'` |
| `visibility` | enum | `private`, `shared` |
| `color` | string | nullable hex |
| `created_at` / `updated_at` | timestamps | |
| `deleted_at` | timestamp | soft delete |

> When `duration_source = 'derived'`, the milestone's `start_at` and `end_at` are recalculated whenever a child event's dates change.

### Milestone Progress

- **Derived (default):** Progress is calculated automatically as `(count of completed child events / total child events) × 100`. Snoozed and cancelled events are excluded from the denominator.
- **Manual override:** The user can switch `progress_source` to `manual` and set `progress_override` (0–100) to express their own percentage. The auto-derived value is still computed in the background so switching back to derived is non-destructive.

### Milestone Deadline Types

#### Soft Deadline (`deadline_type = 'soft'`)
- The milestone's `end_at` **automatically extends** when a child event's `end_at` exceeds the current milestone boundary.
- No user warning is triggered — the milestone silently absorbs schedule growth.
- Useful for personal goals where flexibility is expected.

#### Hard Deadline (`deadline_type = 'hard'`)
- The milestone's `end_at` is **locked** and cannot be increased, either manually or automatically.
- If a child event's `end_at` extends beyond the milestone's `end_at`, the system enters a **breach state** — the event is flagged as overflowing the milestone.
- The breach is surfaced visually across all views:
  | View | Breach Indication |
  |---|---|
  | **List** | Red overflow badge on the event row; warning banner on the milestone header |
  | **Calendar** | Event bar rendered in red/striped past the milestone boundary line |
  | **Timeline / Gantt** | Event bar crosses a hard deadline marker line (dashed red); milestone boundary cannot be dragged |
  | **Table** | Cell highlighted red in the `end_at` column; milestone row shows breach count |
  | **Kanban** | Card flagged with a deadline breach icon and red border |
- The user **cannot drag, extend, or update a hard milestone's `end_at`** from any view — the field is read-only once set.
- Users can resolve a breach via any of the following actions:
  1. **Adjust dates** — edit the overflowing event so its `end_at` falls within the milestone boundary.
  2. **Move to backlog** — detach the event from the milestone (`milestone_id = null`); the event is preserved but no longer associated with the milestone.
  3. **Delete the event** — permanently remove the overflowing event (soft delete).
  4. **Convert milestone to soft** — change `deadline_type` from `hard` to `soft`, allowing the milestone to extend and absorb the overflow.

---

## Tier Gating

**Not decided yet.** Noted as a future consideration. Possible vectors:
- Free: limited number of active events / milestones
- Premium: unlimited events, all views unlocked
- Loyalist: all features + priority reminders, advanced recurrence

---

## Phased Roadmap

### Phase 1 — Foundation (v1)
**Goal:** Get the core data layer and primary interaction working.

- [ ] Database: `milestones`, `events`, `event_dependencies`, `tags`, `taggables`, `event_reminders`, `event_occurrences`, `event_participants`
- [ ] Models + relationships + factories
- [ ] CRUD: Milestones (`MilestoneController`) — including `deadline_type`, `progress_source`
- [ ] CRUD: Events (`EventController`) — no recurrence yet; includes snooze action
- [ ] Hard deadline breach detection (server-side + frontend flag)
- [ ] Tags management (create, assign, remove)
- [ ] **List view** — chronological, grouped by milestone, with status/priority/deadline badges
- [ ] **Snoozed filter** — surfaces snoozed events
- [ ] Basic filters: date range, status, priority, tags
- [ ] Auth/ownership gates (policy: user can only see their own data)
- [ ] **iCalendar `.ics` export** — single event and full planner export

### Phase 2 — Calendar & Core UX
- [ ] **Calendar view** — month/week/day, drag-to-reschedule
- [ ] **Table view** — sortable/filterable spreadsheet layout
- [ ] Sub-events / nesting within a parent event
- [ ] Event color coding

### Phase 3 — Recurrence
- [ ] Recurrence rule builder (UI + backend)
- [ ] `event_occurrences` materialization
- [ ] Edit single / all / following occurrences
- [ ] Recurrence display in views

### Phase 4 — Reminders & Notifications
- [ ] In-app notification delivery
- [ ] Email reminder dispatch (queued jobs)
- [ ] Push notification (browser Web Push API)
- [ ] Reminder management UI per event

### Phase 5 — Dependencies & Ordering
- [ ] `event_dependencies` UI — link events as `blocking` or `informational`
- [ ] Enforce blocking constraints in status transitions
- [ ] Dependency visualization in List view (indented locked state)
- [ ] Flow Chart view — visual dependency graph

### Phase 6 — Collaboration
- [ ] Invite registered users to events (username / email lookup)
- [ ] Accept / decline flows
- [ ] Shared event permissions (viewer / editor / co-owner)
- [ ] Participant UI in event detail
- [ ] Milestone sharing (propagates to contained events)

### Phase 7 — Advanced Views
- [ ] **Kanban view** — drag between status columns; snoozed lane
- [ ] **Timeline / Gantt view** — horizontal bars, milestone bands, hard deadline markers
- [ ] **Flow Chart view** — dependency graph with blocking/informational edges
- [ ] **Graph / analytics view** — completion trends, heatmaps, snooze frequency

### Phase 8 — Tier Gating (TBD)
- [ ] Define gating rules per tier
- [ ] Enforce limits in controllers + display in UI

---

## Resolved Decisions

| Question | Decision |
|---|---|
| **Milestone progress** | Auto-derived from completed child count by default; user can switch to manual override (0–100%) at any time. Switching back to derived is non-destructive. |
| **Event dependencies** | Two modes: `blocking` (enforced — dependent event locked until blocker completes) and `informational` (visual only — no constraint). Events are also snoozeable individually. |
| **Timezone handling** | All datetimes stored as UTC. The frontend converts to the user's browser/local timezone for display. No per-event timezone stored for v1. |
| **Inviting non-users** | Only registered users who are members of the platform can be invited. No email-only external invites. |
| **iCalendar export** | `.ics` export supported from day one — users can sync events to Google Calendar, Apple Calendar, etc. |
| **`skipped` in progress calc** | Excluded from the denominator alongside `cancelled` and snoozed events. |
| **Data loading / update strategy** | See **Data Loading Architecture** section below. No Pinia, no separate REST API, no client-side cache. |
| **Hard milestone `end_at` mutability** | The field is fully immutable once set — cannot be increased or decreased via UI or API. Resolve breaches by adjusting events, moving events to backlog, deleting events, or converting to a soft deadline. |
| **Default view on `/planner`** | When no `?milestone=` param is present, auto-select the first milestone ordered by `created_at`. If no milestones exist, show the Backlog tab. |
| **Route file** | Life Planner routes live in `routes/planner.php`, included from `routes/web.php`. Keeps web.php clean as the feature grows. |
| **ICS status mapping** | `draft`/`upcoming` → `TENTATIVE`, `in_progress` → `CONFIRMED`, `completed` → `COMPLETED`, `cancelled`/`skipped` → `CANCELLED`. |

---

## Data Loading Architecture

All data interactions follow a strict layered strategy. No Pinia, no separate REST API, no client-side cache to synchronise. Inertia's partial reload + deferred prop model handles everything.

### Layer 1 — Initial load: only what is immediately visible

`PlannerController::index()` returns milestone tabs and filter state as standard props (rendered synchronously). Expensive lists (`events`, `tags`) are returned as `Inertia::defer()` — they arrive in a second automatic request right after the page mounts. Vue shows animated skeleton pulse states while deferred props are `undefined`.

```php
return Inertia::render('Planner/Index', [
    'milestones'        => $milestones,               // standard — tabs render immediately
    'activeMilestoneId' => $request->milestone,       // standard — drives active tab
    'filters'           => $filters,                  // standard — drives filter UI state
    'events'            => Inertia::defer(fn() => $events), // deferred
    'tags'              => Inertia::defer(fn() => $tags),   // deferred
]);
```

### Layer 2 — After CRUD: partial reloads (not full page)

After any mutation, reload only the props that changed. `preserveScroll: true` keeps the viewport position.

| Action | Partial reload scope |
|---|---|
| Create / update / delete event | `only: ['events']` |
| Snooze event | `only: ['events']` |
| Create / update milestone | `only: ['milestones', 'events']` (breach recalc) |
| Attach / detach tag | `only: ['events']` or `only: ['milestones']` |
| Change filter | `only: ['events']` |

```ts
router.delete(EventController.destroy(event.id), {
    preserveScroll: true,
    only: ['events'],
})
```

### Layer 3 — Instant feedback: optimistic updates

For snooze, status toggles, and any action with a predictable outcome: apply the change to Inertia props immediately before the server responds. Inertia automatically rolls back on failure — no manual undo needed.

```ts
router.optimistic((props) => ({
    events: {
        ...props.events,
        data: props.events.data.filter(e => e.id !== event.id),
    },
})).post(EventController.snooze(event.id), { snoozed_until: until })
```

### Layer 4 — Off-screen content: `WhenVisible`

Sections below the fold (Backlog tab content, analytics, snoozed events section) are wrapped in Inertia's `<WhenVisible>` component backed by `Inertia::defer()` props. The server query runs only when the element scrolls into the viewport. Each section supplies its own skeleton fallback.

### Layer 5 — Long lists: `InfiniteScroll`

For milestones with large event counts, events are paginated server-side with `Inertia::merge()` and loaded incrementally client-side with `<InfiniteScroll>`. New pages are appended to the existing list, not replaced.

```php
'events' => Inertia::merge(fn() => $events->paginate(20)),
```

### Layer 6 — Fire-and-forget / search-in-drawer: `useHttp`

`useHttp` is used only for standalone requests that do not produce a page prop update — e.g. a tag autocomplete search inside a drawer, or a fire-and-forget analytics ping. For anything that modifies data the page already tracks, prefer partial reloads so the server remains the single source of truth.

---

## Development Philosophy

These principles apply across every phase and every file in this feature.

### Standards
- Every implementation decision defaults to the **best available approach** for correctness, performance, and long-term maintainability — not the fastest-to-type approach.
- Follow Laravel best practices (form requests, policies, Eloquent scopes, service classes for complex logic) and Vue best practices (composables, single-responsibility components, `defineProps` + `defineEmits`).
- All PHP must pass Pint. All TypeScript/Vue must pass ESLint and Prettier.
- Every code path that matters must have a Pest feature test.

### Reusability & Component Extraction
- Components are built for extraction from day one. Every Planner component lives under `@/components/planner/` with a `Planner` prefix and zero coupling to page-level state.
- Props drive everything — no component reads from a global store or accesses `usePage()` inside a leaf component.
- Shared logic (date formatting, colour contrast, snooze offset calculation) lives in composables under `@/composables/planner/`.
- When a pattern appears twice, extract it. When a component exceeds ~200 lines of template, split it.

### Performance
- Follow the Data Loading Architecture above for every page in this feature.
- Never load data that is not needed for the current view. Use `only`, `defer`, and `WhenVisible` aggressively.
- Optimistic updates for all actions where the outcome is predictable. No spinner for snooze, status toggle, or tag attach.
- Virtual scroll for any list that may exceed 50 items in a viewport.
- `preserveScroll: true` on every mutation that is not a deliberate navigation action.

### UX
- Every async operation has a loading state — skeleton pulse for initial loads, spinner or disabled state for mutations.
- Every destructive action (delete event, delete milestone) requires a confirm dialog.
- Every form supports keyboard navigation and accessible labels (via reka-ui primitives).
- Filters and active milestone are persisted as URL query params — pages are bookmarkable and shareable.
- Toast notifications confirm mutations (created, updated, deleted, snoozed). No silent writes.
- Drawers do not navigate away from `/planner`. All CRUD opens a drawer or in-place inline form.

---

## Frontend Library Decisions

**Strategy: build all UI from scratch.** All UI components are owned by us — no black-box third-party UI libraries. This is a solo project built with AI as a coding partner, so the time cost of building custom is viable. Components will eventually be extracted into a standalone reusable package.

Logic and behaviour utilities are fair game — they handle well-defined problems (datetime math, accessibility primitives) without dictating visual design.

### Logic Utilities (use freely)

| Package | Status | Purpose |
|---|---|---|
| `@vueuse/core` | ✅ Installed | `useDraggable`, `useResizeObserver`, `useElementBounding` — drag/drop and layout |
| `vue-draggable-plus` | ✅ Installed | SortableJS-based drag-and-drop — used in Board view (cross-column) and List view (reorder) |
| `vue-sonner` | ✅ Installed | Toast notifications |
| `pinia` | ✅ Installed | State management — `planner.ts` store persists `activeView` to localStorage |
| `vaul-vue` | ✅ Installed | Snap-point bottom drawer — used for event/milestone create–edit and milestone explorer |
| `date-fns` | ❌ Add | All datetime math — grids, offsets, formatting, DST, recurrence expansion |
| `colord` | ❌ Add | Hex color validation and contrast checking |
| `lucide-vue-next` | ✅ Installed | Icons only |

### Headless Behaviour Primitives (reka-ui — already installed)

`reka-ui` is already a dependency. Its headless primitives handle ARIA compliance, keyboard navigation, focus trapping, and portal rendering. We own all visual markup and styling on top of them — these are not consumed as black-box UI components.

Used for: Popover, Dialog, Sheet (drawer), Tabs, Switch, Collapsible, Tooltip, Select.

### View Implementation Approach

| View | Implementation | Key techniques |
|---|---|---|
| **List** | Pure Vue + Tailwind | Computed grouping, Inertia props |
| **Calendar** (month/week/day) | Custom Vue component | CSS Grid, `date-fns` grid math, `useDraggable` |
| **Table** | Custom Vue component | CSS Grid, sortable columns, virtual scroll |
| **Kanban** | Custom Vue component | Flexbox columns, `useDraggable` between columns |
| **Timeline / Gantt** | Custom Vue + SVG | Pixel-precise horizontal bars, milestone bands, SVG hard deadline markers |
| **Flow Chart** | Custom Vue + SVG | DAG layout, SVG dependency edges |
| **Graph / Analytics** | Custom Vue + SVG/Canvas | Heatmaps, completion trend lines |

> **Pricing reference (for our own pricing page):** https://fullcalendar.io/pricing — FullCalendar Premium starts at $480/yr. Useful benchmark when designing Life Planner tier pricing.

---

## Related Files

| File | Status | Purpose |
|---|---|---|
| [`blueprint/life-planner/phase-1.md`](life-planner/phase-1.md) | ✅ Written | Full implementation detail for Phase 1 |
| `blueprint/life-planner/phase-2.md` | 🔜 Pending | Calendar view, Table view, sub-events |
| `blueprint/life-planner/recurrence.md` | 🔜 Pending | Recurrence rule design and edge cases |
| `blueprint/life-planner/views.md` | 🔜 Pending | View-by-view UX and component breakdown |
