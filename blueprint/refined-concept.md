# VoidOfLimbo — Refined High-Level Concept

**Calendar Engine:** Celestine *(a Nakshatra Kālan)* — built in main app, extracted to package when ready

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

**Python AI service:**
A dedicated Python application runs alongside the Laravel app to handle AI/ML tasks. The Laravel app communicates with it to offload work that is better suited to the Python ecosystem (AI models, ML libraries). Built in Phase 1.

- **Initial responsibility:** Receipt processing (upload, parsing, extraction of merchant/date/line items/total)
- **Future responsibilities:** Any AI/ML features added to the roadmap
- **Communication method:** TBD (HTTP API, queue-based, or gRPC) — to be decided when building this service

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
| AI service | Python (sidecar app) | Receipt processing and future AI/ML tasks. Communicates with the Laravel app. |
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

**Leap year / rolling average strategy:** Rolling 7-day and 30-day financial averages will normalise using a trailing 365-day same-day-of-week window rather than fixed-period averages. This cleanly handles BS month-length irregularities and AD leap years while producing statistically meaningful comparisons.

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
| Currency | Per financial account | Each account has its own currency. Set when creating the account. |
| Notification advance notice | TBD global default | User sets a global default for subscription reminders; does not apply to standalone Reminders (those fire at their exact datetime). |
| Region / country | User-defined | Pre-loads confirmed public holidays for the user's region. User can edit, add, or correct holidays at any time (holidays can change after initial announcement). |

---

## Module 1 — Dashboard

The first screen after login. Financial focus with a planning overview sidebar.

**Financial widgets:**
- Rolling 7-day expense total vs. same-day-of-week trailing average *(tab: rolling 30-day vs. same-date trailing average)*
- Purchase habit summary: top spending categories (chart) + most frequent merchants — all other habit insights (time-of-day patterns, day-of-week patterns, impulse vs planned, month-over-month comparison, largest transactions) are available via drill-down into **Module 6 — Analytics & Insights**
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
| Receipt upload + AI extraction (Python service) | Phase 1 |
| Bank Open Banking API integration | Future |
| Retailer loyalty API (Lidl, Morrisons) | Nice-to-have / Future |

### 2.2 Transaction Types
- **Expense** — purchase, bill, fee
- **Income** — salary, freelance, transfer in
- **Transfer** — between user's own accounts. Same-currency transfers record amount only. Cross-currency transfers require the user to enter the exchange rate at the time of the transfer (same mechanism as the secondary-currency field on regular transactions); recorded as a paired debit + credit.

### 2.3 Currency Model
Currency is set **per financial account** (each bank account, savings account, cash wallet, or credit card has its own currency). This allows a user to hold a GBP bank account alongside an NPR savings account. Transactions inherit the currency of their account. The optional secondary-currency field on a transaction stores the **conversion rate locked to the transaction's datetime** (historical rate, not live).

### 2.4 Account Types

| Type | Balance behaviour |
|---|---|
| Bank (current/checking) | Asset — positive balance |
| Savings | Asset — positive balance |
| Cash | Asset — positive balance |
| Credit card | Liability — balance represents debt owed |

All account types are user-labelled. The type field determines how the balance is calculated and displayed (asset vs liability).

**Opening balance:** Optional. User can set an opening balance (amount + date) when creating an account. Balance = opening balance + all transactions recorded after that date. Accounts without an opening balance show relative change only (no absolute balance or net worth figure).

### 2.5 File Attachments

- **Phase 1:** Receipt image/document upload on transactions. The file is sent to the **Python AI service** for parsing (merchant, date, line items, total extracted automatically). User can review and correct the extracted data before saving.
- **Phase 2:** File attachments on all major entities (tasks, events, milestones, etc.)

### 2.6 Data Model Concepts
- `transactions` — TimescaleDB hypertable, partitioned by datetime
- `expense_categories` — hierarchical expense category tree (e.g. Food → Groceries → Lidl)
- `income_categories` — separate hierarchical income category tree (e.g. Employment → Salary)
- `merchants` — normalised payee/merchant records
- `accounts` — typed (bank, savings, cash, credit card), each with its own currency and optional opening balance
- `receipts` — document store, linked to one or more transactions (Phase 1 — upload + AI extraction)
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
- Fields: title, description, start datetime, end datetime, status
- **Status:** Unscheduled (no dates) · Scheduled · In Progress (start passed, end not reached — auto-determined) · Done (manually marked by user)
- **Relationships:** subtask-of · parallel-with · dependent-on (blocking)
- A task can have any number of subtasks; subtasks follow the same rules as tasks
- Without start + end assigned → **Unscheduled** (not shown on calendar; lives in the Task Board unscheduled column)

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

- Fields: title, start time, end time, recurrence pattern (days of week, exceptions), skip-holiday flag
- **Skip conditions:** public holidays, leave days, custom exceptions
- Edit scope on modification: **this occurrence only** or **this and all future occurrences**

**Leave days** are a calendar-level attribute, not a separate module. The Celestine calendar ships with a default leave/holiday layer (populated from the user's region holiday calendar). Users can modify it freely. Leave days are **informational** — they do not hard-block anything. Each schedule, task, milestone, or subscription can independently choose whether to treat leave/holidays as skip days or proceed normally. The choice is per-entity, not global.

**Examples:** Work hours Mon–Fri 9–5, sleep routine (bed by 9PM), gym Mon/Wed/Fri 7AM

> **Boundary with Subscription:** Schedule = behavioural/time pattern. Subscription = recurring financial event. "Pay staff on Fridays" belongs in Subscription, not Schedule.

### 3.4 Subscription

Recurring **financial** events (expenses or income).

- Fields: amount, category, merchant/payer, account, frequency, start date, optional end date
- Complex frequency rules supported: "first working day of month", "last Friday of month"
- Working day calculation uses the user's **region holiday calendar** (pre-loaded confirmed holidays + user-customisable; see User Settings)
- **Ending a subscription:** user can either (a) set an end date in advance — auto-stops after that date, or (b) manually deactivate it at any time. Either way, history is retained.
- Behaviour: sends a **reminder** when due — user manually logs the transaction. Does not auto-create transactions.
- Notification timing: global default set in user settings; overridable per subscription
- Displays payment history (manually logged entries linked to this subscription)

### 3.5 Reminder

General-purpose alert, the simplest planner entity.

- Fields: title, firing time (absolute datetime **or** relative offset from a linked entity, e.g. '30 minutes before')
- Can be linked to any other entity (event, task, subscription, milestone)
- When **standalone**: datetime is the exact firing time
- When **attached to an entity**: user can choose absolute datetime OR relative offset (X minutes / hours / days before the linked entity's start time)
- The global advance-notice setting does not apply to standalone Reminders
- Delivery: in-app notification bell + email

### 3.6 Event

- Fields: title, description, start datetime, end datetime (single or multi-day)
- Location: physical address **or** remote/virtual (URL)
- Attendees: **Phase 1** — free-text notes field (names, contacts). **Future** — registered-user invite/RSVP system
- Can have one or more Reminders attached

### 3.7 Bucket List

Lifetime goals and aspirations. Not time-bound.

- Fields: title, description, notes, status (Active / Achieved / Abandoned)
- Can link to: milestones, tasks, events, schedules, subscriptions (the "how I'll get there")
- **Structure:** flat list (no sub-items in Phase 1; nested sub-items are a future enhancement)
- **Ordering:** FCFS by default (creation order). User can manually reorder via drag-and-drop for visual preference only — order has no functional or priority meaning.

### 3.8 Limbo

Unstructured idea capture — the void where things exist before they have a place. Thematically consistent with the app name.

- Fields: title, notes
- No date/time association
- Can be **promoted** to: Task · Event · Schedule · Subscription · Milestone · Bucket List item

---

## Cross-Cutting Features

### Tags

Free-form, user-defined tags applicable to **all entities** across all modules (transactions, tasks, events, milestones, schedules, subscriptions, reminders, bucket list items, Limbo items).

- Tags are global (not scoped per module) — a tag created on a transaction is the same tag that can be applied to a task
- Used for custom grouping and filtering beyond fixed categories
- Tags are searchable via both global and local search

### Deletion Policy

All entities support **both** soft delete and permanent delete:

- **Soft delete (archive):** entity is hidden from normal views but retained in the database. Can be restored.
- **Permanent delete:** irreversible. Requires strict, explicit confirmation (separate, deliberate action — not a single click). The action is logged internally in the database for admin/support purposes; not exposed in the user UI.
- Default behaviour on “delete” is soft delete; permanent delete is a deliberate secondary action.

---

## Module 4 — Calendar View

All planner entities are surfaced on the calendar with distinct visual treatment (colour coding and icons per entity type, filterable by type/category). This view will eventually be extracted into the Celestine package.

**Entity calendar presence:**

| Entity | Calendar presence |
|---|---|
| Milestone | Span/bar across date range |
| Task (scheduled) | Block on scheduled day(s) |
| Schedule | Recurring pattern blocks |
| Subscription | Due-date indicator |
| Event | Single/multi-day block |
| Reminder | Time-point indicator |
| Task (unscheduled) | Not shown — lives in the task board / unscheduled sidebar |
| Bucket List / Limbo | Not shown on calendar |

---

## Module 5 — Task Board

A dedicated view for managing tasks outside of the calendar grid. Complements the Calendar View by surfacing all tasks in a structured, prioritisable workspace.

**Access:** standalone navigation item; also accessible from within a Milestone's detail view (scoped to that milestone's tasks).

**Columns / states:**
- **Unscheduled** — tasks with no start/end date assigned
- **Scheduled** — tasks with dates assigned but start date not yet reached
- **In Progress** — start date has passed, end date has not (auto-determined from datetime)
- **Done** — manually marked as complete by the user

**Priority view tabs** (switchable, applied across all columns):
- Default list view
- Eisenhower Matrix (Phase 1)
- MoSCoW (Phase 1)
- Value vs Effort (Phase 1)
- 80/20, WSJF, ICE (Phase 2)

**Filtering:** by milestone, by tag, by assignee (Phase 2 when multi-user is added), by due date range.

---

## Module 6 — Analytics & Insights

A dedicated analytics dashboard reachable via drill-down from the main Dashboard and from the main navigation.

**Purchase habit insights:**

| Insight | Categorisation method |
|---|---|
| Top spending categories | Automatic — from transaction category |
| Most frequent merchants | Automatic — from transaction merchant |
| Spending by time of day | Automatic — from transaction datetime |
| Day-of-week spending pattern | Automatic — from transaction datetime |
| Impulse vs planned purchases | **Manual with rule-based default** — system applies a default rule (e.g. unplanned/miscellaneous categories flagged as impulse); user can override the flag on any transaction. AI-detected pattern analysis is a future enhancement. |
| Month-over-month category comparison | Automatic — computed from transaction history |
| Largest single transactions | Automatic — sorted by amount |

**Other analytics (scope to be defined per feature):**
- Income vs expense over time
- Net worth trend (assets minus liabilities across all accounts)
- Subscription cost summary
- Milestone progress and on-time completion rates (future)

---

## Notifications

**Phase 1:** In-app bell + Email (Mailpit in development via Laravel Sail)

**Future:** SMS (third-party provider), webhooks (Discord, Cliq, and others)

---

## User Tiers

Codebase defines: `Free` · `Premium` · `Loyalist`

**Current status: no feature gating during development.** All features available to all users. Tier boundaries to be defined before public launch.

---

## Future / Roadmap

Features explicitly deferred and not part of Phase 1:

| Feature | Notes |
|---|---|
| Multi-user sharing | Families and business partners sharing data. Requires ownership scoping, permission model, and invite flows. |
| Newari (Nepal Sambat) calendar | Display and conversion only initially. |
| Nepali UI localisation | Devanagari script throughout the app. |
| PWA offline support | Read/write with background sync. Potential enhancement. |
| Bucket list sub-items | Nested items under a bucket list entry. |
| File attachments on non-transaction entities | Tasks, events, milestones, etc. |
| Task Gantt / Project View | Visual dependency chart with critical path. |
| Phase 2 task priority views | 80/20, WSJF, ICE. |
| Bank Open Banking API integration | Auto-fetch transactions. |
| Retailer loyalty APIs | Lidl, Morrisons, etc. |
| SMS + webhook notifications | Discord, Cliq, and others. |
| Android native app | Built after web app is feature-complete. |
| Event attendee invite / RSVP | Registered-user invite system. |

---

## Open Items

Items to be defined before implementation of the affected modules.

| # | Item | Affects | Status |
|---|---|---|---|
| 1 | Dashboard "purchase habit" — specific insights to surface | Dashboard | ✅ Resolved — see Module 1 |
| 2 | Bucket list sub-items — support nested items? ordering/prioritisation? | Bucket List | ✅ Resolved — see §3.7 |
| 3 | Dependency graph / Project View (Gantt) | Task / Milestone | ✅ Resolved — Phase 2 dedicated view. See §3.2 |
| 4 | Multi-user sharing | All modules | ✅ Resolved — Phase 2. Single-user in Phase 1. |
| 5 | Public holiday data source | Schedule / Subscription | ✅ Resolved — pre-loaded per region + user-editable |
| 6 | PWA offline support | Mobile / PWA | ✅ Resolved — not Phase 1; potential future enhancement |
| 7 | Tags / labels | All entities | ✅ Resolved — global free-form tags. See Cross-Cutting Features |
| 8 | Archive vs permanent delete | All entities | ✅ Resolved — both, strict confirmation, internal audit log. See Cross-Cutting Features |
| 9 | File attachments beyond transactions | All entities | ✅ Resolved — transactions Phase 1 (AI extraction); all entities Phase 2 |
| 10 | Receipt upload phase + AI service architecture | Financial + Architecture | ✅ Resolved — Phase 1 via Python AI sidecar. See Architecture |
| 11 | Backlog naming clash | Planner | ✅ Resolved — idea-capture entity renamed to “Limbo”; unscheduled tasks called “Unscheduled” |
| 12 | Currency scope (user vs account) | Financial | ✅ Resolved — currency is per financial account |
| 13 | Subscription end date | Planner | ✅ Resolved — manual deactivation or set end date in advance |
| 14 | Leave tracking for schedules | Planner / Calendar | ✅ Resolved — calendar-level attribute, informational only, per-entity opt-in |
| 15 | Delete audit log visibility | Cross-cutting | ✅ Resolved — internal DB only, not exposed in user UI |
| 16 | Task Board view layout and column states | Module 5 | ✅ Resolved — see Module 5 |
| 17 | Analytics & Insights module scope | Module 6 | ✅ Resolved — see Module 6 |
| 18 | Impulse vs planned categorisation method | Analytics | ✅ Resolved — manual + rule-based default, user-correctable; AI pattern analysis is future |
| 19 | Event/Task reminder timing (absolute vs relative offset) | Planner | ✅ Resolved — both supported. See §3.5 |
| 20 | Account opening balance | Financial | ✅ Resolved — optional; accounts without one show relative change only. See §2.4 |
| 21 | Cross-currency transfers | Financial | ✅ Resolved — user enters exchange rate at transfer time. See §2.2 |
| 22 | Income vs expense category separation | Financial | ✅ Resolved — separate hierarchical trees. See §2.6 |
| 23 | Python AI service communication method | Architecture | Open — decide when building the service |
| 24 | OCR/AI solution for receipt extraction | Financial / Python service | Open — decide when building the Python service |
| 25 | User tier feature gating strategy | All modules | Open — define before public launch |
| 26 | Newari (Nepal Sambat) calendar scope | Celestine / Calendar | Open — future phase |
| 27 | GDPR, legal, and compliance sweep | All modules | Open — sweep before public launch |
| 28 | Default advance notice value for notifications | User Settings / Notifications | Open — define when building notification system |
