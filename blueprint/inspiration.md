# Inspiration

A living reference of sites, libraries, and designs that inspire the look, feel, and UX of Limbo. Updated as we discover new references.

---

## UI Components & Design Systems

| Resource | URL | What we like |
|---|---|---|
| shadcn/ui | https://www.shadcn.io/ | Component design patterns, spacing, typography, colour system, interactive states — reference for building our own Planner components |
| shadcn-vue | https://www.shadcn-vue.com/ | Vue port of shadcn/ui; the design token and primitive foundation already in use in this project |
| reka-ui | https://reka-ui.com/ | Headless, unstyled Vue primitives (Popover, Dialog, Tabs, Switch, etc.) — used for ARIA/keyboard behaviour layer under our custom components |
| Lucide Icons | https://lucide.dev/ | Icon library used throughout the app |

---

## Calendar & Planning Tools

| Resource | URL | What we like |
|---|---|---|
| FullCalendar (pricing reference) | https://fullcalendar.io/pricing | Pricing page benchmarked for our own Planner tier design; also studied as a reference for calendar UX patterns (month/week/day views, drag-to-create, event overflow) |
| FullCalendar + shadcn-vue example | https://github.com/Lipdk/shadcn-vue-ui-fullcalendar-example | Integration example studied during evaluation; ultimately building our own calendar from scratch but useful for understanding layout patterns |

---

## Planner Views Inspiration

| Resource | URL | What we like |
|---|---|---|
| GitHub Projects | https://github.com/features/issues | Primary reference for Table/Board/Roadmap views, custom fields, view persistence, and real-time multi-user sync. Architecture studied in depth — see `blueprint/planner-views.md` |
| Notion | https://www.notion.so | Database views (table, board, calendar, gallery, timeline), custom properties, inline editing patterns, filter/sort UI |
| Linear | https://linear.app | Cycle/iteration concept, keyboard-first navigation, command palette, compact card density, status + priority visual language |
| Airtable | https://www.airtable.com | Field type variety (formula, rollup, lookup, attachment), view configuration persistence, grid view inline editing |

---

## Logic & Utility Libraries

| Resource | URL | What we like |
|---|---|---|
| date-fns | https://date-fns.org/ | Datetime math, formatting, DST handling, week/month grid generation — used as the logic backbone for our custom calendar and date picker components |
| colord | https://github.com/omgovich/colord | Lightweight hex color parsing and validation — used in our custom color picker component |
| @vueuse/core | https://vueuse.org/ | Composable utilities — `useDraggable`, `useResizeObserver`, `useElementBounding` used for drag/drop and layout in Planner views |

---

## Planner Views Libraries

| Resource | URL | Role |
|---|---|---|
| TanStack Table (Vue) | https://tanstack.com/table/latest | Headless table engine for the Table view — column management, multi-sort, grouping, row selection, column resizing |
| vue-virtual-scroller | https://github.com/Akryum/vue-virtual-scroller | Row virtualization for TanStack Table — renders only visible rows, handles 10k+ items smoothly |
| @dnd-kit (Vue port) | https://dndkit.com / community Vue adapter | Drag-and-drop for Board view cards and Roadmap bars — composable API, keyboard accessible, collision detection |
| Pinia | https://pinia.vuejs.org/ | Planner state store — items, field schema, filters, sorts, view config, optimistic mutation map |
| laravel-echo | https://github.com/laravel/echo | WebSocket listener — subscribes to private planner channel for real-time item/field value updates |
| Lighthouse (PHP) | https://lighthouse-php.com/ | Laravel GraphQL server — schema-first SDL, Eloquent directives, subscription support |

---

## General UX & Interaction

*(Add as we research)*

---

## Typography & Colour

*(Add as we research)*

