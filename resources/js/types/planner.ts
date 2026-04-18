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
    completed_events_count: number
    progress: number
    is_breached: boolean
}

export type EventStatus = 'draft' | 'upcoming' | 'in_progress' | 'completed' | 'cancelled' | 'skipped'
export type EventPriority = 'low' | 'medium' | 'high' | 'critical'
export type EventType = 'event' | 'task' | 'milestone_marker'

export interface PlannerEventChild {
    id: string
    parent_event_id: string
    title: string
    status: EventStatus
    priority: EventPriority
    start_at: string | null
    end_at: string | null
}

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
    children: PlannerEventChild[]
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
