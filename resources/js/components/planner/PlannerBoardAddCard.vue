<script setup lang="ts">
import { ref } from 'vue'
import { Plus } from 'lucide-vue-next'

const emit = defineEmits<{
    add: [title: string]
}>()

const adding = ref(false)
const title = ref('')
const inputRef = ref<HTMLInputElement | null>(null)

function startAdding() {
    title.value = ''
    adding.value = true
    setTimeout(() => inputRef.value?.focus(), 0)
}

function cancel() {
    adding.value = false
    title.value = ''
}

function submit() {
    const trimmed = title.value.trim()
    if (trimmed) {
        emit('add', trimmed)
    }
    cancel()
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter') {
        submit()
    } else if (e.key === 'Escape') {
        cancel()
    }
}
</script>

<template>
    <div class="px-2 pb-2">
        <!-- Input mode -->
        <div v-if="adding" class="flex flex-col gap-1.5">
            <input
                ref="inputRef"
                v-model="title"
                placeholder="Event title…"
                class="w-full rounded-md border border-border bg-background px-2.5 py-1.5 text-sm outline-none focus:ring-1 focus:ring-primary"
                @keydown="onKeydown"
                @blur="submit"
            />
            <p class="text-xs text-muted-foreground">Enter to add · Esc to cancel</p>
        </div>

        <!-- Add button -->
        <button
            v-else
            type="button"
            class="flex w-full items-center gap-1.5 rounded-md px-2 py-1.5 text-sm text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
            @click="startAdding"
        >
            <Plus class="size-3.5" />
            Add event
        </button>
    </div>
</template>
