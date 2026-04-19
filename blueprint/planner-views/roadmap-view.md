# Planner Views — Roadmap View

**Depends on:** [`blueprint/planner-views/component-tree.md`](./component-tree.md), [`blueprint/planner-views/data-model.md`](./data-model.md)
**Status:** Scoped for Phase 4 — design only, not yet built

---

## Overview

The Roadmap View renders milestones and events as **horizontal timeline bars** — similar to GitHub Projects' Roadmap view or a simplified Gantt chart. It gives users a bird's-eye view of how their life plan is distributed across time.

Unlike a formal Gantt chart (which we may build separately), the Roadmap is focused on **visual clarity** over precise project management. It is the best view for answering: *"What's happening in the next 3 months?"*

---

## Key Features (Phase 4 target)

| Feature | Description |
|---|---|
| Milestone bars | Full-width bars spanning `start_at` → `end_at` of each milestone |
| Event bars | Narrower bars per event, nested within milestone rows |
| Zoom levels | Day / Week / Month / Quarter — adjusts horizontal scale |
| Drag to reschedule | Drag bar left/right to change `start_at` / `end_at` |
| Resize handles | Drag left/right edge to extend or shorten duration |
| Dependency arrows | Lines connecting blocked events (from `event_dependencies`) |
| Today indicator | Vertical red/accent line at today's date |
| Iteration bands | Background highlighting for iteration periods (Phase 4+ custom field) |
| Group by milestone | Default grouping — events nested under their milestone row |
| Scroll sync | Left panel (item titles) + right panel (timeline) scroll in sync |

---

## Rendering Strategy

### SVG vs Canvas vs HTML

| Approach | Pros | Cons |
|---|---|---|
| **SVG** | Easy to target events, CSS animations, screen reader support | Slow with >500 elements |
| **Canvas** | Fast for massive datasets | No native events, must implement hit testing |
| **HTML divs** | Simplest integration with Vue reactivity, CSS transitions | Slower at very high row counts |

**Decision for Phase 4:** Start with **HTML divs + CSS transforms** for bars. Milestone rows are virtualized with `RecycleScroller`. If performance degrades at 500+ visible rows, migrate the bar layer to Canvas while keeping the row sidebar in HTML.

---

## Layout Structure

```
PlannerRoadmapView
├── PlannerRoadmapToolbar
│   ├── ZoomControl          day | week | month | quarter
│   ├── TodayButton          → scroll to today
│   └── ShowDependencies     toggle dependency arrows
│
└── PlannerRoadmapCanvas     [flex row, fills remaining height]
    │
    ├── PlannerRoadmapSidebar  [fixed-width left panel, ~260px]
    │   └── RecycleScroller    [virtualized]
    │       └── PlannerRoadmapSidebarRow  × N
    │           ├── Expand toggle (for milestones)
    │           ├── Item title
    │           └── Progress bar (milestones)
    │
    └── PlannerRoadmapTimeline [flex-1, overflow-x: scroll]
        ├── PlannerTimelineHeader    [sticky top]
        │   ├── Major labels  (months, quarters)
        │   └── Minor labels  (weeks, days)
        │
        ├── PlannerTimelineGrid     [background, position: absolute]
        │   ├── Today vertical line
        │   ├── Weekend shading (when zoom = day/week)
        │   └── Iteration bands (Phase 4 custom field)
        │
        └── PlannerTimelineRows     [scroll-synced with sidebar]
            └── PlannerTimelineRow × N  [virtualized]
                ├── PlannerTimelineBar    [position: absolute, draggable + resizable]
                └── PlannerDependencyArrow (SVG overlay layer, Phase 4)
```

---

## Coordinate System

All bar positions are calculated from a `useRoadmapLayout` composable:

```typescript
// resources/js/composables/useRoadmapLayout.ts

export type ZoomLevel = 'day' | 'week' | 'month' | 'quarter'

interface RoadmapLayout {
    columnWidth: number          // px per unit (day/week/month/quarter)
    totalWidth: number           // total timeline width in px
    viewStart: Date              // leftmost visible date
    viewEnd: Date                // rightmost visible date
    dateToX: (date: Date) => number
    xToDate: (x: number) => Date
    widthFromDuration: (start: Date, end: Date) => number
}

export function useRoadmapLayout(zoom: Ref<ZoomLevel>): RoadmapLayout {
    const columnWidth = computed(() => ({
        day:     40,   // 40px per day
        week:    80,   // 80px per week (~11px/day)
        month:   120,  // 120px per month (~4px/day)
        quarter: 200,  // 200px per quarter (~2px/day)
    }[zoom.value]))

    // Window: always show ±3 units of padding beyond earliest/latest item
    const viewStart = computed(() => subUnits(earliestDate.value, 3, zoom.value))
    const viewEnd   = computed(() => addUnits(latestDate.value, 3, zoom.value))

    const totalWidth = computed(() =>
        differenceInUnits(viewEnd.value, viewStart.value, zoom.value) * columnWidth.value
    )

    function dateToX(date: Date): number {
        return differenceInUnits(date, viewStart.value, zoom.value) * columnWidth.value
    }

    function xToDate(x: number): Date {
        return addUnits(viewStart.value, x / columnWidth.value, zoom.value)
    }

    function widthFromDuration(start: Date, end: Date): number {
        return Math.max(
            differenceInUnits(end, start, zoom.value) * columnWidth.value,
            columnWidth.value / 4,  // minimum bar width = 1/4 unit
        )
    }

    return { columnWidth, totalWidth, viewStart, viewEnd, dateToX, xToDate, widthFromDuration }
}
```

---

## Bar Drag + Resize

```typescript
// Bar drag — move entire bar (changes start_at + end_at by same delta)
// Bar resize — drag left handle (changes start_at only) or right handle (changes end_at only)

function onBarDragEnd(item: PlannerItem, deltaX: number) {
    const layout = useRoadmapLayout(zoom)
    const deltaDays = deltaX / layout.columnWidth.value    // fractional days

    const newStart = addDays(item.startAt, Math.round(deltaDays))
    const newEnd   = addDays(item.endAt,   Math.round(deltaDays))

    mutate(item.id, { startAt: newStart, endAt: newEnd }, () =>
        updateEvent({ id: item.id, input: { startAt: newStart, endAt: newEnd } })
    )
}
```

Use `@vueuse/core`'s `useDraggable` for the drag implementation — avoids a full dnd-kit setup for what is essentially constrained horizontal drag.

---

## Dependency Arrows (SVG Overlay)

When `show_dependencies = true`, render an SVG layer on top of the timeline rows showing finish-to-start dependency arrows:

```
Event A end ──────────────→ Event B start
              (curved arrow)
```

SVG `<path>` with a cubic bezier curve:
```
M (ax + aw) (ay + rowHeight/2)
C (ax + aw + 40) (ay + rowHeight/2)
  (bx - 40) (by + rowHeight/2)
  bx (by + rowHeight/2)
```

Where:
- `ax`, `aw` = x position and width of the source event bar
- `bx` = x position of the target event bar
- `ay`, `by` = y position of each row (from row index × row height)

---

## Zoom Levels

| Zoom | Column unit | Column width | Typical window |
|---|---|---|---|
| Day | 1 day | 40px | 30–60 days |
| Week | 1 week | 80px | 12–24 weeks |
| Month | 1 month | 120px | 12–18 months |
| Quarter | 1 quarter | 200px | 8–12 quarters (2–3 years) |

Switching zoom re-calculates all bar positions via `dateToX()` — no data re-fetch needed.

---

## Data Requirements

Roadmap requires `start_at` and `end_at` to be set. Items without dates:
- Are listed in a "No dates" section at the bottom of the sidebar
- Do not render a bar in the timeline
- Show an "Add dates" prompt on hover

---

## Phase 4 Deferred Features

These are noted but explicitly not in Phase 4 scope:

- **Milestone marker events** rendered as diamond shapes
- **Critical path highlighting** (longest chain of blocking dependencies)
- **Resource view** (grouped by person — Phase 6 when participants are wired)
- **Export to image** (PNG/SVG snapshot of current viewport)
- **Canvas renderer** (only if HTML performance is insufficient)
