# Life Planner — Phase 1: Foundation

**Goal:** Ship the core data layer, Milestone + Event CRUD, List view, Backlog, basic filters, snooze, iCalendar export, and sidebar navigation entry point. No recurrence, no reminders, no sharing.

**Depends on:** [`blueprint/life-planner.md`](../life-planner.md)

---

## Routing

Single entry point. All view toggling is client-side (query param `?view=list` etc., defaulting to `list`).

```
GET /planner          → Planner page (List view, milestone tabs)
```

Named route: `planner`

All sub-actions (CRUD, snooze, export) hit their own API-style controller routes. No full-page reloads.

---

## Navigation

The Life Planner is reachable from the **sidebar navigation**. Add a "Planner" item with an appropriate icon (e.g. calendar/checklist). Follows the existing sidebar pattern used by other authenticated routes.

---

## Database

All Life Planner tables are created in Phase 1 — even those not wired to UI yet (reminders, occurrences, participants). This avoids painful schema additions when later phases land.

### Migrations (in order)

#### 1. `milestones`
```php
$table->ulid('id')->primary();
$table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
$table->string('title');
$table->text('description')->nullable();
$table->string('status')->default('active');          // active|completed|paused|cancelled
$table->string('priority')->default('medium');        // low|medium|high|critical
$table->timestamp('start_at')->nullable();
$table->timestamp('end_at')->nullable();
$table->string('duration_source')->default('derived'); // derived|manual
$table->string('deadline_type')->default('soft');     // soft|hard
$table->string('progress_source')->default('derived'); // derived|manual
$table->unsignedSmallInteger('progress_override')->nullable(); // 0-100
$table->string('visibility')->default('private');     // private|shared
$table->string('color', 7)->nullable();               // hex e.g. #3b82f6
$table->timestamps();
$table->softDeletes();
```

#### 2. `events`
```php
$table->ulid('id')->primary();
$table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
$table->foreignUlid('milestone_id')->nullable()->constrained('milestones')->nullOnDelete();
$table->foreignUlid('parent_event_id')->nullable()->constrained('events')->nullOnDelete();
$table->string('title');
$table->text('description')->nullable();
$table->string('type')->default('event');             // event|task|milestone_marker
$table->string('status')->default('upcoming');        // draft|upcoming|in_progress|completed|cancelled|skipped
$table->string('priority')->default('medium');        // low|medium|high|critical
$table->timestamp('start_at')->nullable();
$table->timestamp('end_at')->nullable();
$table->boolean('is_all_day')->default(false);
$table->boolean('is_milestone_marker')->default(false);
$table->json('recurrence_rule')->nullable();
$table->timestamp('recurrence_ends_at')->nullable();
$table->unsignedInteger('recurrence_count')->nullable();
$table->string('visibility')->default('private');     // private|shared
$table->string('color', 7)->nullable();
$table->string('location')->nullable();
$table->timestamp('snoozed_until')->nullable();
$table->unsignedSmallInteger('snooze_count')->default(0);
$table->timestamps();
$table->softDeletes();

// Indexes
$table->index(['user_id', 'start_at']);
$table->index(['milestone_id', 'start_at']);
$table->index(['user_id', 'status']);
$table->index('snoozed_until');
```

#### 3. `tags`
```php
$table->ulid('id')->primary();
$table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
$table->string('name');
$table->string('color', 7)->nullable();
$table->timestamps();
$table->unique(['user_id', 'name']);
```

#### 4. `taggables`
```php
$table->foreignUlid('tag_id')->constrained()->cascadeOnDelete();
$table->ulidMorphs('taggable');
$table->primary(['tag_id', 'taggable_id', 'taggable_type']);
```

#### 5. `event_reminders` *(table only — delivery wired in Phase 4)*
```php
$table->ulid('id')->primary();
$table->foreignUlid('event_id')->constrained()->cascadeOnDelete();
$table->integer('offset_minutes');          // negative = before, positive = after
$table->json('channels');                   // ["in_app","email","push"]
$table->timestamp('sent_at')->nullable();
$table->timestamps();
```

#### 6. `event_occurrences` *(table only — populated in Phase 3)*
```php
$table->ulid('id')->primary();
$table->foreignUlid('event_id')->constrained()->cascadeOnDelete();
$table->timestamp('start_at');
$table->timestamp('end_at')->nullable();
$table->string('status')->default('upcoming');
$table->json('overrides')->nullable();      // per-occurrence field overrides
$table->timestamps();
$table->index(['event_id', 'start_at']);
```

#### 7. `event_participants` *(table only — UI wired in Phase 6)*
```php
$table->ulid('id')->primary();
$table->foreignUlid('event_id')->constrained()->cascadeOnDelete();
$table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
$table->foreignUlid('invited_by')->constrained('users')->cascadeOnDelete();
$table->string('role')->default('viewer');  // viewer|editor|co_owner
$table->string('status')->default('pending'); // pending|accepted|declined
$table->timestamp('responded_at')->nullable();
$table->timestamps();
$table->unique(['event_id', 'user_id']);
```

#### 8. `event_dependencies` *(table only — UI wired in Phase 5)*
```php
$table->ulid('id')->primary();
$table->foreignUlid('event_id')->constrained()->cascadeOnDelete();
$table->foreignUlid('depends_on_event_id')->constrained('events')->cascadeOnDelete();
$table->string('type')->default('blocking'); // blocking|informational
$table->timestamp('created_at')->useCurrent();
$table->unique(['event_id', 'depends_on_event_id']);
```

---

## Models

### `Milestone`
- Traits: `HasUlids`, `HasFactory`, `SoftDeletes`
- Fillable: `user_id`, `title`, `description`, `status`, `priority`, `start_at`, `end_at`, `duration_source`, `deadline_type`, `progress_source`, `progress_override`, `visibility`, `color`
- Casts: `start_at` → datetime, `end_at` → datetime, `progress_override` → integer
- Relations:
  - `belongsTo(User::class)`
  - `hasMany(Event::class)`
  - `morphToMany(Tag::class, 'taggable')` — via `taggables` pivot
- Computed:
  - `getDerivedProgressAttribute(): int` — `(completed events / total non-snoozed non-cancelled events) * 100`
  - `getProgressAttribute(): int` — returns `progress_override` if `progress_source === 'manual'`, else derived
  - `isBreached(): bool` — true when `deadline_type === 'hard'` and any child event's `end_at > $this->end_at`
  - `derivedStartAt()` / `derivedEndAt()` — min/max of child event dates

### `Event`
- Traits: `HasUlids`, `HasFactory`, `SoftDeletes`
- Fillable: full field list from schema
- Casts: `start_at` → datetime, `end_at` → datetime, `snoozed_until` → datetime, `is_all_day` → boolean, `is_milestone_marker` → boolean, `recurrence_rule` → array
- Relations:
  - `belongsTo(User::class)`
  - `belongsTo(Milestone::class)`
  - `belongsTo(Event::class, 'parent_event_id')` as `parent`
  - `hasMany(Event::class, 'parent_event_id')` as `children`
  - `morphMany` tags via `taggable`
  - `hasMany(EventReminder::class)`
  - `hasMany(EventDependency::class)`
  - `hasMany(EventParticipant::class)`
- Scopes:
  - `scopeActive($q)` — excludes snoozed (where `snoozed_until` is null or in the past)
  - `scopeSnoozed($q)` — where `snoozed_until > now()`
  - `scopeForMilestone($q, $milestoneId)`
  - `scopeBacklog($q)` — where `milestone_id` is null
- Methods:
  - `isSnoozed(): bool`
  - `isBreachingMilestone(): bool` — true if attached to a hard milestone and `end_at > milestone->end_at`

### `Tag`
- Traits: `HasUlids`, `HasFactory`
- Fillable: `user_id`, `name`, `color`
- Relations: `morphedByMany` for both `Event` and `Milestone`

### `EventReminder`, `EventOccurrence`, `EventParticipant`, `EventDependency`
- Stub models with correct fillable/casts/FK relations. No business logic yet.

---

## Enums

Add these PHP-backed string enums to `app/Enums/`:

```
EventStatus       draft | upcoming | in_progress | completed | cancelled | skipped
EventType         event | task | milestone_marker
EventPriority     low | medium | high | critical
EventVisibility   private | shared
MilestoneStatus   active | completed | paused | cancelled
MilestonePriority low | medium | high | critical
DeadlineType      soft | hard
DurationSource    derived | manual
ProgressSource    derived | manual
DependencyType    blocking | informational
```

---

## Factories

- `MilestoneFactory` — states: `hardDeadline()`, `softDeadline()`, `completed()`, `overdue()`
- `EventFactory` — states: `snoozed()`, `completed()`, `inProgress()`, `draft()`, `backlog()` (no milestone), `task()`, `allDay()`
- `TagFactory`

---

## Controllers

### `PlannerController`
```
GET    /planner              index   → Inertia render of Planner/Index.vue with milestones, events, tags, filters
```

This is the single page entry point. It reads the `?milestone=`, `?view=`, and filter query params and passes them as Inertia props.

### `MilestoneController`
```
GET    /milestones              index   → list user's milestones (with derived progress)
POST   /milestones              store
GET    /milestones/{milestone}  show
PUT    /milestones/{milestone}  update  → also recalculates derived dates if needed
DELETE /milestones/{milestone}  destroy → soft delete
```

**Breach detection on update:** When saving an event linked to a hard milestone, the backend must check if the event's `end_at > milestone->end_at`. If so, return the event response with an `is_breaching` flag set to `true`. No automatic extension is applied — that is `soft` behaviour only.

**Soft milestone auto-extend:** When `duration_source = 'derived'` and `deadline_type = 'soft'`, recalculate `end_at` after any child event save/update.

### `EventController`
```
GET    /events              index   → paginated, filterable list
POST   /events              store
GET    /events/{event}      show
PUT    /events/{event}      update
DELETE /events/{event}      destroy → soft delete
POST   /events/{event}/snooze  snooze → sets snoozed_until; increments snooze_count
```

**Snooze action request fields:** `snoozed_until` (datetime, required, must be future).

### `TagController`
```
GET    /tags              index   → user's tags
POST   /tags              store
PUT    /tags/{tag}        update
DELETE /tags/{tag}        destroy
POST   /tags/{tag}/attach   → attach tag to taggable (type + id in body)
DELETE /tags/{tag}/detach   → detach tag from taggable
```

### `PlannerExportController`
```
GET /planner/export/ics              → full planner .ics export
GET /planner/export/ics/{event}      → single event .ics export
GET /planner/export/ics/{milestone}  → milestone (all events) .ics export
```

Returns `Content-Type: text/calendar` with `Content-Disposition: attachment; filename="limbo-planner.ics"`.

Build the VCALENDAR/VEVENT string manually — the ICS format is a simple, well-defined text spec (RFC 5545). No third-party PHP package needed. Each exported event maps:
- `SUMMARY` → `title`
- `DESCRIPTION` → `description`
- `DTSTART` / `DTEND` → UTC timestamps formatted as `Ymd\THis\Z`
- `LOCATION` → `location`
- `UID` → `{id}@limbo`
- `STATUS` → mapped from `EventStatus`

---

## Policies

### `MilestonePolicy`
- `viewAny`, `view`, `create`, `update`, `delete` — all check `$user->id === $milestone->user_id`
- Participants (Phase 6) will extend this

### `EventPolicy`
- `viewAny`, `view`, `create`, `update`, `delete`, `snooze` — `$user->id === $event->user_id`
- Snooze requires ownership only (not shared)

### `TagPolicy`
- All actions: `$user->id === $tag->user_id`

---

## Form Requests

- `StoreMilestoneRequest` / `UpdateMilestoneRequest`
- `StoreEventRequest` / `UpdateEventRequest`
- `SnoozeEventRequest` — validates `snoozed_until` is a future datetime
- `StoreTagRequest` / `UpdateTagRequest`
- `AttachTagRequest` — validates `taggable_type` ∈ `['milestone', 'event']`, `taggable_id` exists

---

## Frontend

### Route & Page

```
GET /planner  →  resources/js/pages/Planner/Index.vue
```

The page receives these Inertia props from the controller:
```ts
{
  milestones: Milestone[]          // user's milestones (with progress, breach flag)
  activeMilestoneId: string | null // currently selected tab (from query param)
  events: Paginated<Event>         // events for selected milestone or backlog
  tags: Tag[]                      // user's tags (for filter chips)
  filters: {
    status?: string
    priority?: string
    tags?: string[]
    date_from?: string
    date_to?: string
    show_snoozed?: boolean
  }
}
```

### List View Layout

```
┌─────────────────────────────────────────────────────────┐
│ [+ New Milestone]                    [Filters ▾] [⬇ ICS]│
├─────────────────────────────────────────────────────────┤
│ [Milestone A ▸] [Milestone B ▸] [Milestone C ▸] [Backlog]│  ← tabs
├─────────────────────────────────────────────────────────┤
│ Milestone A                           ████░░░░ 45%      │  ← milestone header
│ Hard deadline: Dec 31 2026  ⚠ 1 breach                 │
├─────────────────────────────────────────────────────────┤
│ ● Event Title              HIGH  IN PROGRESS  Jan 5 →  │
│   ↳ Sub-event                   UPCOMING      Jan 3 →  │
│ ● Snoozed Event 💤         LOW   UPCOMING      (snoozed)│
│ ● Breaching Event ⚠        HIGH  IN PROGRESS  Jan 10 →  │  ← red highlight
│                                                         │
│ [+ Add event to this milestone]                         │
└─────────────────────────────────────────────────────────┘
```

**Backlog tab** shows events with `milestone_id = null`, same layout but without the milestone header band.

### Drawer (Create / Edit)

A right-side drawer opens for creating and editing both milestones and events. It does not navigate away from `/planner`.

**Event drawer fields (Phase 1):**
- Title (required)
- Type (event / task / milestone_marker) — select
- Status — select
- Priority — select
- Milestone — searchable select (or "No Milestone → Backlog")
- Start date/time
- End date/time
- All-day toggle
- Description — textarea
- Color — colour picker
- Location — text input
- Tags — multi-select / tag input (create new inline)
- Reminders — placeholder UI (shown but disabled until Phase 4)

**Milestone drawer fields (Phase 1):**
- Title (required)
- Description
- Priority
- Status
- Deadline type (soft / hard toggle)
- Duration source (derived / manual)
  - If manual: Start date, End date
- Progress source (derived / manual)
  - If manual: progress slider 0–100
- Color
- Visibility (private only in Phase 1; share option grayed out until Phase 6)
- Tags

### Snooze UX

When the user clicks Snooze on an event (via context menu or event detail):
1. A small popover appears with preset options: **15 min, 1 hour, Tomorrow morning, Next week, Custom date/time**.
2. On confirm, `POST /events/{event}/snooze` is called.
3. Event visually fades/slides out of the active list with a brief animation.
4. A toast confirms "Event snoozed until [time]".

### Hard Deadline Breach UX (List view)

- Milestone tab badge shows `⚠ N breaches` in amber/red when `isBreached()` is true.
- The milestone header band shows a warning callout with count and a "Resolve" CTA.
- Each breaching event row has a red left border and a `⚠ Exceeds deadline` badge.

### Filters panel

A slide-down filter panel accessible from the Filters button:
- Status (multi-select checkboxes)
- Priority (multi-select checkboxes)
- Tags (searchable multi-select)
- Date range (from / to date pickers)
- Show snoozed (toggle, off by default)

Active filters are persisted as query params (`?status=in_progress&priority=high` etc.) so the URL is shareable/bookmarkable.

---

## Frontend Components

**Build philosophy:** All UI components are built from scratch for full ownership and future extraction as a standalone package. No third-party UI component is consumed as a black box. `reka-ui` primitives (headless, unstyled) may be used as accessible behaviour foundations (focus trapping, portal, ARIA), but all visual markup and styling is ours.

Logic utilities are fine to use — they save time without locking UI into someone else's design decisions.

### Logic Utilities (use freely)

| Package | Already installed? | Purpose |
|---|---|---|
| `@vueuse/core` | ✅ Yes | `useDraggable`, `useResizeObserver`, `useElementBounding`, `useEventListener` |
| `date-fns` | ❌ Add | All datetime math — week grids, month grids, offset calculations, formatting, DST handling |
| `colord` | ❌ Add | Hex color parsing, validation, and contrast checking for the color picker |
| `lucide-vue-next` | ✅ Yes | Icons only |

```bash
npm install date-fns colord
```

### reka-ui Primitives (behaviour only, styled by us)

These headless primitives handle ARIA, keyboard navigation, focus trapping, and portal rendering. We own all the markup and CSS on top of them.

| Primitive | Used for |
|---|---|
| `PopoverRoot / PopoverContent` | Snooze picker, filter panel |
| `DialogRoot / DialogContent` | Confirm dialogs (delete, convert deadline type) |
| `SheetRoot / SheetContent` | Drawers (create/edit event & milestone) |
| `TabsRoot / TabsList / TabsTrigger` | Milestone tabs + Backlog tab |
| `SwitchRoot` | All-day toggle, soft/hard deadline toggle, progress source toggle |
| `CollapsibleRoot` | Collapse/expand child events under a parent event |
| `TooltipRoot / TooltipContent` | Icon button labels |
| `SelectRoot / SelectContent` | Status, priority, type dropdowns |

> `reka-ui` is already installed as a dependency of the existing shadcn-vue components. No new packages needed.

### Custom Components to Build (Phase 1)

All components live under `@/components/planner/` and will eventually be extracted into a standalone package. Name everything with the `Planner` prefix for clear namespace.

| Component | Description |
|---|---|
| `PlannerEventRow` | Single event row — title, status/priority badges, dates, snooze indicator, breach indicator, context menu trigger |
| `PlannerMilestoneHeader` | Milestone header band — title, progress bar, deadline type indicator, breach warning with action CTA |
| `PlannerProgressBar` | Animated progress bar for milestone completion — accepts `value` (0–100), `source` (derived/manual), `breached` flag |
| `PlannerColorPicker` | Preset swatch palette (~12 colors) + "Custom" option revealing a hex `<input type="color">` validated via `colord` |
| `PlannerDateTimePicker` | Full date + time picker built from scratch using `date-fns` — month grid navigation, time input, `is_all_day` toggle |
| `PlannerSnoozePopover` | Snooze picker — preset buttons (15 min / 1 hr / Tomorrow morning / Next week) + custom `PlannerDateTimePicker` |
| `PlannerTagInput` | Multi-select tag picker with search and inline "create new tag" (name + color swatch) |
| `PlannerMilestoneDrawer` | Right-side drawer for creating/editing milestones — wraps all milestone fields using reka-ui `SheetContent` |
| `PlannerEventDrawer` | Right-side drawer for creating/editing events — wraps all event fields |
| `PlannerFiltersPanel` | Collapsible filter panel — status, priority, tags, date range, snoozed toggle |
| `PlannerContextMenu` | Right-click / ⋮ menu on event rows — edit, snooze, move to backlog, delete |
| `PlannerBadge` | Styled badge for status, priority, type, and breach — own design tokens, not shadcn Badge |
| `PlannerEmptyState` | Empty state illustration + CTA for empty milestone / backlog |

### Icon Usage (Lucide)

| Action / State | Icon |
|---|---|
| Planner sidebar nav | `CalendarDays` |
| New milestone | `FolderPlus` |
| New event | `Plus` |
| Snooze | `BellOff` |
| Snoozed indicator | `BellOff` (muted color) |
| Hard deadline breach | `AlertTriangle` |
| Delete | `Trash2` |
| Edit | `Pencil` |
| Move to backlog | `Inbox` |
| Export ICS | `Download` |
| Filters | `SlidersHorizontal` |
| Completed | `CircleCheck` |
| Drag handle | `GripVertical` |

---

## Wayfinder

After creating controllers, run:
```bash
sail artisan wayfinder:generate
```

Import route helpers from `@/actions/MilestoneController`, `@/actions/EventController`, etc. in Vue components. Never hardcode URLs.

---

## Testing

All tests are Pest feature tests under `tests/Feature/Planner/`.

### Milestone tests
- Can create a milestone
- Can update a milestone
- Can soft-delete a milestone
- Derived `end_at` extends when a soft-deadline milestone's child event end_at grows
- Derived `end_at` does NOT extend for hard-deadline milestone
- Hard milestone breach detected correctly (`isBreached()` returns true)
- Progress derived correctly (completed / total, excluding snoozed & cancelled)
- Manual progress override returns `progress_override` value
- Cannot view another user's milestone (policy)

### Event tests
- Can create an event (with and without milestone)
- Can create a backlog event (no milestone_id)
- Can snooze an event; `snoozed_until` and `snooze_count` updated
- Active scope excludes snoozed events; snoozed scope returns only snoozed
- `isBreachingMilestone()` returns true when event end_at > hard milestone end_at
- Cannot update another user's event (policy)

### Tag tests
- Can create a tag
- Can attach/detach a tag to an event and milestone
- Tag name is unique per user

### Export tests
- `GET /planner/export/ics` returns `text/calendar` content type
- Response contains a `VEVENT` block for each event
- Single event export contains correct SUMMARY/DTSTART/DTEND
- Unauthenticated request redirects to login

---

## Checklist

- [ ] Migrations: all 8 tables
- [ ] Enums: all 10 enums
- [ ] Models: `Milestone`, `Event`, `Tag`, `EventReminder`, `EventOccurrence`, `EventParticipant`, `EventDependency`
- [ ] Factories: `MilestoneFactory`, `EventFactory`, `TagFactory`
- [ ] Policies: `MilestonePolicy`, `EventPolicy`, `TagPolicy`
- [ ] Form Requests: Milestone (store/update), Event (store/update/snooze), Tag (store/update), AttachTag
- [ ] Controllers: `PlannerController`, `MilestoneController`, `EventController`, `TagController`, `PlannerExportController`
- [ ] Routes registered in `routes/web.php` (or `routes/planner.php` if extracted)
- [ ] Run `wayfinder:generate`
- [ ] `Planner/Index.vue` — List view with milestone tabs + Backlog tab
- [ ] Milestone drawer component
- [ ] Event drawer component
- [ ] Snooze popover component
- [ ] Filters panel component
- [ ] Sidebar navigation entry
- [ ] Breach warning UI (milestone header + event row)
- [ ] iCalendar export endpoints
- [ ] Pest tests (milestones, events, tags, export)
- [ ] Run Pint: `vendor/bin/pint --dirty --format agent`
