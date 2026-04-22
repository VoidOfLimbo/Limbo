<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { Plus, Check } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { store as storeView, destroy as destroyView, activate as activateView } from '@/actions/App/Http/Controllers/Planner/PlannerViewController'
import type { PlannerView, PlannerViewType } from '@/types/planner'

const props = defineProps<{
    views: PlannerView[]
    activeViewId: string | null
    milestoneId: string | null
}>()

const emit = defineEmits<{
    activate: [viewId: string]
    create: [view: PlannerView]
    delete: [viewId: string]
}>()

const viewTypes: { id: PlannerViewType; label: string }[] = [
    { id: 'list', label: 'List' },
    { id: 'table', label: 'Table' },
    { id: 'board', label: 'Board' },
]

const createOpen = ref(false)
const newViewName = ref('')
const newViewType = ref<PlannerViewType>('list')
const creating = ref(false)

function handleActivate(view: PlannerView) {
    if (view.id === props.activeViewId) {
        return
    }

    router.post(
        activateView.url({ plannerView: view.id }),
        {},
        {
            preserveScroll: true,
            only: ['savedViews'],
        },
    )

    emit('activate', view.id)
}

function handleCreate() {
    if (!newViewName.value.trim() || creating.value) {
        return
    }

    creating.value = true

    router.post(
        storeView.url(),
        {
            name: newViewName.value.trim(),
            type: newViewType.value,
            milestone_id: props.milestoneId,
        },
        {
            preserveScroll: true,
            only: ['savedViews'],
            onSuccess: () => {
                createOpen.value = false
                newViewName.value = ''
                newViewType.value = 'list'
            },
            onFinish: () => {
                creating.value = false
            },
        },
    )
}

function handleDelete(view: PlannerView) {
    router.delete(destroyView.url({ plannerView: view.id }), {
        preserveScroll: true,
        only: ['savedViews'],
    })

    emit('delete', view.id)
}
</script>

<template>
    <div v-if="views.length > 0 || true" class="flex items-center gap-1 px-4 py-1.5 border-b border-border overflow-x-auto shrink-0">
        <!-- View tabs -->
        <button
            v-for="view in views"
            :key="view.id"
            type="button"
            class="group relative flex items-center gap-1.5 rounded px-2.5 py-1 text-xs font-medium transition-colors whitespace-nowrap"
            :class="
                view.id === activeViewId
                    ? 'bg-primary/10 text-primary'
                    : 'text-muted-foreground hover:text-foreground hover:bg-muted'
            "
            @click="handleActivate(view)"
        >
            {{ view.name }}
            <span
                v-if="view.id === activeViewId"
                class="ml-0.5 size-1.5 rounded-full bg-primary"
            />
            <!-- Delete button -->
            <span
                class="ml-1 hidden size-3.5 items-center justify-center rounded text-muted-foreground opacity-0 transition-opacity hover:text-destructive group-hover:flex group-hover:opacity-100"
                role="button"
                tabindex="0"
                aria-label="Delete view"
                @click.stop="handleDelete(view)"
                @keydown.enter.stop="handleDelete(view)"
            >
                ×
            </span>
        </button>

        <!-- Create new view -->
        <Popover v-model:open="createOpen">
            <PopoverTrigger as-child>
                <Button variant="ghost" size="icon" class="size-6 shrink-0">
                    <Plus class="size-3.5" />
                </Button>
            </PopoverTrigger>
            <PopoverContent class="w-56 p-3" align="start">
                <div class="space-y-2">
                    <p class="text-xs font-medium text-muted-foreground">New view</p>
                    <Input
                        v-model="newViewName"
                        placeholder="View name…"
                        class="h-7 text-xs"
                        @keydown.enter="handleCreate"
                    />
                    <div class="flex gap-1">
                        <button
                            v-for="vt in viewTypes"
                            :key="vt.id"
                            type="button"
                            class="flex flex-1 items-center justify-center rounded border px-2 py-1 text-xs transition-colors"
                            :class="
                                newViewType === vt.id
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-border text-muted-foreground hover:border-foreground hover:text-foreground'
                            "
                            @click="newViewType = vt.id"
                        >
                            <Check v-if="newViewType === vt.id" class="mr-1 size-3" />
                            {{ vt.label }}
                        </button>
                    </div>
                    <Button
                        size="sm"
                        class="w-full h-7 text-xs"
                        :disabled="!newViewName.trim() || creating"
                        @click="handleCreate"
                    >
                        Create
                    </Button>
                </div>
            </PopoverContent>
        </Popover>
    </div>
</template>
