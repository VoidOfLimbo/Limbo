# Planner Views — Data Model Extensions

**Depends on:** [`blueprint/planner-views.md`](../planner-views.md), [`blueprint/state.md`](../state.md)
**Status:** Blueprint / Design (Phase 3 additions)

---

## Overview

Phase 1 established a fixed-field schema for events and milestones. Phase 3 extends this with:

1. **Custom fields** — user-defined metadata columns attached to planner items
2. **View configurations** — named, persisted views with layout/filter/sort state
3. **Iteration definitions** — time-boxed sprints that custom fields can reference

These additions are purely additive — no existing columns are modified.

---

## New Tables

### `planner_fields` — Field Definitions

Defines the schema of a custom field. Fields are scoped to a milestone or global (user-level).

```php
$table->ulid('id')->primary();
$table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
$table->foreignUlid('milestone_id')->nullable()->constrained('milestones')->nullOnDelete();
// null milestone_id = global field visible across all milestones

$table->string('name');                          // e.g. "Effort", "Department"
$table->string('type');
// Enum values:
//   text | number | date | single_select | multi_select
//   iteration | url | person | checkbox

$table->json('options')->nullable();
// Only for select types. Array of option objects:
// [{ "id": "ulid", "name": "Low", "color": "#4ade80" }, ...]

$table->json('settings')->nullable();
// Type-specific settings:
//   number:  { "format": "integer|decimal|percent|currency", "currency": "USD" }
//   date:    { "include_time": true }
//   url:     { "show_preview": false }
//   iteration: { "start_day": "monday", "duration_weeks": 2 }

$table->unsignedSmallInteger('position')->default(0); // display order
$table->boolean('is_system')->default(false);
// true = built-in field (title, status, priority, start_at, end_at, type)
// System fields cannot be deleted; their 'type' is fixed

$table->timestamps();
```

**System fields** (seeded automatically, `is_system = true`):

| name | type | notes |
|---|---|---|
| `Title` | `text` | maps to `events.title` |
| `Status` | `single_select` | maps to `events.status` enum |
| `Priority` | `single_select` | maps to `events.priority` enum |
| `Type` | `single_select` | maps to `events.type` enum |
| `Start Date` | `date` | maps to `events.start_at` |
| `End Date` | `date` | maps to `events.end_at` |
| `Milestone` | `text` | maps to parent milestone title |

System fields are read-write via their native columns. Custom fields write to `planner_field_values`.

---

### `planner_field_values` — Field Values

One row per (item, field) pair. All value types are stored in a JSONB column.

```php
$table->ulid('id')->primary();
$table->foreignUlid('field_id')->constrained('planner_fields')->cascadeOnDelete();
$table->ulidMorphs('item');
// item_id + item_type (polymorphic: App\Models\Event | App\Models\Milestone)

$table->json('value')->nullable();
// Stores any value type. Examples by field type:
//   text:          "My note"
//   number:        42.5
//   date:          "2026-06-01T00:00:00Z"
//   single_select: "option-ulid-abc"       ← option ID from planner_fields.options
//   multi_select:  ["option-ulid-a", "option-ulid-b"]
//   checkbox:      true
//   url:           "https://example.com"
//   iteration:     "iteration-ulid-xyz"    ← ID from planner_iterations
//   person:        "user-ulid-abc"         ← user ID

$table->timestamps();

// Unique constraint: one value row per (item, field)
$table->unique(['field_id', 'item_id', 'item_type']);
```

**JSONB Indexing Strategy (PostgreSQL):**
```sql
-- GIN index on value for containment queries (@>)
CREATE INDEX planner_field_values_value_gin ON planner_field_values USING gin(value);
-- For faster field_id + item lookups
CREATE INDEX planner_field_values_field_item ON planner_field_values (field_id, item_id, item_type);
```

---

### `planner_views` — Saved View Configurations

A named, persisted view with its own type, layout, filters, and sorts. Analogous to GitHub Projects' view tabs.

```php
$table->ulid('id')->primary();
$table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
$table->foreignUlid('milestone_id')->nullable()->constrained('milestones')->nullOnDelete();
// null = view spans all milestones (global view)

$table->string('name');                          // e.g. "All Tasks", "This Sprint"
$table->string('type');                          // table | board | roadmap | list
$table->boolean('is_default')->default(false);   // one default per user per milestone

$table->json('layout')->nullable();
// Stores column/card display configuration. Shape varies by view type:
//   table: {
//     "columns": [
//       { "field_id": "ulid", "visible": true, "width": 220, "pinned": "left|right|null" }
//     ]
//   }
//   board: {
//     "group_field_id": "ulid",         ← which field drives columns
//     "card_fields": ["ulid", "ulid"],  ← fields shown on cards
//     "column_order": ["opt-id-a", "opt-id-b"]
//   }
//   roadmap: {
//     "zoom": "week",                   ← day|week|month|quarter
//     "show_dependencies": true
//   }

$table->json('filters')->nullable();
// Array of filter rules:
// [
//   { "field_id": "ulid", "operator": "eq|neq|gt|lt|contains|in|not_in|is_empty|is_not_empty",
//     "value": <any> }
// ]

$table->json('sorts')->nullable();
// Array of sort rules (ordered, multi-sort):
// [
//   { "field_id": "ulid", "direction": "asc|desc" }
// ]

$table->json('group_by')->nullable();
// Single grouping configuration:
// { "field_id": "ulid", "direction": "asc|desc", "collapse_empty": true }

$table->unsignedSmallInteger('position')->default(0); // tab order
$table->timestamps();
```

---

### `planner_iterations` — Iteration Definitions (Phase 4)

Time-boxed sprints for the `iteration` field type. Scoped to a milestone.

```php
$table->ulid('id')->primary();
$table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
$table->foreignUlid('milestone_id')->nullable()->constrained('milestones')->nullOnDelete();
$table->string('title');                          // e.g. "Sprint 1", "Week 3"
$table->date('start_date');
$table->date('end_date');
$table->unsignedSmallInteger('position')->default(0);
$table->timestamps();
```

---

## Eloquent Models

### `PlannerField`
- `HasUlids`, `HasFactory`
- Fillable: `user_id`, `milestone_id`, `name`, `type`, `options`, `settings`, `position`, `is_system`
- Casts: `options` → array, `settings` → array, `is_system` → boolean
- Relations: `belongsTo(User)`, `belongsTo(Milestone)`, `hasMany(PlannerFieldValue)`
- Scopes: `scopeSystem()`, `scopeCustom()`, `scopeForMilestone()`

### `PlannerFieldValue`
- `HasUlids`, `HasFactory`
- Fillable: `field_id`, `item_id`, `item_type`, `value`
- Casts: `value` → `AsArrayObject` (preserves JSONB structure)
- Relations: `belongsTo(PlannerField)`, `morphTo('item')`

### `PlannerView`
- `HasUlids`, `HasFactory`
- Fillable: `user_id`, `milestone_id`, `name`, `type`, `is_default`, `layout`, `filters`, `sorts`, `group_by`, `position`
- Casts: `layout` → array, `filters` → array, `sorts` → array, `group_by` → array, `is_default` → boolean
- Relations: `belongsTo(User)`, `belongsTo(Milestone)`

---

## Existing Model Updates

### `Event` — add relation
```php
public function fieldValues(): HasMany
{
    return $this->hasMany(PlannerFieldValue::class, 'item_id')
        ->where('item_type', self::class);
}
```

### `Milestone` — add relation
```php
public function fieldValues(): HasMany
{
    return $this->hasMany(PlannerFieldValue::class, 'item_id')
        ->where('item_type', self::class);
}

public function fields(): HasMany
{
    return $this->hasMany(PlannerField::class);
}
```

---

## Query Patterns

### Loading items with their field values (N+1 safe)
```php
// Eager load field values with the field definition
$events = Event::with(['fieldValues.field', 'tags'])
    ->where('milestone_id', $milestoneId)
    ->orderBy('start_at')
    ->get();
```

### Filtering by a custom field value
```php
// Items where custom field "Effort" equals 3
$events = Event::whereHas('fieldValues', function ($q) use ($fieldId, $value) {
    $q->where('field_id', $fieldId)
      ->whereRaw("value::numeric = ?", [$value]);
})->get();

// Items where single_select field equals option ID
$events = Event::whereHas('fieldValues', function ($q) use ($fieldId, $optionId) {
    $q->where('field_id', $fieldId)
      ->whereRaw("value::text = ?", [json_encode($optionId)]);
})->get();

// Items where multi_select contains option ID
$events = Event::whereHas('fieldValues', function ($q) use ($fieldId, $optionId) {
    $q->where('field_id', $fieldId)
      ->whereRaw("value @> ?::jsonb", [json_encode([$optionId])]);
})->get();
```

---

## Migration Order

These migrations run after Phase 1 migrations:

1. `create_planner_fields_table`
2. `create_planner_field_values_table` (+ GIN index)
3. `create_planner_views_table`
4. `create_planner_iterations_table`
5. Seeder: `PlannerSystemFieldsSeeder` — seeds system fields for all existing users

---

## Cross-Milestone Linking (Life Planner Scope)

Unlike GitHub Projects (which links across repos), the Life Planner links across **life domains**. An event from the "Health" milestone can appear in a "Work-Life Balance" view alongside work events.

Implementation:
- `planner_views` with `milestone_id = null` → global view spanning all user milestones
- Filters can scope by `milestone_id` dynamically
- No foreign key coupling — the view query just omits the milestone filter
