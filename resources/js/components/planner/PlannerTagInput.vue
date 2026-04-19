<script setup lang="ts">
import { ref, computed } from 'vue'
import { X, Tag as TagIcon, Plus, Check } from 'lucide-vue-next'
import {
    ComboboxRoot,
    ComboboxInput,
    ComboboxContent,
    ComboboxEmpty,
    ComboboxItem,
    ComboboxItemIndicator,
    ComboboxGroup,
    ComboboxLabel,
    ComboboxViewport,
} from 'reka-ui'
import { cn } from '@/lib/utils'
import type { PlannerTag } from '@/types/planner'

const props = defineProps<{
    modelValue: string[]          // selected tag IDs
    tags: PlannerTag[]            // available tags
    creatingAllowed?: boolean     // allow inline tag creation (default: true)
}>()

const emit = defineEmits<{
    'update:modelValue': [ids: string[]]
    'create': [name: string]      // parent handles creation and appends new tag
}>()

const search = ref('')
const open = ref(false)

const selectedTags = computed(() =>
    props.tags.filter((t) => props.modelValue.includes(t.id)),
)

const filteredTags = computed(() => {
    const q = search.value.trim().toLowerCase()
    if (!q) return props.tags
    return props.tags.filter((t) => t.name.toLowerCase().includes(q))
})

const canCreate = computed(() => {
    if (!(props.creatingAllowed ?? true)) return false
    const q = search.value.trim()
    if (!q) return false
    return !props.tags.some((t) => t.name.toLowerCase() === q.toLowerCase())
})

function toggle(tagId: string) {
    const current = props.modelValue
    const next = current.includes(tagId)
        ? current.filter((id) => id !== tagId)
        : [...current, tagId]
    emit('update:modelValue', next)
}

function remove(tagId: string) {
    emit('update:modelValue', props.modelValue.filter((id) => id !== tagId))
}

function createAndSelect() {
    const name = search.value.trim()
    if (!name) return
    emit('create', name)
    search.value = ''
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && canCreate.value) {
        e.preventDefault()
        createAndSelect()
    }
    if (e.key === 'Backspace' && !search.value && props.modelValue.length) {
        remove(props.modelValue[props.modelValue.length - 1])
    }
}
</script>

<template>
    <div class="space-y-1.5">
        <!-- Selected tag chips -->
        <div
            v-if="selectedTags.length"
            class="flex flex-wrap gap-1.5"
        >
            <span
                v-for="tag in selectedTags"
                :key="tag.id"
                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium border"
                :style="tag.color
                    ? { backgroundColor: `${tag.color}25`, color: tag.color, borderColor: `${tag.color}50` }
                    : undefined"
                :class="!tag.color ? 'bg-secondary text-secondary-foreground border-border' : ''"
            >
                {{ tag.name }}
                <button
                    type="button"
                    class="hover:opacity-70 transition-opacity focus:outline-none"
                    @click="remove(tag.id)"
                >
                    <X class="size-2.5" />
                </button>
            </span>
        </div>

        <!-- Combobox -->
        <ComboboxRoot
            v-model:open="open"
            :filter-function="() => filteredTags"
            ignore-filter
        >
            <!-- Input trigger -->
            <div
                class="flex items-center gap-1.5 rounded-md border border-input bg-background px-2 py-1.5 text-sm
                       focus-within:ring-1 focus-within:ring-ring transition-shadow"
            >
                <TagIcon class="size-3.5 shrink-0 text-muted-foreground" />
                <ComboboxInput
                    v-model="search"
                    placeholder="Add tags…"
                    class="flex-1 bg-transparent outline-none placeholder:text-muted-foreground text-xs"
                    @keydown="handleKeydown"
                    @focus="open = true"
                />
            </div>

            <!-- Dropdown -->
            <ComboboxContent
                :class="cn(
                    'z-50 max-h-52 overflow-y-auto rounded-md border border-border bg-popover shadow-md',
                    'w-[--reka-combobox-trigger-width] min-w-[180px]',
                )"
                position="popper"
                :side-offset="4"
            >
                <ComboboxViewport class="p-1">
                    <ComboboxEmpty class="py-6 text-center text-xs text-muted-foreground">
                        <template v-if="canCreate">
                            Press Enter to create "{{ search.trim() }}"
                        </template>
                        <template v-else>
                            No tags found
                        </template>
                    </ComboboxEmpty>

                    <ComboboxGroup v-if="filteredTags.length">
                        <ComboboxLabel class="px-2 py-1 text-xs font-medium text-muted-foreground">
                            Tags
                        </ComboboxLabel>
                        <ComboboxItem
                            v-for="tag in filteredTags"
                            :key="tag.id"
                            :value="tag.id"
                            class="relative flex items-center gap-2 rounded px-2 py-1.5 text-xs cursor-pointer
                                   hover:bg-accent select-none outline-none data-[highlighted]:bg-accent"
                            @select.prevent="toggle(tag.id)"
                        >
                            <!-- Color dot -->
                            <span
                                class="size-2 rounded-full shrink-0"
                                :style="tag.color ? { backgroundColor: tag.color } : undefined"
                                :class="!tag.color ? 'bg-muted-foreground/40' : ''"
                            />
                            {{ tag.name }}
                            <ComboboxItemIndicator class="ml-auto">
                                <Check v-if="modelValue.includes(tag.id)" class="size-3 text-primary" />
                            </ComboboxItemIndicator>
                        </ComboboxItem>
                    </ComboboxGroup>

                    <!-- Create option -->
                    <div
                        v-if="canCreate"
                        class="flex items-center gap-2 rounded px-2 py-1.5 text-xs cursor-pointer
                               hover:bg-accent text-muted-foreground mt-1 border-t border-border pt-2"
                        @click="createAndSelect"
                    >
                        <Plus class="size-3" />
                        Create "{{ search.trim() }}"
                    </div>
                </ComboboxViewport>
            </ComboboxContent>
        </ComboboxRoot>
    </div>
</template>
