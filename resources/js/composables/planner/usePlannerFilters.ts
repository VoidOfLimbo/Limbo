import { router } from '@inertiajs/vue3'
import type { PlannerFilters } from '@/types/planner'
/**
 * Manages planner filter state and applies them via partial Inertia reloads.
 * Filters are reflected in the URL so the view is bookmarkable.
 */
export function usePlannerFilters(currentFilters: PlannerFilters, activeMilestoneId: string | null) {
    function applyFilters(patch: Partial<PlannerFilters>) {
        const merged = { ...currentFilters, ...patch }

        // Remove empty/undefined values to keep the URL clean
        const query: Record<string, unknown> = { milestone: activeMilestoneId }
        for (const [key, value] of Object.entries(merged)) {
            if (value !== undefined && value !== null && value !== '' && !(Array.isArray(value) && value.length === 0)) {
                query[key] = value
            }
        }

        router.visit(window.location.pathname, {
            data: query,
            preserveScroll: true,
            preserveState: true,
            only: ['events', 'filters'],
            replace: true,
        })
    }

    function clearFilters() {
        router.visit(window.location.pathname, {
            data: { milestone: activeMilestoneId },
            preserveScroll: true,
            preserveState: true,
            only: ['events', 'filters'],
            replace: true,
        })
    }

    function hasActiveFilters(filters: PlannerFilters): boolean {
        return !!(
            filters.status ||
            filters.priority ||
            (filters.tags && filters.tags.length > 0) ||
            filters.date_from ||
            filters.date_to ||
            filters.show_snoozed
        )
    }

    return { applyFilters, clearFilters, hasActiveFilters }
}
