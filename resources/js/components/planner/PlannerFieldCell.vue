<script setup lang="ts">
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { update as updateEvent } from '@/actions/App/Http/Controllers/Planner/EventController'
import PlannerBadge from '@/components/planner/PlannerBadge.vue'
import type { PlannerEvent, EventStatus, EventPriority } from '@/types/planner'

const props = defineProps<{
    event: PlannerEvent
    fieldKey: 'title'
    value: string
}>()

const emit = defineEmits<{
    update: [value: string]
}>()

const editing = ref(false)
const inputRef = ref<HTMLInputElement | null>(null)
const localValue = ref(props.value)

function startEdit() {
    localValue.value = props.value
    editing.value = true
    // Focus in next tick after render
    setTimeout(() => inputRef.value?.focus(), 0)
}

function cancelEdit() {
    editing.value = false
}

function saveEdit() {
    if (!editing.value) return
    editing.value = false
    if (localValue.value === props.value) return

    const def = updateEvent(props.event.id)
    router.visit(def.url, {
        method: def.method,
        data: { [props.fieldKey]: localValue.value },
        preserveScroll: true,
        only: ['events'],
    })
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter') {
        saveEdit()
    } else if (e.key === 'Escape') {
        cancelEdit()
    }
}
</script>

<template>
    <div class="w-full">
        <!-- Edit mode -->
        <input
            v-if="editing"
            ref="inputRef"
            v-model="localValue"
            class="w-full bg-transparent border border-primary rounded px-1 py-0.5 text-sm outline-none focus:ring-1 focus:ring-primary"
            @blur="saveEdit"
            @keydown="onKeydown"
        />

        <!-- Read mode -->
        <button
            v-else
            type="button"
            class="w-full text-left truncate text-sm hover:text-primary transition-colors"
            @click="startEdit"
        >
            {{ value || '—' }}
        </button>
    </div>
</template>
