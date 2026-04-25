# Planner — Phase 4: Roadmap View

**Goal:** Add a horizontal timeline/Gantt-style Roadmap view that renders milestones and events as bars across time, with zoom levels, drag-to-reschedule, resize handles, and dependency arrows.

**Status: Complete ✅**

**Depends on:** [`blueprint/planner/phase-3.md`](./phase-3.md) (custom fields + `planner_iterations`)

**Implementation docs:**
- [`blueprint/planner-views/roadmap-view.md`](../planner-views/roadmap-view.md) — Full implementation reference (layout, coordinate system, bar drag/resize)
- [`blueprint/planner-views/component-tree.md`](../planner-views/component-tree.md) — Component hierarchy

---

## Scope

The Roadmap view renders the same event/milestone data as a **horizontal timeline** — similar to GitHub Projects' Roadmap or a simplified Gantt chart. It answers: *"What's happening in the next 3 months?"*

Unlike a formal Gantt (which may come later), the Roadmap is focused on visual clarity over precise project management.

---

## Key Features

| Feature | Status | Description |
|---|---|---|
| Milestone bars | ✅ | Full-width bars spanning `start_at` → `end_at` of each milestone |
| Event bars | ✅ | Narrower bars per event, nested within milestone rows |
| Event color by status | ✅ | draft=gray, upcoming=blue, in_progress=violet, completed=green, cancelled=gray |
| Zoom levels | ✅ | Week / Month / Quarter / Year — dropdown picker, persisted to localStorage |
| Drag to reschedule | ✅ | Drag bar left/right to change `start_at` / `end_at` |
| Resize handles | ✅ | Drag left/right edge to extend/shorten duration |
| Hard-deadline lock | ✅ | Right resize handle hidden for hard-deadline milestones |
| Today indicator | ✅ | Vertical accent line + dot at today's date |
| Scroll sync | ✅ | Left sidebar + right timeline scroll in sync |
| Auto-scroll to today | ✅ | On hard refresh, viewport centers on today |
| All milestones expanded | ✅ | Opens with all milestones pre-expanded showing their events |
| Milestone scope filter | ✅ | When inside a milestone, only that milestone + its events shown |
| Optimistic updates | ✅ | Bars move instantly; server round-trip refreshes data in background |
| Stable sort on update | ✅ | Events/milestones don't jump position after a reschedule response |
| Dependency arrows | ❌ Deferred | SVG curved lines (Phase 5+) |
| Iteration bands | ❌ Deferred | Background highlighting for `planner_iterations` date ranges |

---

## Architecture Decisions

### Coordinate System

All bar positions computed by `useRoadmapLayout` composable, provided via Vue injection key `ROADMAP_LAYOUT_KEY`:

```typescript
export type ZoomLevel = 'day' | 'week' | 'month' | 'quarter' | 'year'

export const COLUMN_WIDTH: Record<ZoomLevel, number> = {
    day: 40, week: 80, month: 120, quarter: 200, year: 320
}

interface RoadmapLayoutContext {
    columnWidth: ComputedRef<number>
    totalWidth: ComputedRef<number>
    viewStart: ComputedRef<Date>     // earliestDate - 3 units
    viewEnd: ComputedRef<Date>       // latestDate + 3 units
    zoom: Ref<ZoomLevel>
    dateToX: (date: Date | string) => number
    xToDate: (x: number) => Date
    widthFromDuration: (start, end) => number
    pxPerDay: ComputedRef<number>
}
```

Switching zoom re-calculates all bar positions via `dateToX()` — no data re-fetch.

### Deferred Props + Stable Local Copies

`props.events` is a deferred Inertia prop — it becomes `undefined` during every partial reload. `PlannerRoadmapView` holds stable local copies:

```typescript
const localMilestones = ref<PlannerMilestone[]>([...props.milestones])
const localEvents = ref<PlannerEvent[]>(props.events?.data ?? [])

// Watcher skips undefined; merges by ID to preserve display order
watch(() => props.events, (val) => {
    if (!val) return
    // merge-by-ID: update in-place, preserve order, append new, drop removed
})

// Clear stale events immediately on milestone navigation
watch(() => props.activeMilestoneId, () => { localEvents.value = [] })
```

### Backend: Board + Roadmap as All-Events Views

```php
// PlannerController — roadmap scope:
$skipMilestoneFilter = $view === 'roadmap' && $activeMilestoneId === null;
// Dashboard roadmap: all user events (cross-milestone overview)
// Milestone roadmap: scoped to that milestone only
```

### Milestone Scope in Frontend

`visibleMilestones` filters `localMilestones` to just the active milestone when `activeMilestoneId` is set. The full `localMilestones` is kept for stable-merge, but only `visibleMilestones` drives the rows list and timeline viewport bounds.

### Hard-Deadline Milestone Protection

- Right resize handle hidden (`v-if="!isRightResizeLocked"`)
- `end_at` excluded from reschedule request payload
- Optimistic update preserves original `end_at`
- Backend ignores `end_at` for hard-deadline milestones anyway

---

## Component Structure

```
PlannerRoadmapView                   (root — provides ROADMAP_LAYOUT_KEY)
├── PlannerRoadmapToolbar            zoom dropdown (Week/Month/Quarter/Year), Today, Dependencies
│
└── canvas (flex row)
    ├── left sidebar (260px fixed)
    │   ├── header ("Item" label, 52px)
    │   └── PlannerRoadmapSidebar    (scroll-synced, rows list, expand toggle)
    │
    └── timelineScrollEl (flex-1, overflow-auto)
        ├── PlannerTimelineHeader    sticky top, 52px (24px major + 28px minor labels)
        └── rows area
            ├── PlannerTimelineGrid  background: column dividers, row dividers,
            │                        weekend shading, today line
            └── PlannerTimelineBar × N  pointer-events drag/resize, emits reschedule
```

---

## New Database

### `planner_iterations`

Added in Phase 4 for the `iteration` custom field type:

```php
$table->ulid('id')->primary();
$table->foreignUlid('field_id')->constrained('planner_fields')->cascadeOnDelete();
$table->string('title');           // e.g. "Sprint 1", "Q2 2026"
$table->date('start_date');
$table->date('end_date');
$table->unsignedSmallInteger('position')->default(0);
$table->timestamps();
```

Model: `PlannerIteration` (`HasUlids`, `field()` BelongsTo, dates cast as `'date'`)
Relationship: `PlannerField hasMany PlannerIteration` → `iterations()` ordered by `start_date`

---

## Checklist

### Database
- [x] Migration: `planner_iterations`
- [x] Model: `PlannerIteration`
- [x] Relationship: `PlannerField hasMany PlannerIteration`

### Backend
- [x] `PlannerController` — roadmap as all-events (no pagination), deferred prop
- [x] Milestone scope: dashboard = all events, milestone page = scoped to milestone
- [x] `buildEventsQuery` — `$skipMilestoneFilter` flag
- [x] Event/milestone update endpoints handle `start_at`/`end_at` from drag
- [x] Hard-deadline milestone `end_at` protected in `MilestoneController`

### Frontend
- [x] `useRoadmapLayout` composable (zoom, dateToX, xToDate, widthFromDuration, pxPerDay)
- [x] `PlannerRoadmapView` — root component, local stable copies, optimistic updates, scroll sync, auto-scroll to today
- [x] `PlannerRoadmapToolbar` — zoom dropdown, today button, dependencies toggle
- [x] `PlannerRoadmapSidebar` — expandable milestone/event rows, status dot, lock/breach icons
- [x] `PlannerTimelineHeader` — major/minor date labels (sticky, 52px)
- [x] `PlannerTimelineGrid` — today line, weekend shading, column/row dividers
- [x] `PlannerTimelineBar` — pointer-event drag/resize, status-based event colors, hard-deadline lock
- [x] `PlannerViewSwitcher` — Roadmap button (GanttChartSquare icon)
- [x] `Index.vue` — `handleRoadmapReschedule`, `ALL_EVENTS_VIEWS` watcher, roadmap section template
- [x] Scroll sync between sidebar and timeline panels
- [x] Auto-scroll to today on first load
- [x] Stable sort on prop updates (merge-by-ID watchers)
- [x] Milestone scope filter (`visibleMilestones` computed)
- [x] All milestones pre-expanded on open
- [x] `activeMilestoneId` watcher clears stale events on milestone navigation

### Deferred / Outstanding
- [ ] **Dependency arrow SVG overlay** — render `EventDependency` (Blocking / Informational) as curved SVG lines between bars; toggleable from `PlannerRoadmapToolbar` (button already present, currently no-op).
- [ ] **Iteration bands** — render background highlighting on the timeline grid for `planner_iterations` date ranges (table + model already exist, no UI yet).
- [ ] **Iteration custom field UI** — surface `planner_iterations` in `PlannerFieldManager` (create / edit / list iterations for an `iteration`-type field).
- [ ] **Row virtualization** with `RecycleScroller` (only if 500+ rows causes perf issues; currently rendering all rows).

---

## Rendering Strategy

Bars: HTML divs + CSS transforms positioned via `dateToX()` / `widthFromDuration()`. No virtualization yet — revisit only if 500+ visible rows lags. Keep sidebar in HTML even if we ever migrate the bar layer to Canvas.

→ Full rendering reference: [`blueprint/planner-views/roadmap-view.md`](../planner-views/roadmap-view.md)

