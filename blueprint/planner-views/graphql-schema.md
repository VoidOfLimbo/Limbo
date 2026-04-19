# Planner Views — GraphQL Schema (Lighthouse)

**Depends on:** [`blueprint/planner-views/data-model.md`](./data-model.md)
**Status:** Blueprint / Design (Phase 3)

---

## Overview

We use [Lighthouse](https://lighthouse-php.com/) as the Laravel GraphQL server. It maps directly to Eloquent models and provides:
- Schema-first SDL definition
- Built-in pagination, filtering, sorting directives
- Subscription support via Laravel Echo channels
- Policy-driven authorization with `@can` directives

The schema lives in `graphql/schema.graphql`.

---

## Field Type Enum

```graphql
enum PlannerFieldType {
    TEXT
    NUMBER
    DATE
    SINGLE_SELECT
    MULTI_SELECT
    ITERATION
    URL
    PERSON
    CHECKBOX
}
```

---

## View Type Enum

```graphql
enum PlannerViewType {
    LIST
    TABLE
    BOARD
    ROADMAP
}
```

---

## Event / Item Enums (mirrors PHP Enums)

```graphql
enum EventStatus {
    DRAFT
    UPCOMING
    IN_PROGRESS
    COMPLETED
    CANCELLED
    SKIPPED
}

enum EventPriority {
    LOW
    MEDIUM
    HIGH
    CRITICAL
}

enum EventType {
    EVENT
    TASK
    MILESTONE_MARKER
}

enum MilestoneStatus {
    ACTIVE
    COMPLETED
    PAUSED
    CANCELLED
}
```

---

## Core Types

### SelectOption
```graphql
type SelectOption {
    id: ID!
    name: String!
    color: String
}

input SelectOptionInput {
    id: ID
    name: String!
    color: String
}
```

### PlannerField
```graphql
type PlannerField {
    id: ID!
    name: String!
    type: PlannerFieldType!
    isSystem: Boolean!
    position: Int!
    options: [SelectOption!]          # for SELECT types
    settings: JSON                    # type-specific config
    milestone: Milestone
    createdAt: DateTime!
    updatedAt: DateTime!
}
```

### PlannerFieldValue
```graphql
type PlannerFieldValue {
    id: ID!
    field: PlannerField!
    value: JSON                       # typed by field.type at runtime
}
```

### PlannerView
```graphql
type PlannerView {
    id: ID!
    name: String!
    type: PlannerViewType!
    isDefault: Boolean!
    position: Int!
    layout: JSON
    filters: JSON
    sorts: JSON
    groupBy: JSON
    milestone: Milestone
    createdAt: DateTime!
    updatedAt: DateTime!
}
```

### PlannerIteration
```graphql
type PlannerIteration {
    id: ID!
    title: String!
    startDate: Date!
    endDate: Date!
    position: Int!
    milestone: Milestone
}
```

### Tag
```graphql
type Tag {
    id: ID!
    name: String!
    color: String
}
```

### Milestone
```graphql
type Milestone {
    id: ID!
    title: String!
    description: String
    status: MilestoneStatus!
    priority: MilestonePriority!
    startAt: DateTime
    endAt: DateTime
    color: String
    progress: Int!                    # computed 0-100
    events(
        first: Int
        after: String
        status: [EventStatus!]
        priority: [EventPriority!]
        type: [EventType!]
        search: String
    ): EventConnection! @paginate
    fieldValues: [PlannerFieldValue!]!
    tags: [Tag!]!
    createdAt: DateTime!
    updatedAt: DateTime!
}
```

### Event (PlannerItem)
```graphql
type Event {
    id: ID!
    title: String!
    description: String
    type: EventType!
    status: EventStatus!
    priority: EventPriority!
    startAt: DateTime
    endAt: DateTime
    isAllDay: Boolean!
    isMilestoneMarker: Boolean!
    color: String
    location: String
    snoozedUntil: DateTime
    snoozeCount: Int!
    milestone: Milestone
    parentEvent: Event
    childEvents: [Event!]!
    dependencies: [EventDependency!]!
    fieldValues: [PlannerFieldValue!]!
    tags: [Tag!]!
    recurrenceRule: JSON
    createdAt: DateTime!
    updatedAt: DateTime!
}

type EventDependency {
    id: ID!
    dependsOnEvent: Event!
    type: DependencyType!
}

type EventConnection {
    edges: [EventEdge!]!
    pageInfo: PageInfo!
    totalCount: Int!
}

type EventEdge {
    node: Event!
    cursor: String!
}
```

---

## Queries

```graphql
type Query {
    # ── Views ────────────────────────────────────────────────────────────
    plannerViews(milestoneId: ID): [PlannerView!]!
        @auth
        @can(ability: "viewAny", model: "App\\Models\\PlannerView")

    plannerView(id: ID! @eq): PlannerView
        @auth
        @find
        @can(ability: "view", model: "App\\Models\\PlannerView")

    # ── Fields ───────────────────────────────────────────────────────────
    plannerFields(milestoneId: ID): [PlannerField!]!
        @auth
        # Returns system fields + custom fields for the milestone (or global)

    # ── Milestones ───────────────────────────────────────────────────────
    milestones(
        status: [MilestoneStatus!]
        first: Int
        after: String
    ): MilestoneConnection! @auth @paginate

    milestone(id: ID! @eq): Milestone
        @auth
        @find
        @can(ability: "view", model: "App\\Models\\Milestone")

    # ── Events (flat — for table/board view across milestones) ───────────
    plannerItems(
        milestoneId: ID
        status: [EventStatus!]
        priority: [EventPriority!]
        type: [EventType!]
        tagIds: [ID!]
        search: String
        fieldFilters: [FieldFilterInput!]  # custom field filters
        sortBy: [SortInput!]
        first: Int = 50
        after: String
    ): EventConnection! @auth

    event(id: ID! @eq): Event
        @auth
        @find
        @can(ability: "view", model: "App\\Models\\Event")

    # ── Iterations ───────────────────────────────────────────────────────
    plannerIterations(milestoneId: ID): [PlannerIteration!]! @auth
}
```

### Input Types for Queries
```graphql
input FieldFilterInput {
    fieldId: ID!
    operator: FilterOperator!
    value: JSON
}

enum FilterOperator {
    EQ
    NEQ
    GT
    LT
    GTE
    LTE
    CONTAINS
    NOT_CONTAINS
    IN
    NOT_IN
    IS_EMPTY
    IS_NOT_EMPTY
}

input SortInput {
    fieldId: ID!
    direction: SortDirection!
}

enum SortDirection {
    ASC
    DESC
}
```

---

## Mutations

```graphql
type Mutation {
    # ── Events ───────────────────────────────────────────────────────────
    createEvent(input: CreateEventInput! @spread): Event!
        @auth
        @can(ability: "create", model: "App\\Models\\Event")

    updateEvent(id: ID!, input: UpdateEventInput! @spread): Event!
        @auth
        @can(ability: "update", find: "id", model: "App\\Models\\Event")

    deleteEvent(id: ID!): Event
        @auth
        @can(ability: "delete", find: "id", model: "App\\Models\\Event")
        @delete

    snoozeEvent(id: ID!, until: DateTime!): Event!
        @auth
        @can(ability: "update", find: "id", model: "App\\Models\\Event")

    updateEventFieldValue(
        eventId: ID!
        fieldId: ID!
        value: JSON
    ): PlannerFieldValue!
        @auth

    bulkUpdateEvents(
        ids: [ID!]!
        input: BulkUpdateEventInput! @spread
    ): [Event!]!
        @auth

    bulkDeleteEvents(ids: [ID!]!): [Event!]! @auth

    # ── Milestones ───────────────────────────────────────────────────────
    createMilestone(input: CreateMilestoneInput! @spread): Milestone!
        @auth
        @can(ability: "create", model: "App\\Models\\Milestone")

    updateMilestone(id: ID!, input: UpdateMilestoneInput! @spread): Milestone!
        @auth
        @can(ability: "update", find: "id", model: "App\\Models\\Milestone")

    deleteMilestone(id: ID!): Milestone
        @auth
        @can(ability: "delete", find: "id", model: "App\\Models\\Milestone")
        @delete

    # ── Custom Fields ────────────────────────────────────────────────────
    createPlannerField(input: CreatePlannerFieldInput! @spread): PlannerField!
        @auth

    updatePlannerField(id: ID!, input: UpdatePlannerFieldInput! @spread): PlannerField!
        @auth

    deletePlannerField(id: ID!): PlannerField @auth @delete

    reorderPlannerFields(fieldIds: [ID!]!): [PlannerField!]! @auth

    # ── Views ────────────────────────────────────────────────────────────
    createPlannerView(input: CreatePlannerViewInput! @spread): PlannerView!
        @auth

    updatePlannerView(id: ID!, input: UpdatePlannerViewInput! @spread): PlannerView!
        @auth

    deletePlannerView(id: ID!): PlannerView @auth @delete

    setDefaultPlannerView(id: ID!): PlannerView! @auth

    # ── Iterations ───────────────────────────────────────────────────────
    createPlannerIteration(input: CreateIterationInput! @spread): PlannerIteration! @auth
    updatePlannerIteration(id: ID!, input: UpdateIterationInput! @spread): PlannerIteration! @auth
    deletePlannerIteration(id: ID!): PlannerIteration @auth @delete
}
```

### Mutation Input Types
```graphql
input CreateEventInput {
    milestoneId: ID
    parentEventId: ID
    title: String!
    description: String
    type: EventType
    status: EventStatus
    priority: EventPriority
    startAt: DateTime
    endAt: DateTime
    isAllDay: Boolean
    color: String
    location: String
    tagIds: [ID!]
}

input UpdateEventInput {
    title: String
    description: String
    type: EventType
    status: EventStatus
    priority: EventPriority
    startAt: DateTime
    endAt: DateTime
    isAllDay: Boolean
    milestoneId: ID
    color: String
    location: String
    tagIds: [ID!]
}

input BulkUpdateEventInput {
    status: EventStatus
    priority: EventPriority
    milestoneId: ID
    tagIds: [ID!]
}

input CreatePlannerFieldInput {
    milestoneId: ID
    name: String!
    type: PlannerFieldType!
    options: [SelectOptionInput!]
    settings: JSON
    position: Int
}

input UpdatePlannerFieldInput {
    name: String
    options: [SelectOptionInput!]
    settings: JSON
    position: Int
}

input CreatePlannerViewInput {
    milestoneId: ID
    name: String!
    type: PlannerViewType!
    layout: JSON
    filters: JSON
    sorts: JSON
    groupBy: JSON
}

input UpdatePlannerViewInput {
    name: String
    layout: JSON
    filters: JSON
    sorts: JSON
    groupBy: JSON
    position: Int
}
```

---

## Subscriptions

```graphql
type Subscription {
    # Fires when any event in the user's planner is created/updated/deleted
    plannerItemUpdated(milestoneId: ID): PlannerItemEvent!
        @subscription(class: "App\\GraphQL\\Subscriptions\\PlannerItemUpdated")

    # Fires when a field value changes
    plannerFieldValueUpdated(milestoneId: ID): FieldValueEvent!
        @subscription(class: "App\\GraphQL\\Subscriptions\\PlannerFieldValueUpdated")

    # Fires when a view config changes (another session updated the view)
    plannerViewUpdated(viewId: ID!): PlannerView!
        @subscription(class: "App\\GraphQL\\Subscriptions\\PlannerViewUpdated")
}

type PlannerItemEvent {
    action: String!     # "created" | "updated" | "deleted"
    event: Event
    eventId: ID         # for "deleted" actions where model is gone
}

type FieldValueEvent {
    action: String!     # "updated"
    fieldValue: PlannerFieldValue!
    eventId: ID!
}
```

---

## Lighthouse Configuration Notes

### Pagination
Use cursor-based pagination (`@paginate`) for all list queries. Return `*Connection` types with `edges`, `pageInfo`, and `totalCount`.

### Authorization
- `@auth` — all queries/mutations require authentication
- `@can` — delegate to existing Eloquent policies (EventPolicy, MilestonePolicy)
- Custom field and view operations check `user_id = auth()->id()` in resolvers

### Custom Resolvers
Operations that can't be handled by Lighthouse directives alone:
- `plannerItems` — complex multi-field filter query
- `updateEventFieldValue` — upsert into `planner_field_values`
- `bulkUpdateEvents` / `bulkDeleteEvents`
- All subscription resolution

Resolvers live in `app/GraphQL/Queries/`, `app/GraphQL/Mutations/`, `app/GraphQL/Subscriptions/`.

### N+1 Prevention
Use `@with` and Lighthouse's built-in DataLoader batching:
```graphql
type Event {
    fieldValues: [PlannerFieldValue!]! @hasMany @with(relation: "fieldValues.field")
    tags: [Tag!]! @morphToMany
}
```

---

## Endpoint

```
POST /graphql
```

All planner data operations go through this single endpoint. Inertia page loads still use REST routes for initial data hydration.
