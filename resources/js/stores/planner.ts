import { defineStore } from 'pinia';
import { ref, watch } from 'vue';

export type PlannerView = 'list' | 'table' | 'board';

const STORAGE_KEY = 'planner:activeView';

export const usePlannerStore = defineStore('planner', () => {
    const activeView = ref<PlannerView>(
        (localStorage.getItem(STORAGE_KEY) as PlannerView) ?? 'list',
    );

    watch(activeView, (val) => {
        localStorage.setItem(STORAGE_KEY, val);
    });

    function setView(view: PlannerView) {
        activeView.value = view;
    }

    return { activeView, setView };
});
