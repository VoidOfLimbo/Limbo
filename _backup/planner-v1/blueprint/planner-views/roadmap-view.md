# Planner Views — Roadmap View

**Depends on:** [`blueprint/planner-views/component-tree.md`](./component-tree.md), [`blueprint/planner-views/data-model.md`](./data-model.md)
**Status:** Complete ✅ — implemented in Phase 4

---

## Overview

The Roadmap View renders milestones and events as **horizontal timeline bars** — similar to GitHub Projects' Roadmap view or a simplified Gantt chart. It gives users a bird's-eye view of how their life plan is distributed across time.

Unlike a formal Gantt chart, the Roadmap is focused on **visual clarity** over precise project management. Best for answering: *"What's happening in the next 3 months?"*

---

## Key Features

| Feature | Status | Description |
|---|---|---|
| Milestone bars | ✅ | Full-width bars spanning `start_at` → `end_at`, coloured by milestone `color` or `--primary` |
| Event bars | ✅ | Per-event bars, status-coloured by default (user `color` field overrides) |
| Event status colors | ✅ | draft/skipped=gray, upcoming=blue, in_progress=violet, completed=green, cancelled=gray |
| Zoom levels | ✅ | Week / Month / Quarter / Year — dropdown picker, persisted to `localStorage` |
| Drag to reschedule | ✅ | Pointer events (move entire bar) → emits `reschedule` → parent handles server call |
| Resize handles | ✅ | Left/right edge handles — drag to extend/shorten duration |
| Hard-deadline lock | ✅ | Right handle hidden for hard-deadline milestones with a set `end_at` |
| Today indicator | ✅ | Vertical accent line + dot at today's date |
| Scroll sync | ✅ | Sidebar (item titles) + timeline (bars) scroll vertically in sync |
| Auto-scroll to today | ✅ | On load, viewport horizontally centers on today |
| Pre-expanded milestones | ✅ | All milestones open with events visible by default |
| Milestone scope filter | ✅ | Inside a milestone: only that milestone + its events shown |
| Optimistic updates | ✅ | Bars reposition instantly; server response merges data back in-place |
| Dependency arrows | ❌ Deferred | SVG curved lines (Phase 5+) |
| Iteration bands | ❌ Deferred | Background highlighting for `planner_iterations` date ranges |

---

## Rendering Strategy

**HTML divs + CSS transforms** for bars (implemented). Switching zoom re-calculates all positions via `dateToX()` — no data re-fetch. Row virtualization with `RecycleScroller` deferred until 500+ row performance testing.

---

## Layout Structure

```
PlannerRoadmapView                   (root — provides ROADMAP_LAYOUT_KEY via Vue inject)
├── PlannerRoadmapToolbar
│   ├── ZoomControl          day | week | month | quarter
│   ├── TodayButton          → scroll to today
│   └── ShowDependencies     toggle dependency arrows
│
└── PlannerRoadmapCanvas     [flex row, fills remaining height]
    │
    ├── PlannerRoadmapSidebar  [fixed-width left panel, 260px]
    │   └── rows × N
    │       ├── milestone row: expand toggle, status dot, title, lock/breach icons
    │       └── event row: indented, status dot, title
    │
    └── timelineScrollEl [flex-1, overflow-auto]
        └── inner div (width = totalWidth)
            ├── PlannerTimelineHeader    [sticky top, 52px]
            │   ├── Major row (24px): months or years depending on zoom
            │   └── Minor row (28px): days / weeks (W1–W52) / months / quarters
            │
            ├── PlannerTimelineGrid     [absolute, pointer-events-none]
            │   ├── Today vertical line + dot
            │   ├── Weekend shading (day/week zoom only)
            │   └── Column/row dividers
            │
            └── PlannerTimelineBar × N  [absolute per row]
                ├── bar body (draggable, coloured by status or explicit color)
                ├── left resize handle
                └── right resize handle (hidden for hard-deadline milestones)
```

---

## Coordinate System

All bar positions are provided by `useRoadmapLayout` composable and injected via `ROADMAP_LAYOUT_KEY`:

```typescript
// resources/js/composables/planner/useRoadmapLayout.ts

export type ZoomLevel = 'day' | 'week' | 'month' | 'quarter' | 'year'

export const COLUMN_WIDTH: Record<ZoomLevel, number> = {
    day: 40, week: 80, month: 120, quarter: 200, year: 320,
}

const DAYS_PER_UNIT: Record<ZoomLevel, number> = {
    day: 1, week: 7, month: 30.4375, quarter: 91.3125, year: 365.25,
}

interface RoadmapLayoutContext {
    columnWidth: ComputedRef<number>
    totalWidth: ComputedRef<number>
    viewStart: ComputedRef<Date>     // earliestItemDate - 3 units (±30 days fallback when no items)
    viewEnd: ComputedRef<Date>       // latestItemDate + 3 units
    zoom: Ref<ZoomLevel>
    dateToX: (date: Date | string) => number
    xToDate: (x: number) => Date
    widthFromDuration: (start, end) => number   // min width = columnWidth / 4
    pxPerDay: ComputedRef<number>
}
```

`allItems` fed to `useRoadmapLayout` is scoped to `visibleMilestones` + `localEvents`, so the viewport is always sized around what's actually displayed.

---

## Bar Drag + Resize

Uses native pointer events (no useDraggable / dnd-kit):

```typescript
// PlannerTimelineBar.vue
function startInteraction(e: PointerEvent, mode: 'move' | 'resize-left' | 'resize-right') {
    e.preventDefault()
    isDragging.value = true
    dragStartX.value = e.clientX
    document.addEventListener('pointermove', onPointerMove)
    document.addEventListener('pointerup', onPointerUp, { once: true })
}

// onPointerUp: deltaDays = Math.round(dragOffsetX / pxPerDay)
// emit reschedule(id, kind, newStart.toISOString().slice(0,10), newEnd.toISOString().slice(0,10))
```

Visual feedback during drag uses CSS `transform: translateX()` and width delta — no re-render needed.

### Hard-Deadline Milestone Protection

```typescript
const isRightResizeLocked = computed(() =>
    props.kind === 'milestone'
    && (props.item as PlannerMilestone).deadline_type === 'hard'
    && props.item.end_at !== null,
)
```

Right handle uses `v-if="!isRightResizeLocked"`. Parent also omits `end_at` from the reschedule payload, and the optimistic update preserves the original `end_at`.

---

## Zoom Levels

| Zoom | Column unit | Column width | `pxPerDay` | Typical window |
|---|---|---|---|---|
| Week | 1 week | 80px | ~11.4px | 6–12 months |
| Month | 1 month | 120px | ~3.9px | 12–18 months |
| Quarter | 1 quarter | 200px | ~2.2px | 2–3 years |
| Year | 1 year | 320px | ~0.88px | 5–8 years |

Preference stored in `localStorage` under `planner:roadmapZoom`. Switching zoom re-calculates all positions via `dateToX()` — no data re-fetch.

### Timeline Header Labels

| Zoom | Major row | Minor row |
|---|---|---|
| Week | Month + year (e.g. "Apr 2026") | ISO week numbers (W1–W52) |
| Month | Year (e.g. "2026") | Month abbreviations (Jan, Feb…) |
| Quarter | Year (e.g. "2026") | Q1, Q2, Q3, Q4 |
| Year | Year (e.g. "2026") | Q1, Q2, Q3, Q4 |

---

## Data Requirements

Roadmap requires `start_at` and `end_at` to be set on an item for a bar to render. Items without dates show a placeholder at today's position but no interactive bar.

---

## Deferred Features

| Feature | Notes |
|---|---|
| **Dependency arrows** | SVG cubic bezier overlay connecting finish→start relationships |
| **Iteration bands** | Background highlighting for `planner_iterations` date ranges |
| **Row virtualization** | `RecycleScroller` — only needed at 500+ rows |
| **Milestone marker diamonds** | Point-in-time events rendered as diamond shapes |
| **Critical path highlighting** | Longest chain of blocking dependencies |
| **Canvas renderer** | Migration from HTML divs — only if HTML performance is insufficient |
