# App State — VoidOfLimbo

Current snapshot of what exists in the codebase. Updated manually as features are built.

> **Naming note:** The planner feature was previously called **"Life Planner"** / **"life-planner"**. All references now use **"Planner"**. If you see "life planner" anywhere, it refers to this same feature.

---

## Blueprint Documents

| Document | Purpose |
|---|---|
| [`blueprint/planner.md`](./planner.md) | Core data model, views overview, milestones, events, tags |
| [`blueprint/planner/phase-1.md`](./planner/phase-1.md) | Phase 1 spec — migrations, models, controllers, list view |
| [`blueprint/planner/phase-2.md`](./planner/phase-2.md) | Phase 2 spec — Table view + Board view |
| [`blueprint/planner/phase-3.md`](./planner/phase-3.md) | Phase 3 spec — Custom Fields + Named Views (REST) |
| [`blueprint/planner/phase-4.md`](./planner/phase-4.md) | Phase 4 spec — Roadmap / Timeline view |
| [`blueprint/planner-views.md`](./planner-views.md) | **GitHub Projects-style views** — master doc, architecture, phase breakdown |
| [`blueprint/planner-views/component-tree.md`](./planner-views/component-tree.md) | Full Vue component hierarchy for all views |
| [`blueprint/planner-views/data-model.md`](./planner-views/data-model.md) | Custom fields + view config tables (`planner_fields`, `planner_field_values`, `planner_views`) |
| [`blueprint/planner-views/graphql-schema.md`](./planner-views/graphql-schema.md) | GraphQL schema (reference doc — deferred) |
| [`blueprint/planner-views/realtime-sync.md`](./planner-views/realtime-sync.md) | WebSocket strategy (deferred to future phase) |
| [`blueprint/planner-views/table-view.md`](./planner-views/table-view.md) | TanStack Table + Vue Virtual Scroller implementation guide |
| [`blueprint/planner-views/board-view.md`](./planner-views/board-view.md) | dnd-kit Kanban board implementation guide |
| [`blueprint/planner-views/roadmap-view.md`](./planner-views/roadmap-view.md) | Roadmap/Timeline view — scoped for Phase 4 |
| [`blueprint/inspiration.md`](./inspiration.md) | UI references, libraries, design inspiration |

---

## Feature Progress

### Planner — Phase 1

| Area | Status |
|---|---|
| Migrations (8 tables) | ✅ Done |
| Enums (10) | ✅ Done |
| Models (7) | ✅ Done |
| Factories (MilestoneFactory, EventFactory, TagFactory) | ✅ Done |
| Policies (Milestone, Event, Tag) | ✅ Done |
| Form Requests (8) | ✅ Done |
| Controllers (PlannerController, MilestoneController, EventController, TagController, PlannerExportController) | ✅ Done |
| Routes + Wayfinder generated | ✅ Done |
| Pest tests — 70 passed (193 assertions) | ✅ Done |
| Seeder (`PlannerSeeder` — all states, edge cases) | ✅ Done |
| `Planner/Index.vue` page | ✅ Done |
| `PlannerMilestoneTabs` | ✅ Done |
| `PlannerMilestoneSelector` (groupBy: quarter/status/priority, collapsible groups, arc progress) | ✅ Done |
| `PlannerMilestoneHeader` | ✅ Done |
| `PlannerMilestoneExplorer` (bottom drawer — browse/search all milestones) | ✅ Done |
| `PlannerFilters` (redesigned — color-coded pills, active chip bar, mobile-responsive) | ✅ Done |
| `PlannerEventList` (VueDraggable drag-to-reorder) | ✅ Done |
| `PlannerEventRow` (drag handle, group context menu) | ✅ Done |
| `PlannerChildRow` (collapsible nested event row) | ✅ Done |
| `PlannerEventDrawer` (responsive grid layout, vaul drawer, tags) | ✅ Done |
| `PlannerMilestoneDrawer` (responsive grid layout, vaul drawer, tags) | ✅ Done |
| `PlannerSnoozePopover` | ✅ Done |
| `PlannerContextMenu` | ✅ Done |
| `PlannerBadge` | ✅ Done |
| `PlannerTagInput` + tags in drawers | ✅ Done |
| `PlannerEmptyState` | ✅ Done |
| Snooze toast confirmation | ✅ Done |
| Event `sort_order` — drag-to-reorder in list view (`POST /events/reorder`) | ✅ Done |

---

### Planner — Phase 2 (Table View + Board View)

> **Status: Complete ✅**
> See [`blueprint/planner/phase-2.md`](./planner/phase-2.md), [`blueprint/planner-views/table-view.md`](./planner-views/table-view.md), [`blueprint/planner-views/board-view.md`](./planner-views/board-view.md)

| Area | Status |
|---|---|
| Install `@tanstack/vue-table` | ✅ Done |
| Install `vue-draggable-plus` (SortableJS-based; chosen over @dnd-kit/vue which is immature) | ✅ Done |
| Install `vue-sonner` (toast notifications) | ✅ Done |
| Install `pinia` | ✅ Done |
| `resources/js/stores/planner.ts` — Pinia store, `activeView` persisted to localStorage | ✅ Done |
| `PlannerViewSwitcher` (List / Table / Board) | ✅ Done |
| `PlannerTableView` — TanStack Table (FlexRender, sorting, column resize) | ✅ Done |
| `PlannerFieldCell` — inline editing for title field | ✅ Done |
| `PlannerBoardView` — vue-draggable-plus columns + cards | ✅ Done |
| `PlannerBoardColumn` — droppable zone per status | ✅ Done |
| `PlannerBoardCard` — draggable; priority/type badges, date range, tags | ✅ Done |
| `PlannerBoardAddCard` — inline title input at column bottom | ✅ Done |
| `PlannerBulkActionBar` — bulk delete, clear selection | ✅ Done |
| View type persisted to `localStorage` via Pinia store | ✅ Done |
| `PlannerMilestoneSelector` — groupBy compact bar replacing old tab strip | ✅ Done |
| `PlannerMilestoneExplorer` — bottom vaul-vue drawer, browse + select milestones | ✅ Done |
| `PlannerChildRow` — collapsible nested event row with chevron toggle | ✅ Done |

---

### Planner — Phase 3 (Custom Fields + Named Views — REST)

> **Status: Complete ✅**
> See [`blueprint/planner/phase-3.md`](./planner/phase-3.md), [`blueprint/planner-views/data-model.md`](./planner-views/data-model.md)
> Architecture: REST (GraphQL/Lighthouse deferred; no new npm/composer packages needed)

| Area | Status |
|---|---|
| Migration: `planner_fields` | ✅ Done |
| Migration: `planner_field_values` | ✅ Done |
| Migration: `planner_views` | ✅ Done |
| Model: `PlannerField` | ✅ Done |
| Model: `PlannerFieldValue` | ✅ Done |
| Model: `PlannerView` | ✅ Done |
| `PlannerSystemFieldsSeeder` (7 system fields per user) | ✅ Done |
| Add `fieldValues` / `fields` / `plannerViews` relations to `Event` + `Milestone` | ✅ Done |
| `PlannerFieldController` (index, store, update, destroy, storeOption, destroyOption) | ✅ Done |
| `PlannerFieldValueController` (upsert, destroy) | ✅ Done |
| `PlannerViewController` (index, store, update, destroy, activate) | ✅ Done |
| Form requests for field + view CRUD | ✅ Done |
| Policies: `PlannerFieldPolicy`, `PlannerViewPolicy` | ✅ Done |
| Register routes in `routes/planner.php` | ✅ Done |
| Run `sail artisan wayfinder:generate --with-form` | ✅ Done |
| `PlannerController` — add deferred `fields` + `savedViews` Inertia props | ✅ Done |
| TS types: `PlannerField`, `PlannerFieldValue`, `PlannerView` | ✅ Done |
| `usePlannerStore` — rename `PlannerView` → `PlannerViewMode` to avoid type collision | ✅ Done |
| `PlannerViewTabs` component (tab strip + create button) | ✅ Done |
| `PlannerFieldManager` component (drawer) | ✅ Done |
| Custom field columns in `PlannerTableView` | ✅ Done |
| Custom field group-by in `PlannerBoardView` | ✅ Done |
| Pest tests — field CRUD, field values, view persistence, policy, controller | ✅ Done (99 tests, 262 assertions) |

---

### Planner — Pagination (List + Table Views)

> **Status: Complete ✅**

**Design decisions:**
- `per_page` stored in `localStorage`, sent as `X-Planner-Per-Page` header — never in URL
- `view` (list/table/board) stored in `localStorage` + Pinia, sent as `X-Planner-View` header — never in URL
- **List view**: manual "Load more" button (appends pages client-side)
- **Table view**: traditional paginator footer (prev / next, page X of Y)
- **Board view**: all events fetched unpaginated — per_page not applied
- `plannerVisit()` helper wraps all `router.visit` calls with preference headers
- `groupBy` preference moved to Pinia store + localStorage (shared across dashboard + selector)

| Area | Status |
|---|---|
| Backend: `per_page` + `view` from request headers (not query params) | ✅ Done |
| Board view returns all events (no pagination) | ✅ Done |
| `plannerVisit()` helper injects headers on every navigation | ✅ Done |
| Per-page selector visible for list + table, hidden concept for board | ✅ Done |
| `PlannerTableView` — pagination footer | ✅ Done |
| `PlannerEventList` — Load more + multi-column layout (1–4 cols, capped by container width) | ✅ Done |

---

### Planner — Phase 3.5 (Milestone Dashboard + UI Polish)

> **Status: Complete ✅** — added in this session, not in original blueprint

| Area | Status |
|---|---|
| `PlannerMilestoneDashboard` — grid/list of all milestones with search, group-by tabs, sort, filter | ✅ Done |
| `PlannerMilestoneCard` — progress bar, status/priority badges, breach + hard deadline icons with tooltips | ✅ Done |
| Dashboard grid view: `auto-fill minmax(300px, 1fr)` responsive layout | ✅ Done |
| Dashboard list view: multi-column picker (1–4 cols, capped by container width via `useElementSize`) | ✅ Done |
| `groupBy` in Pinia store + localStorage — shared between dashboard and milestone selector | ✅ Done |

---

### Planner — Phase 4 (Roadmap View)

> **Status: Complete ✅**
> See [`blueprint/planner/phase-4.md`](./planner/phase-4.md), [`blueprint/planner-views/roadmap-view.md`](./planner-views/roadmap-view.md)

| Area | Status |
|---|---|
| Migration: `planner_iterations` | ✅ Done |
| Model: `PlannerIteration` (HasUlids, date casts, `field()` BelongsTo) | ✅ Done |
| `PlannerField hasMany PlannerIteration` relationship | ✅ Done |
| `PlannerController` — roadmap as all-events (no pagination), deferred prop | ✅ Done |
| Roadmap milestone scope: dashboard = all events, milestone page = scoped | ✅ Done |
| `useRoadmapLayout` composable — dateToX, xToDate, widthFromDuration, pxPerDay | ✅ Done |
| `ROADMAP_LAYOUT_KEY` injection key (provided by `PlannerRoadmapView`) | ✅ Done |
| `ZoomLevel`: `week` \| `month` \| `quarter` \| `year` (day available but not in UI) | ✅ Done |
| `PlannerRoadmapView` — local stable copies, optimistic updates, scroll sync, auto-scroll to today | ✅ Done |
| `PlannerRoadmapToolbar` — zoom dropdown (Week/Month/Quarter/Year), Today button, Dependencies toggle | ✅ Done |
| `PlannerRoadmapSidebar` — expandable milestone/event rows, status dot, lock/breach icons | ✅ Done |
| `PlannerTimelineHeader` — sticky 52px, major/minor rows, today highlight, weekend dim | ✅ Done |
| `PlannerTimelineGrid` — column/row dividers, weekend shading, today line + dot | ✅ Done |
| `PlannerTimelineBar` — pointer-event drag/resize, status-based event colors, hard-deadline lock | ✅ Done |
| `PlannerViewSwitcher` — Roadmap button (GanttChartSquare icon) | ✅ Done |
| `Index.vue` — `handleRoadmapReschedule`, `ALL_EVENTS_VIEWS` watcher, roadmap template | ✅ Done |
| Stable sort on prop updates (merge-by-ID watchers, no row jumping) | ✅ Done |
| Milestone scope filter (`visibleMilestones` — only active milestone shown per context) | ✅ Done |
| Stale events cleared on milestone navigation (`activeMilestoneId` watcher) | ✅ Done |
| All milestones pre-expanded on open | ✅ Done |
| Hard-deadline milestone `end_at` protected (right resize handle hidden, payload omits `end_at`) | ✅ Done |
| Zoom preference persisted to `localStorage` (`planner:roadmapZoom`) | ✅ Done |
| Dependency arrow SVG overlay | ❌ Deferred |
| Iteration bands background highlighting | ❌ Deferred |

---

## Tech Stack

| Layer | Package | Version |
|---|---|---|
| Runtime | PHP | 8.4 |
| Framework | Laravel | 13 |
| SPA bridge | Inertia.js (Laravel + Vue) | v3 |
| Frontend | Vue 3 | 3.x |
| Styling | Tailwind CSS | v4 |
| UI components | shadcn-vue (new-york-v4) + reka-ui | v2.6.1 |
| Drawers | vaul-vue | — |
| State management | Pinia | — |
| Drag-and-drop | vue-draggable-plus (SortableJS-based) | v0.6.1 |
| Data tables | @tanstack/vue-table | v8.21.3 |
| Toasts | vue-sonner | — |
| Icons | lucide-vue-next | — |
| Auth backend | Laravel Fortify | v1 |
| Social auth | Laravel Socialite | v5 |
| Typed routes | Laravel Wayfinder | v0 (always use `--with-form`) |
| Testing | Pest | v4 |
| Dev server | Laravel Sail | v1 |
| Code style | Laravel Pint | v1 |

---

## Database Schema

### `users`
| Column | Type | Notes |
|---|---|---|
| `id` | ULID (PK) | |
| `username` | string | unique |
| `name` | string | display name |
| `email` | string | unique |
| `email_verified_at` | timestamp | nullable |
| `password` | string | nullable — OAuth-only users have no password |
| `bio` | string(500) | nullable |
| `tier` | string | `free` / `premium` / `loyalist` (default: `free`) |
| `profile_image` | string | nullable |
| `profile_image_fallback` | string | nullable |
| `cover_image` | string | nullable |
| `cover_image_fallback` | string | nullable |
| `remember_token` | string | nullable |
| `two_factor_secret` | text | nullable (Fortify 2FA) |
| `two_factor_recovery_codes` | text | nullable (Fortify 2FA) |
| `two_factor_confirmed_at` | timestamp | nullable (Fortify 2FA) |
| `created_at` / `updated_at` | timestamps | |

### `social_accounts`
| Column | Type | Notes |
|---|---|---|
| `id` | ULID (PK) | |
| `user_id` | string(26) FK → `users.id` | cascade delete |
| `provider` | string | e.g. `google`, `microsoft` |
| `provider_id` | string | provider's user ID |
| `provider_token` | text | nullable |
| `provider_refresh_token` | text | nullable |
| `created_at` / `updated_at` | timestamps | |
| — | unique | `(provider, provider_id)` |

### Standard Laravel tables
`password_reset_tokens`, `sessions`, `cache`, `jobs`

### Planner tables

#### `milestones`
| Column | Type | Notes |
|---|---|---|
| `id` | ULID (PK) | |
| `user_id` | ULID FK → `users.id` | cascade delete |
| `title` | string | |
| `description` | text | nullable |
| `status` | string | `active` / `completed` / `paused` / `cancelled` |
| `priority` | string | `low` / `medium` / `high` / `critical` |
| `start_at` | timestamp | nullable |
| `end_at` | timestamp | nullable |
| `duration_source` | string | `derived` / `manual` |
| `deadline_type` | string | `soft` / `hard` |
| `progress_source` | string | `derived` / `manual` |
| `progress_override` | smallint | nullable, 0–100 |
| `visibility` | string | `private` / `shared` |
| `color` | string(7) | nullable, hex e.g. `#3b82f6` |
| `created_at` / `updated_at` | timestamps | |
| `deleted_at` | timestamp | soft delete |

#### `events`
| Column | Type | Notes |
|---|---|---|
| `id` | ULID (PK) | |
| `user_id` | ULID FK → `users.id` | cascade delete |
| `milestone_id` | ULID FK → `milestones.id` | nullable, null on delete |
| `parent_event_id` | ULID FK → `events.id` | nullable, null on delete |
| `title` | string | |
| `description` | text | nullable |
| `type` | string | `event` / `task` / `milestone_marker` |
| `status` | string | `draft` / `upcoming` / `in_progress` / `completed` / `cancelled` / `skipped` |
| `priority` | string | `low` / `medium` / `high` / `critical` |
| `start_at` | timestamp | nullable |
| `end_at` | timestamp | nullable |
| `is_all_day` | boolean | default false |
| `is_milestone_marker` | boolean | default false |
| `recurrence_rule` | json | nullable |
| `recurrence_ends_at` | timestamp | nullable |
| `recurrence_count` | unsignedInteger | nullable |
| `visibility` | string | `private` / `shared` |
| `color` | string(7) | nullable |
| `location` | string | nullable |
| `snoozed_until` | timestamp | nullable |
| `snooze_count` | smallint | default 0 |
| `sort_order` | unsignedInteger | nullable — manual list-view sort order (NULLS LAST, then `start_at`) |
| `created_at` / `updated_at` | timestamps | |
| `deleted_at` | timestamp | soft delete |

#### `tags`
| Column | Type | Notes |
|---|---|---|
| `id` | ULID (PK) | |
| `user_id` | ULID FK → `users.id` | cascade delete |
| `name` | string | unique per user |
| `color` | string(7) | nullable |
| `created_at` / `updated_at` | timestamps | |

#### `taggables` (polymorphic pivot)
| Column | Type | Notes |
|---|---|---|
| `tag_id` | ULID FK → `tags.id` | |
| `taggable_id` | ULID | morphs |
| `taggable_type` | string | morphs |

#### `event_reminders` (table only — delivery wired Phase 4)
`id`, `event_id`, `offset_minutes`, `channels` (json), `sent_at`

#### `event_occurrences` (table only — populated Phase 3)
`id`, `event_id`, `start_at`, `end_at`, `status`, `overrides` (json)

#### `event_participants` (table only — UI wired Phase 6)
`id`, `event_id`, `user_id`, `invited_by`, `role`, `status`, `responded_at`

#### `event_dependencies` (table only — UI wired Phase 5)
`id`, `event_id`, `depends_on_event_id`, `type` (`blocking` / `informational`)

---

## Models

### `User`
- Traits: `HasUlids`, `HasFactory`, `Notifiable`, `TwoFactorAuthenticatable`
- Implements: `MustVerifyEmail`
- Fillable: `username`, `name`, `email`, `password`, `bio`, `tier`, `profile_image`, `profile_image_fallback`, `cover_image`, `cover_image_fallback`
- Hidden: `password`, `two_factor_secret`, `two_factor_recovery_codes`, `remember_token`
- Casts: `email_verified_at` → datetime, `password` → hashed, `two_factor_confirmed_at` → datetime, `tier` → `UserTier`
- Relations: `hasMany(SocialAccount::class)`

### `SocialAccount`
- Traits: `HasUlids`, `HasFactory`
- Fillable: `user_id`, `provider`, `provider_id`, `provider_token`, `provider_refresh_token`
- Relations: `belongsTo(User::class)`

### Planner Models

> **Alias note:** "Life Planner" is now called **Planner**. These models belong to the Planner feature.

#### `Milestone`
- Traits: `HasUlids`, `HasFactory`, `SoftDeletes`
- Fillable: `user_id`, `title`, `description`, `status`, `priority`, `start_at`, `end_at`, `duration_source`, `deadline_type`, `progress_source`, `progress_override`, `visibility`, `color`
- Casts: `start_at` → datetime, `end_at` → datetime, `status` → `MilestoneStatus`, `priority` → `MilestonePriority`, `deadline_type` → `DeadlineType`, `duration_source` → `DurationSource`, `progress_source` → `ProgressSource`
- Relations: `belongsTo(User)`, `hasMany(Event)`, `morphToMany(Tag, 'taggable')`
- Computed: `getDerivedProgressAttribute()`, `getProgressAttribute()`, `isBreached()`, `derivedStartAt()`, `derivedEndAt()`

#### `Event`
- Traits: `HasUlids`, `HasFactory`, `SoftDeletes`
- Fillable: full field list from schema
- Casts: `start_at/end_at/snoozed_until` → datetime, `is_all_day/is_milestone_marker` → boolean, `recurrence_rule` → array, `status` → `EventStatus`, `type` → `EventType`, `priority` → `EventPriority`, `visibility` → `EventVisibility`
- Relations: `belongsTo(User)`, `belongsTo(Milestone)`, `belongsTo(Event, 'parent_event_id')` as `parent`, `hasMany(Event, 'parent_event_id')` as `children`, `morphToMany(Tag, 'taggable')`, `hasMany(EventReminder)`, `hasMany(EventDependency)`, `hasMany(EventParticipant)`
- Scopes: `scopeActive()`, `scopeSnoozed()`, `scopeForMilestone()`, `scopeBacklog()`
- Methods: `isSnoozed()`, `isBreachingMilestone()`

#### `Tag`
- Traits: `HasUlids`, `HasFactory`
- Fillable: `user_id`, `name`, `color`
- Relations: `morphedByMany(Event)`, `morphedByMany(Milestone)`

#### Stub models (tables + FK relations only)
`EventReminder`, `EventOccurrence`, `EventParticipant`, `EventDependency`

## Enums

### `UserTier: string`
| Case | Value |
|---|---|
| `Free` | `'free'` |
| `Premium` | `'premium'` |
| `Loyalist` | `'loyalist'` |

### Planner Enums

| Enum | Values |
|---|---|
| `EventStatus` | `draft` / `upcoming` / `in_progress` / `completed` / `cancelled` / `skipped` |
| `EventType` | `event` / `task` / `milestone_marker` |
| `EventPriority` | `low` / `medium` / `high` / `critical` |
| `EventVisibility` | `private` / `shared` |
| `MilestoneStatus` | `active` / `completed` / `paused` / `cancelled` |
| `MilestonePriority` | `low` / `medium` / `high` / `critical` |
| `DeadlineType` | `soft` / `hard` |
| `DurationSource` | `derived` / `manual` |
| `ProgressSource` | `derived` / `manual` |
| `DependencyType` | `blocking` / `informational` |

---

## Authentication

Powered by **Fortify** (backend) + **Socialite** (OAuth).

- Login identifier: `email`
- Post-auth redirect: `/dashboard`
- Usernames are lowercased before save

**Fortify features enabled:** registration, password reset, email verification, 2FA (TOTP)

**OAuth providers configured:** Google, Microsoft

**Fortify Actions** (`app/Actions/Fortify/`): CreateNewUser, UpdateUserProfileInformation, UpdateUserPassword, ResetUserPassword

---

## Routes

### Public
| Method | Path | Page / Handler |
|---|---|---|
| GET | `/` | `Welcome` |
| GET | `/privacy-policy` | `Legal/PrivacyPolicy` |
| GET | `/invite` | `Invite` |

### Auth-only (`auth` middleware)
| Method | Path | Handler |
|---|---|---|
| GET | `/settings` | redirect → `/settings/profile` |
| GET | `/settings/profile` | `ProfileController@edit` |
| PATCH | `/settings/profile` | `ProfileController@update` |

### Auth + verified (`auth`, `verified`)
| Method | Path | Handler |
|---|---|---|
| GET | `/dashboard` | `Dashboard` |
| DELETE | `/settings/profile` | `ProfileController@destroy` |
| GET | `/settings/security` | `SecurityController@edit` |
| PUT | `/settings/password` | `SecurityController@update` (throttle 6/min) |
| GET | `/settings/appearance` | `settings/Appearance` |
| GET | `/planner` | `Planner\PlannerController@index` (named: `planner`) |
| GET | `/planner/export/ics` | `Planner\PlannerExportController` (named: `planner.export.ics`) |
| GET | `/planner/export/ics/event/{event}` | `Planner\PlannerExportController` (named: `planner.export.ics.event`) |
| GET | `/planner/export/ics/milestone/{milestone}` | `Planner\PlannerExportController` (named: `planner.export.ics.milestone`) |
| GET | `/events` | `Planner\EventController@index` (named: `events.index`) |
| POST | `/events` | `Planner\EventController@store` (named: `events.store`) |
| POST | `/events/reorder` | `Planner\EventController@reorder` (named: `events.reorder`) |
| GET | `/events/{event}` | `Planner\EventController@show` (named: `events.show`) |
| PUT | `/events/{event}` | `Planner\EventController@update` (named: `events.update`) |
| DELETE | `/events/{event}` | `Planner\EventController@destroy` (named: `events.destroy`) |
| POST | `/events/{event}/snooze` | `Planner\EventController@snooze` (named: `events.snooze`) |
| GET | `/milestones` | `Planner\MilestoneController@index` (named: `milestones.index`) |
| POST | `/milestones` | `Planner\MilestoneController@store` (named: `milestones.store`) |
| GET | `/milestones/{milestone}` | `Planner\MilestoneController@show` (named: `milestones.show`) |
| PUT | `/milestones/{milestone}` | `Planner\MilestoneController@update` (named: `milestones.update`) |
| DELETE | `/milestones/{milestone}` | `Planner\MilestoneController@destroy` (named: `milestones.destroy`) |
| GET | `/tags` | `Planner\TagController@index` (named: `tags.index`) |
| POST | `/tags` | `Planner\TagController@store` (named: `tags.store`) |
| PUT | `/tags/{tag}` | `Planner\TagController@update` (named: `tags.update`) |
| DELETE | `/tags/{tag}` | `Planner\TagController@destroy` (named: `tags.destroy`) |
| POST | `/tags/{tag}/attach` | `Planner\TagController@attach` (named: `tags.attach`) |
| DELETE | `/tags/{tag}/detach` | `Planner\TagController@detach` (named: `tags.detach`) |

Fortify registers its own auth routes (login, register, logout, password, 2FA, email verify).

---

## Pages (`resources/js/pages/`)

| Page | Route | Auth? |
|---|---|---|
| `Welcome.vue` | `/` | no |
| `Dashboard.vue` | `/dashboard` | yes + verified |
| `Invite.vue` | `/invite` | no |
| `Legal/PrivacyPolicy.vue` | `/privacy-policy` | no |
| `auth/Login.vue` | `/login` | — |
| `auth/Register.vue` | `/register` | — |
| `auth/ForgotPassword.vue` | `/forgot-password` | — |
| `auth/ResetPassword.vue` | `/reset-password` | — |
| `auth/ConfirmPassword.vue` | `/confirm-password` | — |
| `auth/TwoFactorChallenge.vue` | `/two-factor-challenge` | — |
| `auth/VerifyEmail.vue` | `/verify-email` | — |
| `settings/Profile.vue` | `/settings/profile` | yes |
| `settings/Security.vue` | `/settings/security` | yes + verified |
| `settings/Appearance.vue` | `/settings/appearance` | yes + verified |
| `Planner/Index.vue` | `/planner` | yes + verified |

---

## Components (`resources/js/components/`)

### Animation / visual effects
| Component | Purpose |
|---|---|
| `NeonText.vue` | Per-character CSS @keyframe neon sign flicker. Supports per-char tilt, colour, flicker rate. Props: `text`, `tag`, `color`, `spread`, `tilt[]`, `flicker[]`, `defaultNeonColor`, `defaultMinFlickers`, `defaultMaxFlickers`, `defaultInterval`, `defaultSpeed` |
| `GlitchText.vue` | Glitch distortion effect on text. Exposes `trigger()` ref method |
| `ScrambleText.vue` | Cycles through a list of strings with a character-scramble transition. Emits `@change` |
| `BounceWrapper.vue` | Wraps content with a bounce-in animation |

### Navigation / layout
| Component | Purpose |
|---|---|
| `AppShell.vue` | Root shell wrapper |
| `AppHeader.vue` | Top navigation bar |
| `AppSidebar.vue` | Sidebar navigation |
| `AppSidebarHeader.vue` | Sidebar logo / title area |
| `AppContent.vue` | Main content wrapper |
| `AppLogo.vue` | Full logo (icon + wordmark) |
| `AppLogoIcon.vue` | Icon-only logo |
| `NavMain.vue` | Primary nav link list |
| `NavFooter.vue` | Sidebar bottom links |
| `NavUser.vue` | User avatar + name in nav |
| `Breadcrumbs.vue` | Breadcrumb trail |
| `DynamicFloatingMenu.vue` | Floating dock with position (`top`/`bottom`/`left`/`right`), alignment, collapsible toggle, active item highlight, and `@select` emit |
| `ThemeToggle.vue` | Light / dark / system theme switcher |

### Auth / user
| Component | Purpose |
|---|---|
| `UserInfo.vue` | Displays user avatar + name |
| `UserMenuContent.vue` | Dropdown menu content for logged-in user |
| `DeleteUser.vue` | Account deletion confirmation UI |
| `TwoFactorSetupModal.vue` | QR code + TOTP confirmation modal |
| `TwoFactorRecoveryCodes.vue` | Displays and regenerates 2FA recovery codes |

### UI primitives
| Component | Purpose |
|---|---|
| `AlertError.vue` | Error alert banner |
| `InputError.vue` | Inline field validation message |
| `PasswordInput.vue` | Password field with show/hide toggle |
| `Heading.vue` | Semantic section heading |
| `TextLink.vue` | Styled anchor |
| `AppearanceTabs.vue` | Theme switcher tabs |
| `PlaceholderPattern.vue` | Decorative background pattern |
| `ui/` | shadcn-vue primitives (Badge, Button, etc.) |

### Planner components (`resources/js/components/planner/`)
| Component | Status | Purpose |
|---|---|---|
| `PlannerMilestoneTabs.vue` | ✅ Done | Legacy horizontal tab strip (superseded by `PlannerMilestoneSelector`) |
| `PlannerMilestoneSelector.vue` | ✅ Done | Compact bar with Popover; groupBy (quarter/status/priority), collapsible groups, arc progress ring |
| `PlannerMilestoneHeader.vue` | ✅ Done | Milestone band — title, progress bar, deadline badge, breach warning |
| `PlannerMilestoneExplorer.vue` | ✅ Done | Bottom vaul-vue drawer — full milestone browser with search and select |
| `PlannerFilters.vue` | ✅ Done | Collapsible filter panel — color-coded pills, active chip bar, mobile-responsive (separators hidden on mobile) |
| `PlannerEventList.vue` | ✅ Done | Paginated event list with VueDraggable drag-to-reorder; deferred-prop skeleton state |
| `PlannerEventRow.vue` | ✅ Done | Single event row — drag handle (GripVertical), badges, dates, snooze indicator, context menu |
| `PlannerChildRow.vue` | ✅ Done | Collapsible nested event row with chevron toggle |
| `PlannerEventDrawer.vue` | ✅ Done | Bottom vaul-vue drawer for create/edit event; responsive grid layout (4 rows), snap points |
| `PlannerMilestoneDrawer.vue` | ✅ Done | Bottom vaul-vue drawer for create/edit milestone; responsive grid layout (4 rows), snap points |
| `PlannerSnoozePopover.vue` | ✅ Done | Snooze preset picker |
| `PlannerContextMenu.vue` | ✅ Done | ⋮ / right-click menu — edit, snooze, move to backlog, delete |
| `PlannerBadge.vue` | ✅ Done | Owned badge for status, priority, type, breach |
| `PlannerTagInput.vue` | ✅ Done | Multi-select tag picker + inline create |
| `PlannerEmptyState.vue` | ✅ Done | Empty state illustration + CTA |
| `PlannerViewSwitcher.vue` | ✅ Done | List / Table / Board switcher; view persisted in Pinia + localStorage |
| `PlannerTableView.vue` | ✅ Done | TanStack Table (FlexRender, sorting, column resize) |
| `PlannerFieldCell.vue` | ✅ Done | Inline editing cell for table title field |
| `PlannerBoardView.vue` | ✅ Done | VueDraggable kanban — cross-column event drag |
| `PlannerBoardColumn.vue` | ✅ Done | Droppable zone per status |
| `PlannerBoardCard.vue` | ✅ Done | Draggable card — priority/type badges, date range, tags |
| `PlannerBoardAddCard.vue` | ✅ Done | Inline title input at column bottom |
| `PlannerBulkActionBar.vue` | ✅ Done | Bulk delete + clear selection |

---

## Composables (`resources/js/composables/`)

| Composable | Purpose |
|---|---|
| `useAppearance` | Reads/writes light/dark/system preference |
| `useCurrentUrl` | Reactive current URL |
| `useInitials` | Derives initials from a name string |
| `useTextScramble` | Drives the character-scramble animation used by `ScrambleText` |
| `useTwoFactorAuth` | Manages 2FA setup state (QR fetch, confirmation, recovery codes) |
| `planner/usePlannerFilters` | Manages planner filter state + URL sync |

---

## Layouts (`resources/js/layouts/`)

| Layout | Used by |
|---|---|
| `AppLayout.vue` | Authenticated app pages |
| `AuthLayout.vue` | Auth pages (login, register, etc.) |
| `app/` | Sub-layouts for app shell variants |
| `auth/` | Sub-layouts for auth page variants |
| `settings/` | Settings page wrapper |

---

## Welcome Page — Current State

A single-page marketing/landing experience with three scroll sections:

### Hero
- **NeonText** "VoidOfLimbo" — `#aa00ff`, `L` tilted 18°
- **ScrambleText** cycling "By the / For the / Of the" — triggers glitch on change
- **GlitchText** "Developer"
- Tagline paragraph
- CTA buttons: "Create Free Account" (conditional on `canRegister`), "Sign In"
- **DynamicFloatingMenu** anchored bottom-center, collapsible, tracks active section via IntersectionObserver

### Features
6 cards:
1. Page Builder (Servers)
2. Community Servers (Community)
3. Expense Planner (Productivity)
4. Planner (Productivity)
5. Granular Access Control (Access)
6. Loyalist Perks (Premium)

### CTA
Join / pricing call-to-action (section exists, content TBD).

---

## Actions (`app/Actions/Fortify/`)

Standard Fortify action stubs, customized for this app:
- `CreateNewUser` — validates username + email, creates user with `Free` tier
- `UpdateUserProfileInformation` — updates name, username, email (triggers re-verification on email change)
- `UpdateUserPassword` — validates current + new password
- `ResetUserPassword` — sets new password after token verification

---

## Development Standards

These apply to every feature and file in this codebase. Non-negotiable.

### General
- Always use the **best available approach** for the framework and stack — not the shortest. Correctness, performance, and maintainability come first.
- Follow the AGENTS.md skill guidelines. Load the relevant skill before touching a domain (Pest, Inertia, Wayfinder, Tailwind, etc.).
- Every change must be tested. Write or update Pest feature tests and run them before marking work done.
- Run `vendor/bin/pint --dirty --format agent` after every PHP file change.

### Backend (Laravel)
- All input validation lives in Form Requests — never in controllers.
- All authorization lives in Policies — never inline `if ($user->id !== $resource->user_id)`.
- Eloquent scopes for any reusable query condition. No raw query duplication across controllers.
- Service classes or Action classes for business logic that spans more than one model/concept.
- Eager-load relationships before iterating — no N+1 ever ships.

### Frontend (Vue + Inertia)
- **Data loading follows the layered strategy** (see `blueprint/planner.md` → Data Loading Architecture):
  - Standard props for data needed on first render.
  - `Inertia::defer()` for secondary data; Vue shows skeleton pulse states while `undefined`.
  - `router.reload({ only: [...], preserveScroll: true })` for post-mutation updates.
  - `router.optimistic()` for instant feedback on predictable actions (snooze, toggle).
  - `<WhenVisible>` + `Inertia::defer()` for off-screen sections.
  - `Inertia::merge()` + `<InfiniteScroll>` for long paginated lists.
  - `useHttp` only for standalone requests that produce no page prop update.
- Components are single-responsibility. When a component exceeds ~200 lines of template, split it.
- No component reads `usePage()` or touches global state internally — props drive everything.
- Shared logic lives in composables (`@/composables/`). If a pattern appears twice, extract it.
- Every async operation has a visible loading/skeleton state. No silent waits.
- Every destructive action has a confirm dialog.
- All interactive elements are accessible (keyboard nav, ARIA) via reka-ui primitives.
- `preserveScroll: true` on every mutation that is not a deliberate page navigation.

### Reusability & Extraction
- Feature components live under `@/components/{feature}/` with a feature prefix (e.g. `Planner`).
- Components are built with extraction in mind — zero coupling to page layout or route-specific logic.
- Composables live under `@/composables/{feature}/`.
- Design tokens (colours, spacing, radii) come from Tailwind config — no magic numbers in components.

---

## What Doesn't Exist Yet

- Any real dashboard content
- Server / community feature (Page Builder)
- Expense Planner module
- Planner module
- Subscription / payment flow
- Loyalist perks implementation
- Invite system (page exists, logic TBD)
- Public user profiles
- OAuth callback controllers (Socialite routes not yet wired)
