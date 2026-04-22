import { router, usePage } from '@inertiajs/vue3'
import type { PlannerFilters } from '@/types/planner'

/**
 * Manages planner filter state and applies them via partial Inertia reloads.
 * Filters are reflected in the URL so the view is bookmarkable.
 * Preferences (view, per_page) are passed as headers, not URL params.
 */
export function usePlannerFilters(
    currentFilters: PlannerFilters,
    activeMilestoneId: string | null,
    getHeaders?: () => Record<string, string>,
) {
    const page = usePage()

    function pathname() {
        return page.url.split('?')[0]
    }

    function applyFilters(patch: Partial<PlannerFilters>) {
        const merged = { ...currentFilters, ...patch }

        // Remove empty/undefined values to keep the URL clean
        const query: Record<string, unknown> = { milestone: activeMilestoneId }
        for (const [key, value] of Object.entries(merged)) {
            if (value !== undefined && value !== null && value !== '' && !(Array.isArray(value) && value.length === 0)) {
                query[key] = value
            }
        }

        router.visit(pathname(), {
            data: query,
            preserveScroll: true,
            preserveState: true,
            only: ['events', 'filters'],
            replace: true,
            headers: getHeaders?.() ?? {},
        })
    }

    function clearFilters() {
        router.visit(pathname(), {
            data: { milestone: activeMilestoneId },
            preserveScroll: true,
            preserveState: true,
            only: ['events', 'filters'],
            replace: true,
            headers: getHeaders?.() ?? {},
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
