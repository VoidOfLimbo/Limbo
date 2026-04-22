export interface PlannerTag {
    id: string
    name: string
    color: string | null
}

export interface PlannerMilestone {
    id: string
    title: string
    description: string | null
    status: 'active' | 'completed' | 'paused' | 'cancelled'
    priority: 'low' | 'medium' | 'high' | 'critical'
    start_at: string | null
    end_at: string | null
    duration_source: 'manual' | 'derived'
    deadline_type: 'soft' | 'hard'
    progress_source: 'derived' | 'manual'
    progress_override: number | null
    visibility: 'private' | 'shared'
    color: string | null
    total_events_count: number
    draft_events_count: number
    upcoming_events_count: number
    in_progress_events_count: number
    completed_events_count: number
    cancelled_events_count: number
    skipped_events_count: number
    breach_count: number
    progress: number
    is_breached: boolean
}

export type EventStatus = 'draft' | 'upcoming' | 'in_progress' | 'completed' | 'cancelled' | 'skipped'
export type EventPriority = 'low' | 'medium' | 'high' | 'critical'
export type EventType = 'event' | 'task' | 'milestone_marker'

export interface PlannerEvent {
    id: string
    user_id: string
    milestone_id: string | null
    parent_event_id: string | null
    title: string
    description: string | null
    type: EventType
    status: EventStatus
    priority: EventPriority
    start_at: string | null
    end_at: string | null
    is_all_day: boolean
    is_milestone_marker: boolean
    recurrence_rule: unknown | null
    visibility: 'private' | 'shared'
    color: string | null
    location: string | null
    snoozed_until: string | null
    snooze_count: number
    tags: PlannerTag[]
    milestone: Pick<PlannerMilestone, 'id' | 'title' | 'deadline_type' | 'end_at'> | null
    children: PlannerEvent[]
    field_values?: PlannerFieldValue[]
}

export interface PaginatedData<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
    next_page_url: string | null
    prev_page_url: string | null
}

export interface PlannerFilters {
    status?: string | string[]
    priority?: string | string[]
    tags?: string[]
    date_from?: string
    date_to?: string
    show_snoozed?: string
}

export type GroupByKey = 'quarter' | 'month' | 'status' | 'priority' | 'deadline' | 'visibility' | 'duration'

// ── Phase 3: Custom Fields + Views ────────────────────────────────────────────

export type PlannerFieldType =
    | 'text'
    | 'number'
    | 'date'
    | 'single_select'
    | 'multi_select'
    | 'checkbox'
    | 'url'
    | 'person'

export interface PlannerFieldOption {
    id: string
    name: string
    color: string | null
}

export interface PlannerField {
    id: string
    user_id: string
    milestone_id: string | null
    name: string
    type: PlannerFieldType
    options: PlannerFieldOption[] | null
    settings: Record<string, unknown> | null
    position: number
    is_system: boolean
    created_at: string
    updated_at: string
}

export interface PlannerFieldValue {
    id: string
    field_id: string
    item_id: string
    item_type: string
    value: unknown
}

export type PlannerViewType = 'list' | 'table' | 'board' | 'roadmap'

export interface PlannerViewTableLayout {
    columns: Array<{
        field_id: string
        visible: boolean
        width: number
        pinned: 'left' | 'right' | null
    }>
}

export interface PlannerViewBoardLayout {
    group_field_id: string
    card_fields: string[]
    column_order: string[]
}

export interface PlannerView {
    id: string
    user_id: string
    milestone_id: string | null
    name: string
    type: PlannerViewType
    is_default: boolean
    layout: PlannerViewTableLayout | PlannerViewBoardLayout | Record<string, unknown> | null
    filters: Array<{ field_id: string; operator: string; value: unknown }> | null
    sorts: Array<{ field_id: string; direction: 'asc' | 'desc' }> | null
    group_by: { field_id: string; direction: 'asc' | 'desc'; collapse_empty: boolean } | null
    position: number
    created_at: string
    updated_at: string
}

