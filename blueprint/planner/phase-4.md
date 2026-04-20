# Planner — Phase 4: Roadmap View

**Goal:** Add a horizontal timeline/Gantt-style Roadmap view that renders milestones and events as bars across time, with zoom levels, drag-to-reschedule, resize handles, and dependency arrows.

**Status: Scoped — design only, not yet started ❌**

**Depends on:** [`blueprint/planner/phase-3.md`](./phase-3.md) (custom fields + `planner_iterations`)

**Implementation docs:**
- [`blueprint/planner-views/roadmap-view.md`](../planner-views/roadmap-view.md) — Full implementation guide (layout, coordinate system, bar drag/resize, dependency arrows)
- [`blueprint/planner-views/component-tree.md`](../planner-views/component-tree.md) — Component hierarchy

---

## Scope

The Roadmap view renders the same event/milestone data as a **horizontal timeline** — similar to GitHub Projects' Roadmap or a simplified Gantt chart. It answers: *"What's happening in the next 3 months?"*

Unlike a formal Gantt (which may come later), the Roadmap is focused on visual clarity over precise project management.

---

## Key Features

| Feature | Description |
|---|---|
| Milestone bars | Full-width bars spanning `start_at` → `end_at` of each milestone |
| Event bars | Narrower bars per event, nested within milestone rows |
| Zoom levels | Day / Week / Month / Quarter — adjusts horizontal scale |
| Drag to reschedule | Drag bar left/right to change `start_at` / `end_at` |
| Resize handles | Drag left/right edge to extend or shorten duration |
| Dependency arrows | SVG curved lines connecting blocked events (`event_dependencies`) |
| Today indicator | Vertical accent line at today's date |
| Iteration bands | Background highlighting for iteration periods (custom field type) |
| Group by milestone | Default — events nested under their milestone row |
| Scroll sync | Left sidebar (item titles) + right timeline scroll in sync |

---

## Rendering Strategy

Start with **HTML divs + CSS transforms** for bars. Milestone rows are virtualized with `RecycleScroller`. If performance degrades at 500+ visible rows, migrate the bar layer to Canvas while keeping the row sidebar in HTML.

→ Full rendering decisions: [`blueprint/planner-views/roadmap-view.md`](../planner-views/roadmap-view.md)

---

## Coordinate System

All bar positions computed by a `useRoadmapLayout` composable:

```typescript
{
    columnWidth: number      // px per unit (day/week/month/quarter)
    totalWidth: number       // total timeline width in px
    viewStart: Date          // leftmost visible date
    viewEnd: Date            // rightmost visible date
    dateToX: (date: Date) => number
    xToDate: (x: number) => Date
    widthFromDuration: (start: Date, end: Date) => number
}
```

Switching zoom re-calculates all bar positions — no data re-fetch needed.

---

## New Database Requirements

One new table introduced in Phase 4:

### `planner_iterations` (via `iteration` custom field type)
Iteration is a custom field type (`type = 'iteration'`) added in Phase 3. Phase 4 adds the backing `planner_iterations` table that stores the actual iteration date ranges:

```php
$table->ulid('id')->primary();
$table->foreignUlid('field_id')->constrained('planner_fields')->cascadeOnDelete();
$table->string('title');           // e.g. "Sprint 1", "Q2 2026"
$table->date('start_date');
$table->date('end_date');
$table->unsignedSmallInteger('position')->default(0);
$table->timestamps();
```

---

## Component Structure

```
PlannerRoadmapView
├── PlannerRoadmapToolbar
│   ├── ZoomControl          day | week | month | quarter
│   ├── TodayButton          → scroll to today
│   └── ShowDependencies     toggle dependency arrows
│
└── PlannerRoadmapCanvas     [flex row, fills remaining height]
    ├── PlannerRoadmapSidebar  [fixed-width ~260px]
    │   └── RecycleScroller
    │       └── PlannerRoadmapSidebarRow × N
    │           ├── Expand toggle (milestones)
    │           ├── Item title
    │           └── Progress bar (milestones)
    │
    └── PlannerRoadmapTimeline [flex-1, overflow-x scroll]
        ├── PlannerTimelineHeader (sticky top)
        │   ├── Major labels  (months, quarters)
        │   └── Minor labels  (weeks, days)
        ├── PlannerTimelineGrid (position: absolute)
        │   ├── Today vertical line
        │   ├── Weekend shading (day/week zoom)
        │   └── Iteration bands
        └── PlannerTimelineRows (scroll-synced with sidebar)
            ├── PlannerTimelineBar × N  [draggable + resizable]
            └── SVG overlay layer       [dependency arrows]
```

---

## Checklist

### New packages / dependencies
- [ ] `vue-virtual-scroller` — row virtualization (shared with Table view if added)
- [ ] No new npm packages expected; uses `@vueuse/core` `useDraggable` for bar drag/resize

### Database
- [ ] Migration: `planner_iterations`
- [ ] Model: `PlannerIteration`
- [ ] Relationship: `PlannerField hasMany PlannerIteration`

### Backend
- [ ] GraphQL type + queries for iterations
- [ ] Update event/milestone update endpoints to handle `start_at`/`end_at` changes from drag

### Frontend
- [ ] `useRoadmapLayout` composable (zoom, dateToX, xToDate, widthFromDuration)
- [ ] `PlannerRoadmapView` — root component
- [ ] `PlannerRoadmapToolbar` — zoom control, today button, show dependencies toggle
- [ ] `PlannerRoadmapCanvas` — flex row container
- [ ] `PlannerRoadmapSidebar` — virtualized left panel
- [ ] `PlannerRoadmapSidebarRow` — expandable milestone / event row
- [ ] `PlannerRoadmapTimeline` — horizontally scrollable right panel
- [ ] `PlannerTimelineHeader` — major/minor date labels (sticky)
- [ ] `PlannerTimelineGrid` — today line, weekend shading, iteration bands
- [ ] `PlannerTimelineRows` — virtualized row layer (scroll-synced with sidebar)
- [ ] `PlannerTimelineBar` — draggable + left/right resizable bar
- [ ] Dependency arrow SVG overlay
- [ ] Add `roadmap` to `PlannerViewSwitcher`
- [ ] Scroll sync between sidebar and timeline panels
