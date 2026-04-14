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
| FullCalendar (pricing reference) | https://fullcalendar.io/pricing | Pricing page benchmarked for our own Life Planner tier design; also studied as a reference for calendar UX patterns (month/week/day views, drag-to-create, event overflow) |
| FullCalendar + shadcn-vue example | https://github.com/Lipdk/shadcn-vue-ui-fullcalendar-example | Integration example studied during evaluation; ultimately building our own calendar from scratch but useful for understanding layout patterns |

---

## Logic & Utility Libraries

| Resource | URL | What we like |
|---|---|---|
| date-fns | https://date-fns.org/ | Datetime math, formatting, DST handling, week/month grid generation — used as the logic backbone for our custom calendar and date picker components |
| colord | https://github.com/omgovich/colord | Lightweight hex color parsing and validation — used in our custom color picker component |
| @vueuse/core | https://vueuse.org/ | Composable utilities — `useDraggable`, `useResizeObserver`, `useElementBounding` used for drag/drop and layout in Planner views |

---

## General UX & Interaction

*(Add as we research)*

---

## Typography & Colour

*(Add as we research)*

