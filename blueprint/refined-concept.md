# VoidOfLimbo — Refined High-Level Concept

**Calendar Engine:** Celestine *(a Nakshatra Kālan)* — local Composer package

---

## Vision

VoidOfLimbo is a unified personal life-management platform anchored by a native Nepali calendar (Bikram Sambat). It combines financial tracking, life planning, scheduling, and goal management into a single coherent experience — giving users both high-level insight and the ability to drill down into granular detail across time, money, and goals. The target audience is general-purpose: individuals, families, and small business owners.

---

## Architecture

**Single Laravel application.** Everything is built directly in the main app first. Features are extracted into standalone local Composer packages **only when there is a clear reuse case** (i.e. the feature would be genuinely useful in another project). Package extraction happens organically during development, not upfront.

**Package philosophy (when extraction happens):**
- Follows the **Laravel standard package structure** (service providers, config publishing, migrations, etc.)
- Ships **both** PHP backend (logic, models, services) and a **Vue component library** (building blocks + layouts)
- Two tiers of Vue components: **Primitives** (unstyled, low-level) and **Composed** (plug-and-play, slot/prop-driven)
- Full-page **layouts** shipped where appropriate — opinionated defaults, fully overridable via slots
- Consuming app can extend PHP logic and override/customise UI — package is the core, app diverges where needed
- Packages live in `/packages`, linked via Composer `path` repository + Vite path alias when extracted

**Anticipated future packages** *(not extracted yet — built in main app first):*

| Likely package | Name | What it will contain |
|---|---|---|
| Calendar engine + UI | `Celestine` (a Nakshatra Kālan) | BS/AD calendar logic, date conversion, recurring pattern engine, calendar Vue components + layouts |
| Others | TBD | Extracted as reuse cases emerge during development |

---

## Tech Stack

| Layer | Choice | Notes |
|---|---|---|
| Backend | Laravel 13 (PHP 8.3) | |
| Frontend | Vue.js + Inertia.js | |
| Database | TimescaleDB (PostgreSQL extension) | Hypertables for financial + event time-series data |
| Auth | Laravel Fortify + Socialite | Microsoft OAuth already integrated |
| Notifications (dev) | Mailpit via Laravel Sail | |
| Notifications (prod) | In-app bell + Email | SMS / webhooks (Discord, Cliq) planned for future |
| Testing | Pest | |
| Mobile | PWA first | Separate Android app after web app is feature-complete |
| Localisation | English (Phase 1) | Nepali / Devanagari UI in Phase 2 |
| Search | Global (cross-module) + local (module-scoped) | Global search from main nav; local search within each module |

---

## Calendar Engine: Celestine (a Nakshatra Kālan)

**Currently built in the main app.** Will be extracted into a standalone package when ready. This section describes the intended final shape of the extracted package.

The calendar is the central dependency of the entire application. All planning, scheduling, financial, and display features reference it.

### PHP Backend

**Phase 1 scope:**
- **BS (Bikram Sambat)** — primary calendar system
- **AD (Gregorian)** — secondary, full interoperability with BS

**Future phase:**
- **Newari (Nepal Sambat)** — display and conversion

**Capabilities:**
- Bidirectional date conversion BS ↔ AD
- BS-aware month length tables (irregular lengths, no fixed formula)
- Recurring pattern engine: daily, weekly, monthly (nth weekday of month, last working day, etc.), custom — shared by Schedule, Subscription, Event, Reminder
- Locale-aware date formatting: Nepali numerals, month and day names in Nepali and English
- Holiday / leave integration hooks (consumed by the Schedule module)
- Extendable — consuming app can extend core classes and bind overrides via the service provider

### Vue Component Library

The package provides **two tiers of Vue components:**

| Tier | Description | Examples |
|---|---|---|
| **Primitives** | Low-level, unstyled building blocks. Full control given to the consuming app. | `<CalendarCell>`, `<DayLabel>`, `<MonthGrid>` |
| **Composed** | Plug-and-play assembled views with sensible defaults. Customisable via props and slots. | `<CalendarDay>`, `<CalendarWeek>`, `<CalendarMonth>`, `<CalendarYear>` |

**Component design principles:**
- Slot-heavy: every meaningful section is a named slot so the consuming app can inject custom content
- Prop-driven configuration: entity rendering (colours, icons, filtering) controlled via props
- Emit events for all interactions (day-click, event-click, range-select) — the app decides what to do
- Styles are scoped and themeable; consuming app can publish and override CSS variables

**Leap year / rolling average strategy:** Rolling 7-day and 30-day financial averages will normalise using a trailing 365-day same-day-of-week window rather than fixed-period averages. This cleanly handles BS month-length irregularities and AD leap years while producing statistically meaningful comparisons.

**Calendar views provided:**
- Daily · Weekly · Monthly · Yearly grid views
- Mini calendar (for date pickers and sidebars)

**Layouts provided:**
Where it makes sense the package also ships full **page layouts** for each calendar view (the assembled page with header, navigation, sidebars, and the calendar grid wired together). These are higher-level than composed components — an opinionated default that works out of the box. The consuming app can use them as-is, override via slots, or discard them entirely and build its own layout from composed components and primitives.

---

## User Settings

Per-user account settings that affect behaviour across all modules:

| Setting | Default | Notes |
|---|---|---|
| Preferred calendar system | BS (Bikram Sambat) | User-selectable: BS or AD. Affects all date display throughout the app. |
| Timezone | Auto-detected from browser | User can override. All datetimes stored in UTC internally. |
| Language / locale | English | Nepali (Devanagari) in Phase 2. |
| Currency | User-defined on first setup | One primary currency per account. |
| Notification advance notice | TBD global default | User sets a global default; individual reminders/subscriptions can override. |
| Region / country | User-defined | Pre-loads confirmed public holidays for the user's region. User can edit, add, or correct holidays at any time (holidays can change after initial announcement). |

---

## Module 1 — Dashboard

The first screen after login. Financial focus with a planning overview sidebar.

**Financial widgets:**
- Rolling 7-day expense total vs. same-day-of-week trailing average *(tab: rolling 30-day vs. same-date trailing average)*
- Purchase habit summary: top spending categories (chart) + most frequent merchants — all other habit insights (time-of-day patterns, day-of-week patterns, impulse vs planned, month-over-month comparison, largest transactions) are available via drill-down into a dedicated habits dashboard
- Upcoming subscriptions / bills (next 7 days)

**Planning widgets:**
- Tasks due today / overdue
- Upcoming events (next 7 days)
- Active milestone progress summary
- Unread reminders

Drill-down from every widget navigates to the corresponding module.

---

## Module 2 — Financial Tracking

### 2.1 Transaction Entry

| Method | Phase |
|---|---|
| Manual entry (amount, category, merchant, date, notes) | Phase 1 |
| Bank transaction import — CSV / OFX upload | Phase 1 |
| Receipt upload + OCR extraction | Phase 2 (solution TBD) |
| Bank Open Banking API integration | Future |
| Retailer loyalty API (Lidl, Morrisons) | Nice-to-have / Future |

### 2.2 Transaction Types
- **Expense** — purchase, bill, fee
- **Income** — salary, freelance, transfer in
- **Transfer** — between user's own accounts

### 2.3 Currency Model
Single active currency per account (user-defined). Optional secondary-currency field on a transaction with the **conversion rate locked to the transaction's datetime** (historical rate, not live). This preserves accurate historical value while keeping the data model simple.

### 2.4 Account Types

| Type | Balance behaviour |
|---|---|
| Bank (current/checking) | Asset — positive balance |
| Savings | Asset — positive balance |
| Cash | Asset — positive balance |
| Credit card | Liability — balance represents debt owed |

All account types are user-labelled. The type field determines how the balance is calculated and displayed (asset vs liability).

### 2.5 File Attachments

- **Phase 1:** Receipt image/document upload on transactions only
- **Phase 2:** File attachments on all major entities (tasks, events, milestones, etc.)

### 2.6 Data Model Concepts
- `transactions` — TimescaleDB hypertable, partitioned by datetime
- `categories` — hierarchical (e.g. Food → Groceries → Lidl)
- `merchants` — normalised payee/merchant records
- `accounts` — typed (bank, savings, cash, credit card)
- `receipts` — document store, linked to one or more transactions (Phase 2)
- `tags` — global free-form tags, polymorphic relationship to all entity types

---

## Module 3 — Planner

All planner entities appear on the Celestine calendar. Entities can be freely cross-linked.

### 3.1 Milestone

High-level goal container. Not day-to-day detail — the "what" not the "how."

| Sub-type | Behaviour |
|---|---|
| **Fixed** | Defined start + end date. Deadline is **hard** (strict) or **soft** (advisory). Deadline type changes require explicit confirmation and are audit-logged on the milestone. |
| **Flexible / Continuous** | No end date; runs until manually stopped. |
| **Flexible / Bounded** | Has a "no later than" date. Auto-disables on that date; hidden from calendar. User can extend or close it. |

**Hard deadline breach flow** (triggered when scheduling a task beyond the milestone's hard deadline):
User is presented with a modal to choose one of:
1. Extend the hard deadline to a new date
2. Convert to soft deadline + set new advisory date
3. Cap the task at the current hard deadline
4. Cancel — return and revise the task

The milestone records which option was taken and when (full audit log).

### 3.2 Task

- Belongs to a **milestone** or is **standalone**
- Fields: title, description, start datetime, end datetime, subtasks, relationships
- Without start + end assigned → **Unallocated** (backlog state, not shown on calendar)
- **Task relationships:** parallel-with · dependent-on (blocking) · subtask-of
- A task can have any number of subtasks; subtasks follow the same rules as tasks

**Priority classification views:**

| View | Phase |
|---|---|
| Eisenhower Matrix (Urgent/Important quadrants) | Phase 1 |
| MoSCoW (Must/Should/Could/Won't) | Phase 1 |
| Value vs Effort | Phase 1 |
| 80/20 | Phase 2 |
| WSJF (Weighted Shortest Job First) | Phase 2 |
| ICE (Impact/Confidence/Ease) | Phase 2 |

**Dependency visualisation:**

| Phase | View |
|---|---|
| Phase 1 | Structured list within the Task/Milestone detail panel — shows "blocked by" and "blocking" per task |
| Phase 2 | Dedicated **Project View** (separate from the calendar grid) — Gantt-style chart with timeline bars and dependency arrows, accessible from a Milestone or Task. Shows the critical path, parallel tracks, and cascading impact of delays. |

> The Gantt/Project View is **not** a calendar grid mode. It is a standalone view opened from the context of a specific Milestone or Task.

### 3.3 Schedule

Recurring **behavioural and time-based patterns**. Not financial.

- Pattern rules: days of week, time window, per-day-variant times (e.g. Friday earlier finish)
- Skip conditions: public holidays, annual leave, sick leave, custom exceptions
- Edit scope on modification: **this occurrence only** or **this and all future occurrences**

**Examples:** Work hours Mon–Fri 9–5, sleep routine (bed by 9PM), gym Mon/Wed/Fri 7AM

> **Boundary with Subscription:** Schedule = behavioural/time pattern. Subscription = recurring financial event. "Pay staff on Fridays" belongs in Subscription, not Schedule.

### 3.4 Subscription

Recurring **financial** events (expenses or income).

- Fields: amount, category, merchant, account, frequency, start date
- Complex frequency rules supported: "first working day of month", "last Friday of month"
- Working day calculation uses the user's **region holiday calendar** (pre-loaded confirmed holidays + user-customisable; see User Settings)
- Behaviour: sends a **reminder** when due — user manually logs the transaction. Does not auto-create transactions.
- Notification timing: global default set in user settings; overridable per subscription
- Displays payment history (manually logged entries linked to this subscription)

### 3.5 Reminder

General-purpose alert, the simplest planner entity.

- Fields: title, datetime
- Can be linked to any other entity (event, task, subscription, milestone)
- Notification timing: global default set in user settings; overridable per reminder
- Delivery: in-app notification bell + email

### 3.6 Event

- Fields: title, description, start datetime, end datetime (single or multi-day)
- Location: physical address **or** remote/virtual (URL)
- Attendees: **Phase 1** — free-text notes field (names, contacts). **Future** — registered-user invite/RSVP system
- Can have one or more Reminders attached

### 3.7 Bucket List

Lifetime goals and aspirations. Not time-bound.

- Fields: title, description, notes, status (Active / Achieved / Abandoned)
- Can link to: milestones, tasks, events (the "how I'll get there")
- **Structure:** flat list (no sub-items in Phase 1; nested sub-items are a future enhancement)
- **Ordering:** FCFS by default (creation order). User can manually reorder via drag-and-drop for visual preference only — order has no functional or priority meaning.

### 3.8 Backlog

Unstructured idea capture — the "someday / maybe" list.

- Fields: title, notes
- No date/time association
- Can be **promoted** to: Task · Event · Schedule · Subscription · Milestone · Bucket List item

---

## Cross-Cutting Features

### Tags

Free-form, user-defined tags applicable to **all entities** across all modules (transactions, tasks, events, milestones, schedules, subscriptions, reminders, bucket list items, backlog items).

- Tags are global (not scoped per module) — a tag created on a transaction is the same tag that can be applied to a task
- Used for custom grouping and filtering beyond fixed categories
- Tags are searchable via both global and local search

### Deletion Policy

All entities support **both** soft delete and permanent delete:

- **Soft delete (archive):** entity is hidden from normal views but retained in the database. Can be restored.
- **Permanent delete:** irreversible. Requires strict confirmation (explicit confirmation prompt; separate from standard destructive actions). The action is logged for accountability.
- Default behaviour on "delete" is soft delete; permanent delete is a deliberate secondary action.

---

## Module 4 — Calendar View

All planner entities are surfaced on the calendar with distinct visual treatment (colour coding and icons per entity type, filterable by type/category). This view will eventually be extracted into the Celestine package.

**Entity calendar presence:**

| Entity | Calendar presence |
|---|---|
| Milestone | Span/bar across date range |
| Task (allocated) | Block on scheduled day(s) |
| Schedule | Recurring pattern blocks |
| Subscription | Due-date indicator |
| Event | Single/multi-day block |
| Reminder | Time-point indicator |
| Task (unallocated) | Not shown — lives in backlog sidebar |
| Bucket List / Backlog | Not shown on calendar |

**Future:** Multi-user sharing (family/business collaborators) — Phase 2. Will require ownership scoping, permission model, and invite flows.

---

## Notifications

**Phase 1:** In-app bell + Email (Mailpit in development via Laravel Sail)

**Future:** SMS (third-party provider), webhooks (Discord, Cliq, and others)

---

## User Tiers

Codebase defines: `Free` · `Premium` · `Loyalist`

**Current status: no feature gating during development.** All features available to all users. Tier boundaries to be defined before public launch.

---

## Open Items

Items to be defined before implementation of the affected modules.

| # | Item | Affects | Status |
|---|---|---|---|
| 1 | Dashboard "purchase habit" — specific insights to surface | Dashboard | ✅ Resolved — see Module 1 |
| 2 | Bucket list sub-items — support nested items? ordering/prioritisation? | Bucket List | ✅ Resolved — see §3.7 |
| 3 | Dependency graph / Project View (Gantt) | Task / Milestone | ✅ Resolved — Phase 2 dedicated view, not a calendar mode. See §3.2 |
| 4 | Multi-user sharing (families, business partners) | All modules | ✅ Resolved — Phase 2. Single-user in Phase 1. |
| 5 | Public holiday data source | Schedule / Subscription | ✅ Resolved — pre-loaded confirmed holidays per region + user-editable |
| 6 | PWA offline support | Mobile / PWA | ✅ Resolved — not in Phase 1; potential future enhancement |
| 7 | Tags / labels | All entities | ✅ Resolved — global free-form tags, all entities. See Cross-Cutting Features |
| 8 | Archive vs permanent delete | All entities | ✅ Resolved — both, with strict confirmation + accountability log. See Cross-Cutting Features |
| 9 | File attachments beyond transactions | All entities | ✅ Resolved — transactions only (Phase 1); all entities (Phase 2) |
| 10 | OCR receipt extraction solution | Financial — Phase 2 | Open |
| 11 | User tier feature gating strategy | All modules | Open — define before public launch |
| 12 | Newari (Nepal Sambat) calendar scope and timeline | Celestine / Calendar | Open — future phase |
| 13 | GDPR, legal, and compliance sweep | All modules | Open — sweep before public launch |
| 14 | Default advance notice value for notifications | User Settings / Notifications | Open — define when building notification system |
