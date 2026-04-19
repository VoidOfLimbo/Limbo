# App State — VoidOfLimbo

Current snapshot of what exists in the codebase. Updated manually as features are built.

---

## Blueprint Documents

| Document | Purpose |
|---|---|
| [`blueprint/life-planner.md`](./life-planner.md) | Core data model, views overview, milestones, events, tags |
| [`blueprint/life-planner/phase-1.md`](./life-planner/phase-1.md) | Phase 1 spec — migrations, models, controllers, list view |
| [`blueprint/planner-views.md`](./planner-views.md) | **GitHub Projects-style views** — master doc, architecture, phase breakdown |
| [`blueprint/planner-views/component-tree.md`](./planner-views/component-tree.md) | Full Vue component hierarchy for all views |
| [`blueprint/planner-views/data-model.md`](./planner-views/data-model.md) | Custom fields + view config tables (`planner_fields`, `planner_field_values`, `planner_views`) |
| [`blueprint/planner-views/graphql-schema.md`](./planner-views/graphql-schema.md) | Lighthouse GraphQL schema — types, queries, mutations, subscriptions |
| [`blueprint/planner-views/realtime-sync.md`](./planner-views/realtime-sync.md) | Echo + WebSocket strategy, optimistic UI pattern |
| [`blueprint/planner-views/table-view.md`](./planner-views/table-view.md) | TanStack Table + Vue Virtual Scroller implementation guide |
| [`blueprint/planner-views/board-view.md`](./planner-views/board-view.md) | dnd-kit Kanban board implementation guide |
| [`blueprint/planner-views/roadmap-view.md`](./planner-views/roadmap-view.md) | Roadmap/Timeline view — scoped for Phase 4 |
| [`blueprint/inspiration.md`](./inspiration.md) | UI references, libraries, design inspiration |

---

## Feature Progress

### Life Planner — Phase 1

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
| Pest tests — 26 passing (48 assertions) | ✅ Done |
| Seeder (`PlannerSeeder` — all states, edge cases) | ✅ Done |
| `Planner/Index.vue` page | ✅ Done |
| `PlannerMilestoneTabs` | ✅ Done |
| `PlannerMilestoneHeader` | ✅ Done |
| `PlannerFilters` | ✅ Done |
| `PlannerEventList` | ✅ Done |
| `PlannerEventRow` | ✅ Done |
| `PlannerEventDrawer` | ✅ Done (missing tag input) |
| `PlannerMilestoneDrawer` | ✅ Done (missing tag input) |
| `PlannerSnoozePopover` | ✅ Done |
| `PlannerContextMenu` | ❌ TODO |
| `PlannerBadge` | ❌ TODO |
| `PlannerTagInput` + tags in drawers | ❌ TODO |
| `PlannerEmptyState` | ❌ TODO |
| Snooze toast confirmation | ❌ TODO |

---

### Life Planner — Phase 2 (Table View + Board View)

> **Status: Blueprint complete — not yet started**
> See [`blueprint/planner-views.md`](./planner-views.md), [`blueprint/planner-views/table-view.md`](./planner-views/table-view.md), [`blueprint/planner-views/board-view.md`](./planner-views/board-view.md)

| Area | Status |
|---|---|
| Install `@tanstack/vue-table` | ❌ TODO |
| Install `vue-virtual-scroller` | ❌ TODO |
| Install `@dnd-kit` (Vue adapter) | ❌ TODO |
| `PlannerViewSwitcher` (List / Table / Board) | ❌ TODO |
| `PlannerTableView` — TanStack Table + virtual scroll | ❌ TODO |
| `PlannerColumnHeader` — resizable, sortable | ❌ TODO |
| `PlannerTableRow` + `PlannerTableCell` | ❌ TODO |
| `PlannerFieldCell` — inline editing (system fields) | ❌ TODO |
| `PlannerBoardView` — dnd-kit columns + cards | ❌ TODO |
| `PlannerBoardCard` — draggable | ❌ TODO |
| `PlannerBoardAddCard` — inline add | ❌ TODO |
| `PlannerBulkActionBar` — bulk status/priority/delete | ❌ TODO |
| View config persisted to `localStorage` (Phase 2, before DB) | ❌ TODO |

---

### Life Planner — Phase 3 (GraphQL + Custom Fields + Real-time)

> **Status: Blueprint complete — not yet started**
> See [`blueprint/planner-views/graphql-schema.md`](./planner-views/graphql-schema.md), [`blueprint/planner-views/data-model.md`](./planner-views/data-model.md), [`blueprint/planner-views/realtime-sync.md`](./planner-views/realtime-sync.md)

| Area | Status |
|---|---|
| Install Lighthouse (`nuwave/lighthouse`) | ❌ TODO |
| Install Soketi (via Sail) | ❌ TODO |
| Install `laravel-echo` + `pusher-js` | ❌ TODO |
| Migrations: `planner_fields`, `planner_field_values`, `planner_views` | ❌ TODO |
| Models: `PlannerField`, `PlannerFieldValue`, `PlannerView` | ❌ TODO |
| `PlannerSystemFieldsSeeder` | ❌ TODO |
| GraphQL schema (`graphql/schema.graphql`) | ❌ TODO |
| GraphQL resolvers (queries, mutations, subscriptions) | ❌ TODO |
| Broadcast events + observers | ❌ TODO |
| Channel authorization (`routes/channels.php`) | ❌ TODO |
| Pinia `usePlannerStore` with optimistic mutation map | ❌ TODO |
| `useOptimisticUpdate` composable | ❌ TODO |
| `usePlannerRealtime` composable (Echo subscription) | ❌ TODO |
| `PlannerFieldManager` — custom field CRUD | ❌ TODO |
| Custom field columns in Table view | ❌ TODO |
| Custom field grouping in Board view | ❌ TODO |
| `planner_views` saved/named views (replace localStorage) | ❌ TODO |

---

### Life Planner — Phase 4 (Roadmap View)

> **Status: Blueprint scoped — design only**
> See [`blueprint/planner-views/roadmap-view.md`](./planner-views/roadmap-view.md)

| Area | Status |
|---|---|
| `PlannerRoadmapView` — timeline canvas | ❌ Future |
| `useRoadmapLayout` composable (dateToX, zoom levels) | ❌ Future |
| Bar drag + resize (useDraggable) | ❌ Future |
| Dependency arrows (SVG overlay) | ❌ Future |
| Iteration field type + `planner_iterations` table | ❌ Future |

---

## Tech Stack

| Layer | Package | Version |
|---|---|---|
| Runtime | PHP | 8.4 |
| Framework | Laravel | 13 |
| SPA bridge | Inertia.js (Laravel + Vue) | v3 |
| Frontend | Vue 3 | 3.x |
| Styling | Tailwind CSS | v4 |
| Auth backend | Laravel Fortify | v1 |
| Social auth | Laravel Socialite | v5 |
| Typed routes | Laravel Wayfinder | v0 |
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

### Life Planner tables

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

### Life Planner Models

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

### Life Planner Enums

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
| GET/POST/PUT/DELETE | `/events`, `/events/{event}`, `/events/{event}/snooze` | `Planner\EventController` |
| GET/POST/PUT/DELETE | `/milestones`, `/milestones/{milestone}` | `Planner\MilestoneController` |
| GET/POST/PUT/DELETE | `/tags`, `/tags/{tag}`, `/tags/{tag}/attach`, `/tags/{tag}/detach` | `Planner\TagController` |

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

### Life Planner components (`resources/js/components/planner/`)
| Component | Status | Purpose |
|---|---|---|
| `PlannerMilestoneTabs.vue` | ✅ Done | Horizontal tab strip for milestone switching + Backlog tab |
| `PlannerMilestoneHeader.vue` | ✅ Done | Milestone band — title, progress bar, deadline badge, breach warning |
| `PlannerFilters.vue` | ✅ Done | Collapsible filter panel — status, priority, tags, date range, snoozed toggle |
| `PlannerEventList.vue` | ✅ Done | Paginated event list with deferred-prop skeleton state |
| `PlannerEventRow.vue` | ✅ Done | Single event row — badges, dates, snooze indicator |
| `PlannerEventDrawer.vue` | ✅ Done | Right-side sheet for create/edit event (missing tag input) |
| `PlannerMilestoneDrawer.vue` | ✅ Done | Right-side sheet for create/edit milestone (missing tag input) |
| `PlannerSnoozePopover.vue` | ✅ Done | Snooze preset picker |
| `PlannerContextMenu` | ❌ TODO | ⋮ / right-click menu — edit, snooze, move to backlog, delete |
| `PlannerBadge` | ❌ TODO | Owned badge for status, priority, type, breach |
| `PlannerTagInput` | ❌ TODO | Multi-select tag picker + inline create |
| `PlannerEmptyState` | ❌ TODO | Empty state illustration + CTA |

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
4. Life Planner (Productivity)
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
- **Data loading follows the layered strategy** (see `blueprint/life-planner.md` → Data Loading Architecture):
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
- Life Planner module
- Subscription / payment flow
- Loyalist perks implementation
- Invite system (page exists, logic TBD)
- Public user profiles
- OAuth callback controllers (Socialite routes not yet wired)
