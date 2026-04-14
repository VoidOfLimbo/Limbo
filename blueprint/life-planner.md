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

---

## Frontend Library Decisions

**Strategy: build all UI from scratch.** All UI components are owned by us — no black-box third-party UI libraries. This is a solo project built with AI as a coding partner, so the time cost of building custom is viable. Components will eventually be extracted into a standalone reusable package.

Logic and behaviour utilities are fair game — they handle well-defined problems (datetime math, accessibility primitives) without dictating visual design.

### Logic Utilities (use freely)

| Package | Status | Purpose |
|---|---|---|
| `@vueuse/core` | ✅ Installed | `useDraggable`, `useResizeObserver`, `useElementBounding` — drag/drop and layout |
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
