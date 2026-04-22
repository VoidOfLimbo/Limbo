import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

export type PlannerViewMode = 'list' | 'table' | 'board';
export type GroupByKey = 'quarter' | 'month' | 'status' | 'priority' | 'deadline' | 'visibility' | 'duration';

const STORAGE_KEY = 'planner:activeView';
const STORAGE_GROUP_BY_KEY = 'planner:groupBy';

export const usePlannerStore = defineStore('planner', () => {
    const stored = typeof localStorage !== 'undefined' ? localStorage.getItem(STORAGE_KEY) : null
    const activeView = ref<PlannerViewMode>((stored as PlannerViewMode) ?? 'list')

    watch(activeView, (val) => {
        if (typeof localStorage !== 'undefined') {
            localStorage.setItem(STORAGE_KEY, val)
        }
    })

    function setView(view: PlannerViewMode) {
        activeView.value = view;
    }

    const storedGroupBy = typeof localStorage !== 'undefined' ? localStorage.getItem(STORAGE_GROUP_BY_KEY) : null
    const groupBy = ref<GroupByKey>((storedGroupBy as GroupByKey) ?? 'status')

    watch(groupBy, (val) => {
        if (typeof localStorage !== 'undefined') {
            localStorage.setItem(STORAGE_GROUP_BY_KEY, val)
        }
    })

    return { activeView, setView, groupBy };
});
