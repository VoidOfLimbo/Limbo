import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

export type PlannerViewMode = 'list' | 'table' | 'board';

const STORAGE_KEY = 'planner:activeView';

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

    return { activeView, setView };
});
